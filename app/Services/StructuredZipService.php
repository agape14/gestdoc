<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class StructuredZipService
{
    public const TYPE_CONTRATOS = 'CONTRATOS';
    public const TYPE_RESOLUCIONES = 'RESOLUCIONES_CONFORMIDADES';
    public const TYPE_COMPROBANTES = 'COMPROBANTES';
    public const TYPE_OTROS = 'OTROS';
    public const TYPE_SUSPENSION = 'SUSPENSION';
    public const TYPE_REINICIO = 'REINICIO';
    public const TYPE_TDR = 'TDR';
    public const TYPE_PRODUCTOS = 'PRODUCTOS_TECNICOS';

    /**
     * @param  array<int, array{zipPath: string, storagePath: string}>  $entries
     */
    public static function streamDownload(array $entries, string $downloadName)
    {
        $used = [];
        $zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'gestdoc-' . uniqid('', true) . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'No se pudo crear el archivo ZIP.');
        }

        $tempHandles = [];
        foreach ($entries as $entry) {
            $storagePath = $entry['storagePath'] ?? '';
            if ($storagePath === '') {
                continue;
            }
            $disk = storage_disk_for_path($storagePath);
            if (! Storage::disk($disk)->exists($storagePath)) {
                continue;
            }

            $zipInner = self::uniqueZipPath($entry['zipPath'] ?? 'documento.bin', $used);
            $stream = Storage::disk($disk)->readStream($storagePath);
            if ($stream === false || $stream === null) {
                continue;
            }
            $tmp = tmpfile();
            if ($tmp === false) {
                fclose($stream);
                continue;
            }
            stream_copy_to_stream($stream, $tmp);
            if (is_resource($stream)) {
                fclose($stream);
            }
            $meta = stream_get_meta_data($tmp);
            $tmpPath = $meta['uri'] ?? null;
            if (! $tmpPath) {
                fclose($tmp);
                continue;
            }
            $zip->addFile($tmpPath, $zipInner);
            $tempHandles[] = $tmp;
        }

        $zip->close();
        foreach ($tempHandles as $h) {
            if (is_resource($h)) {
                fclose($h);
            }
        }

        $safeName = preg_replace('/[^A-Za-z0-9._-]+/', '-', $downloadName) ?: 'documentos.zip';
        if (! str_ends_with(strtolower($safeName), '.zip')) {
            $safeName .= '.zip';
        }

        return response()->streamDownload(function () use ($zipPath) {
            $out = fopen($zipPath, 'rb');
            if ($out) {
                fpassthru($out);
                fclose($out);
            }
            @unlink($zipPath);
        }, $safeName, [
            'Content-Type' => 'application/zip',
        ]);
    }

    public static function sanitizeSegment(?string $name, int $max = 70): string
    {
        $name = trim((string) $name);
        $name = preg_replace('/[\\\\\/:*?"<>|]+/', ' ', $name) ?? $name;
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;
        $slug = Str::slug(mb_substr($name, 0, $max), '_');

        return $slug !== '' ? Str::upper($slug) : 'SIN_NOMBRE';
    }

    public static function projectFolder(?string $cui, ?string $nombre): string
    {
        $cui = trim((string) $cui);
        $nombreSeg = self::sanitizeSegment($nombre ?: '', 50);
        if ($cui !== '') {
            $cuiSeg = self::sanitizeSegment($cui, 20);

            return $nombreSeg !== 'SIN_NOMBRE' ? $cuiSeg . '_' . $nombreSeg : $cuiSeg;
        }

        return $nombreSeg;
    }

    public static function mapDocumentoTipo(?string $tipoONombre): string
    {
        $t = Str::upper(trim((string) $tipoONombre));
        if ($t === '' || str_contains($t, 'OTRO')) {
            return self::TYPE_OTROS;
        }
        if (str_contains($t, 'CONTRATO')) {
            return self::TYPE_CONTRATOS;
        }
        if (str_contains($t, 'CONFORMIDAD') || str_contains($t, 'RESOLUC') || str_contains($t, 'ACTA')) {
            return self::TYPE_RESOLUCIONES;
        }
        if (str_contains($t, 'COMPROBANTE') || str_contains($t, 'PAGO')) {
            return self::TYPE_COMPROBANTES;
        }
        if (str_contains($t, 'TDR')) {
            return self::TYPE_TDR;
        }
        if (str_contains($t, 'SUSPENS')) {
            return self::TYPE_SUSPENSION;
        }
        if (str_contains($t, 'REINICIO')) {
            return self::TYPE_REINICIO;
        }

        return self::TYPE_OTROS;
    }

    public static function originalFilename(string $storagePath, ?string $fallback = null): string
    {
        $base = basename(str_replace('\\', '/', $storagePath));
        if ($base === '' || $base === '.' || $base === '..') {
            $base = $fallback ?: 'archivo';
        }
        $base = preg_replace('/[\\\\\/:*?"<>|]+/', '_', $base) ?? $base;

        return $base !== '' ? $base : 'archivo.bin';
    }

    /**
     * Nombre legible dentro del ZIP: etiqueta del documento + extensión real del archivo.
     */
    public static function readableFilename(string $storagePath, ?string $label = null): string
    {
        $ext = strtolower(pathinfo($storagePath, PATHINFO_EXTENSION));
        $slug = Str::slug(mb_substr(trim((string) $label), 0, 80));
        if ($slug === '') {
            return self::originalFilename($storagePath, $label);
        }

        return $ext !== '' ? $slug . '.' . $ext : $slug;
    }

    /**
     * Extrae entradas ZIP de un registro de experiencia / consultor / expediente.
     *
     * @param  array<int, string>  $allowedTypes
     * @return array<int, array{zipPath: string, storagePath: string}>
     */
    public static function entriesFromRecord(object $model, array $allowedTypes = []): array
    {
        $project = self::projectFolder(
            $model->cui ?? null,
            $model->cliente ?? $model->proyecto ?? $model->titulo ?? $model->objeto_del_contrato ?? $model->entidad ?? ('registro-' . ($model->id ?? ''))
        );

        $out = [];
        $push = function (?string $typeFolder, ?string $path, ?string $label) use (&$out, $project, $allowedTypes) {
            if (! $path) {
                return;
            }
            $typeFolder = $typeFolder ?: self::TYPE_OTROS;
            if ($allowedTypes && ! in_array($typeFolder, $allowedTypes, true)) {
                return;
            }
            $out[] = [
                'zipPath' => $project . '/' . self::readableFilename($path, $label),
                'storagePath' => $path,
            ];
        };

        if (method_exists($model, 'documentos')) {
            foreach ($model->documentos ?? [] as $doc) {
                $tipo = $doc->tipo_documento_adjunto ?? $doc->nombre ?? '';
                $push(self::mapDocumentoTipo($tipo), $doc->file_path ?? null, $doc->nombre ?? $tipo);
            }
        }

        $push(self::TYPE_CONTRATOS, $model->archivo_contrato ?? $model->contrato_archivo ?? $model->contrato ?? null, 'contrato');
        $push(self::TYPE_RESOLUCIONES, $model->archivo_conformidad_servicio ?? $model->conformidad_tecnica ?? $model->resolucion_archivo ?? $model->actas_resoluciones ?? null, 'resolucion');
        $push(self::TYPE_COMPROBANTES, $model->archivo_comprobante_pago ?? null, 'comprobante');
        $push(self::TYPE_SUSPENSION, $model->archivo_suspension ?? $model->archivo_acta_suspension ?? null, 'suspension');
        $push(self::TYPE_REINICIO, $model->archivo_reinicio ?? $model->archivo_acta_reinicio ?? null, 'reinicio');
        $push(self::TYPE_TDR, $model->tdr_archivo ?? null, 'tdr');
        $push(self::TYPE_OTROS, $model->documento ?? null, 'documento');
        $push(self::TYPE_RESOLUCIONES, $model->archivo_acta_recepcion ?? null, 'acta-recepcion');
        $push(self::TYPE_RESOLUCIONES, $model->archivo_acta_inicio ?? null, 'acta-inicio');
        $push(self::TYPE_RESOLUCIONES, $model->archivo_acta_entrega_terreno ?? null, 'acta-entrega-terreno');
        $push(self::TYPE_OTROS, $model->archivo_acta_adicional ?? null, 'acta-adicional');
        $push(self::TYPE_OTROS, $model->archivo_acta_deductivo ?? null, 'acta-deductivo');
        $push(self::TYPE_RESOLUCIONES, $model->archivo_aprobacion_acto_resolutivo ?? null, 'acto-resolutivo');

        if (method_exists($model, 'documentosLiquidacion')) {
            foreach ($model->documentosLiquidacion ?? [] as $doc) {
                $push(self::TYPE_OTROS, $doc->file_path ?? null, $doc->nombre ?? 'liquidacion');
            }
        }

        if (is_array($model->producto_tecnico ?? null)) {
            foreach ($model->producto_tecnico as $i => $path) {
                $push(self::TYPE_PRODUCTOS, $path, 'producto-' . ($i + 1));
            }
        }

        return $out;
    }

    /**
     * @param  array<int, string>  $used
     */
    protected static function uniqueZipPath(string $zipPath, array &$used): string
    {
        $zipPath = str_replace('\\', '/', $zipPath);
        $zipPath = preg_replace('#/+#', '/', $zipPath) ?? $zipPath;
        $dir = trim(dirname($zipPath), '.');
        $base = pathinfo($zipPath, PATHINFO_FILENAME);
        $ext = pathinfo($zipPath, PATHINFO_EXTENSION);
        $n = 1;
        $candidate = $zipPath;
        while (isset($used[$candidate])) {
            $n++;
            $suffix = $base . '-' . $n . ($ext !== '' ? '.' . $ext : '');
            $candidate = ($dir && $dir !== '/') ? $dir . '/' . $suffix : $suffix;
        }
        $used[$candidate] = true;

        return $candidate;
    }
}
