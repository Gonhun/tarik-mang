<?php

namespace App\Support;

/**
 * Pola LIKE "diawali teks" yang aman: escape karakter khusus LIKE (%, _, dan [ untuk SQL Server).
 * Contoh: SqlLike::prefix('[Kehilangan]') -> '[[]Kehilangan]%'
 */
class SqlLike
{
    public static function escape(string $text): string
    {
        return str_replace(['[', '%', '_'], ['[[]', '[%]', '[_]'], $text);
    }

    public static function prefix(string $text): string
    {
        return self::escape($text) . '%';
    }
}
