<?php

namespace App\Support;

class AllowedUploads
{
    public const EXTENSIONS = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'dwg', 'zip', 'rar', 'png', 'jpg', 'jpeg',
    ];

    public const MAX_KB = 25600;

    public static function acceptAttr(): string
    {
        return '.' . implode(',.', self::EXTENSIONS);
    }

    public static function extensionsList(): string
    {
        return implode(',', self::EXTENSIONS);
    }

    /**
     * Regla Laravel: valida extensión (no MIME) para aceptar DWG/RAR/ZIP reales.
     */
    public static function rule(string $prefix = 'nullable'): string
    {
        return $prefix . '|file|max:' . self::MAX_KB . '|extensions:' . self::extensionsList();
    }
}
