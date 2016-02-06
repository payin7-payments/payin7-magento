<?php

namespace Payin7Payments;

class Unicode
{
    public static $has_mb;

    public static function strlen($string, $encoding = 'UTF8')
    {
        return self::$has_mb ? mb_strlen($string, $encoding) : strlen($string);
    }

    public static function ucfirst($string, $encoding = 'UTF8')
    {
        if (self::$has_mb) {
            $firstChar = mb_substr($string, 0, 1, $encoding);
            $then = mb_substr($string, 1, null, $encoding);
            return mb_strtoupper($firstChar, $encoding) . $then;
        } else {
            return ucfirst($string);
        }
    }

    public static function strtoupper($string, $encoding = 'UTF8')
    {
        return self::$has_mb ? mb_strtoupper($string, $encoding) : strtoupper($string);
    }

    public static function strtolower($string, $encoding = 'UTF8')
    {
        return self::$has_mb ? mb_strtolower($string, $encoding) : strtolower($string);
    }

    public static function substr($str, $start, $length = null, $encoding = 'UTF8')
    {
        return self::$has_mb ? mb_substr($str, $start, $length, $encoding) : substr($str, $start, $length);
    }
}

Unicode::$has_mb = function_exists('mb_strtolower');
