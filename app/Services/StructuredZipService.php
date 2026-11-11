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
     * Construye una carpeta única por registro seleccionado.
     *
     * Cuando dos registros generan el mismo nombre de carpeta (por ejemplo, el mismo
     * cliente sin CUI) se numeran como "1. CARPETA", "2. CARPETA", ... para que los
     * documentos no se mezclen entre proyectos.
     *
     * @param  array<int, string>  $names
     * @return array<int, string>
     */
    public static function numberDuplicateFolders(array $names): array
    {
        $counts = array_count_values($names);
        $seen = [];

        foreach ($names as $i => $name) {
            if (($counts[$name] ?? 0) > 1) {
                $seen[$name] = ($seen[$name] ?? 0) + 1;
                $names[$i] = $seen[$name] . '. ' . $name;
            }
        }

        return $names;
    }

    /**
     * Carpeta raíz de un registro (CUI_PROYECTO o nombre del proyecto).
     */
    public static function recordFolder(object $model): string
    {
        return self::projectFolder(
            $model->cui ?? null,
            $model->cliente
                ?? $model->proyecto
                ?? $model->titulo
                ?? $model->objeto_del_contrato
                ?? $model->entidad
                ?? $model->nombre_candidato
                ?? $model->nombre_sigla_entidad
                ?? $model->nomenclatura
                ?? ('registro-' . ($model->id ?? ''))
        );
    }

    /**
     * Extrae las entradas ZIP de varios registros garantizando UNA carpeta por
     * proyecto y sin mezclar archivos entre registros seleccionados.
     *
     * @param  iterable<int, object>  $records
     * @param  array<int, string>  $allowedTypes
     * @return array<int, array{zipPath: string, storagePath: string}>
     */
    public static function entriesFromRecords(iterable $records, array $allowedTypes = []): array
    {
        $list = is_array($records) ? array_values($records) : iterator_to_array($records, false);
        if ($list === []) {
            return [];
        }

        $folders = [];
        foreach ($list as $record) {
            $folders[] = self::recordFolder($record);
        }
        $folders = self::numberDuplicateFolders($folders);

        $entries = [];
        foreach ($list as $i => $record) {
            $entries = array_merge($entries, self::entriesFromRecord($record, $allowedTypes, $folders[$i]));
        }

        return $entries;
    }

    /**
     * Extrae entradas ZIP de un registro de experiencia / consultor / expediente.
     *
     * @param  array<int, string>  $allowedTypes
     * @return array<int, array{zipPath: string, storagePath: string}>
     */
    public static function entriesFromRecord(object $model, array $allowedTypes = [], ?string $project = null): array
    {
        $project = $project ?: self::recordFolder($model);
        $seenPaths = [];

        $out = [];
        $push = function (?string $typeFolder, ?string $path, ?string $label) use (&$out, &$seenPaths, $project, $allowedTypes) {
            if (! is_string($path) || trim($path) === '') {
                return;
            }
            $path = trim($path);
            if (isset($seenPaths[$path]) || preg_match('#^https?://#i', $path)) {
                return;
            }
            $typeFolder = $typeFolder ?: self::TYPE_OTROS;
            if ($allowedTypes && ! in_array($typeFolder, $allowedTypes, true)) {
                return;
            }
            $seenPaths[$path] = true;
            $out[] = [
                'zipPath' => $project . '/' . self::readableFilename($path, $label),
                'storagePath' => $path,
            ];
        };

        // Documentos secundarios / subproyectos vinculados al registro principal.
        foreach (['documentos', 'documentosLiquidacion'] as $relation) {
            if (! method_exists($model, $relation)) {
                continue;
            }
            $relationDefault = $relation === 'documentosLiquidacion' ? 'liquidacion' : 'documento';
            try {
                $documents = $model->{$relation};
            } catch (\Throwable $e) {
                continue;
            }
            foreach ($documents ?? [] as $doc) {
                $tipo = $doc->tipo_documento_adjunto ?? $doc->nombre ?? '';
                $label = $doc->nombre ?: ($tipo ?: $relationDefault);
                $push(self::mapDocumentoTipo($tipo), $doc->file_path ?? null, $label);
            }
        }

        // Listas de archivos (productos técnicos, informes, etc.).
        foreach (['producto_tecnico', 'informes_tecnicos', 'panel_fotografico'] as $field) {
            try {
                $value = $model->{$field} ?? null;
            } catch (\Throwable $e) {
                continue;
            }
            if (is_array($value)) {
                foreach ($value as $i => $path) {
                    $push(
                        $field === 'producto_tecnico' ? self::TYPE_PRODUCTOS : self::TYPE_OTROS,
                        is_string($path) ? $path : null,
                        str_replace('_', '-', $field) . '-' . ($i + 1)
                    );
                }
            }
        }

        // Campos de archivo simples del registro. Se incluyen todos los que
        // empiezan por "archivo_" o terminan en "_archivo", además de columnas
        // históricas con nombres especiales.
        $extraFields = [
            'documento', 'contrato', 'acta_suspension', 'acta_reinicio', 'archivo_cv',
            'personal_clave', 'panel_fotografico', 'promesa_consorcio',
            'resolucion_archivo', 'actas_resoluciones', 'conformidad_tecnica',
        ];
        $attributes = method_exists($model, 'getAttributes') ? $model->getAttributes() : [];

        foreach ($attributes as $key => $value) {
            if (! is_string($value) || trim($value) === '') {
                continue;
            }
            $isFileField = str_starts_with($key, 'archivo_')
                || str_ends_with($key, '_archivo')
                || in_array($key, $extraFields, true);
            if (! $isFileField) {
                continue;
            }

            $push(
                self::mapDocumentoTipo($key),
                $value,
                str_replace('_', ' ', preg_replace('/^archivo_/', '', $key) ?? $key)
            );
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
