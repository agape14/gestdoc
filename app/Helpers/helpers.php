<?php

use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

if (!function_exists('parse_fecha_dd_mm_yyyy')) {
    /**
     * Parsea fecha en formato DD/MM/YYYY a instancia Carbon (Y-m-d).
     * Retorna null si no es válida.
     */
    function parse_fecha_dd_mm_yyyy(?string $value): ?string
    {
        if (!$value || !is_string($value)) {
            return null;
        }
        $trimmed = trim($value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $trimmed)) {
            [$year, $month, $day] = array_map('intval', explode('-', $trimmed));
            if (!checkdate($month, $day, $year)) {
                return null;
            }
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }
        $parts = preg_split('/[\/\-\.]/', $trimmed);
        if (count($parts) !== 3) {
            return null;
        }
        $day = (int) $parts[0];
        $month = (int) $parts[1];
        $year = (int) $parts[2];
        if ($day < 1 || $day > 31 || $month < 1 || $month > 12 || $year < 1900 || $year > 2100) {
            return null;
        }
        try {
            $c = Carbon::createFromDate($year, $month, $day);
            return $c->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}

if (!function_exists('storage_disk_for_path')) {
    /**
     * Devuelve el disco (r2 o public) según el prefijo del path.
     * Paths que empiezan con "expedientes/" están en R2.
     */
    function storage_disk_for_path(?string $path): string
    {
        return $path && str_starts_with($path, 'expedientes/') ? 'r2' : 'public';
    }
}

if (!function_exists('storage_url_for_path')) {
    /**
     * Devuelve la URL del archivo según su path (R2 o local).
     */
    function storage_url_for_path(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        return Storage::disk(\storage_disk_for_path($path))->url($path);
    }
}
