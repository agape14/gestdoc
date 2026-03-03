<?php

use Illuminate\Support\Facades\Storage;

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
