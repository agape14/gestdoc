<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Configuration extends Model
{
    protected $fillable = ['key', 'value', 'type', 'description'];

    /**
     * Obtener un valor de configuración por su clave
     */
    public static function get($key, $default = null)
    {
        return Cache::remember("config_{$key}", 3600, function () use ($key, $default) {
            $config = self::where('key', $key)->first();
            return $config ? $config->value : $default;
        });
    }

    /**
     * Establecer un valor de configuración
     */
    public static function set($key, $value, $type = 'text', $description = null)
    {
        $config = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'description' => $description
            ]
        );

        Cache::forget("config_{$key}");
        
        return $config;
    }

    /**
     * Limpiar caché de una configuración
     */
    public static function clearCache($key)
    {
        Cache::forget("config_{$key}");
    }
}
