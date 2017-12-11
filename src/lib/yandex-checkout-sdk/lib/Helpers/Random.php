<?php

namespace YaMoney\Helpers;

class Random
{
    public static function int($min = null, $max = null, $useBest = true)
    {
        if ($min === null) {
            $min = 0;
        }
        if ($max === null) {
            $max = PHP_INT_MAX;
        }
        if (function_exists('random_int') && $useBest) {
            return random_int($min, $max);
        } else {
            return mt_rand($min, $max);
        }
    }

    public static function float($min = null, $max = null, $useBest = true)
    {
        $random = self::int(null, null, $useBest) / PHP_INT_MAX;
        if ($min === null) {
            $min = 0.0;
        }
        if ($max === null) {
            return $random + $min;
        }
        return ($random * ($max - $min)) + $min;
    }

    public static function str($length, $maxLength = null, $characters = null, $useBest = true)
    {
        $result = '';
        if ($maxLength !== null) {
             if (is_string($maxLength)) {
                 $characters = $maxLength;
             } else {
                 $length = self::int($length, $maxLength, $useBest);
             }
        }
        if ($characters === null) {
            for ($i = 0; $i < $length; $i++) {
                $chr = chr(self::int(32, 125, $useBest));
                $result .= $chr;
            }
        } else {
            for ($i = 0; $i < $length; $i++) {
                $chr = $characters[self::int(0, strlen($characters) - 1, $useBest)];
                $result .= $chr;
            }
        }
        return $result;
    }

    public static function hex($length, $useBest = true)
    {
        return self::str($length, '0123456789abcdef', $useBest);
    }

    public static function bytes($length, $useBest = true)
    {
        if (function_exists('random_bytes') && $useBest) {
            $result = random_bytes($length);
        } else {
            $result = '';
            for ($i = 0; $i < $length; $i++) {
                $chr = chr(self::int(0, 255));
                $result .= $chr;
            }
        }
        return $result;
    }

    public static function value(array $values, $useBest = true)
    {
        return $values[self::int(0, count($values) - 1, $useBest)];
    }

    public static function bool()
    {
        return self::int(0, 1) === 1;
    }
}
