<?php

namespace YaMoney\Helpers;

class TypeCast
{
    /**
     * @param mixed $value
     * @return bool
     */
    public static function canCastToString($value)
    {
        if (is_scalar($value)) {
            return !is_bool($value) && !is_resource($value);
        } elseif (is_object($value)) {
            return method_exists($value, '__toString');
        }
        return false;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public static function canCastToEnumString($value)
    {
        if (is_string($value) && $value !== '') {
            return true;
        } elseif (is_object($value)) {
            return method_exists($value, '__toString');
        }
        return false;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public static function canCastToDateTime($value)
    {
        if ($value instanceof \DateTime) {
            return true;
        } elseif (is_numeric($value)) {
            $value = (float)$value;
            return $value >= 0;
        } elseif (is_string($value)) {
            return $value !== '';
        } elseif (is_object($value)) {
            return method_exists($value, '__toString') && ((string)$value) !== '';
        }
        return false;
    }

    /**
     * @param string|int|\DateTime $value
     * @return \DateTime|null
     */
    public static function castToDateTime($value)
    {
        if ($value instanceof \DateTime) {
            return clone $value;
        }
        if (is_numeric($value)) {
            $date = new \DateTime();
            $date->setTimestamp((int)$value);
        } elseif (is_string($value) || (is_object($value) && method_exists($value, '__toString'))) {
            $date = date_create((string)$value);
            if ($date === false) {
                $date = null;
            }
        } else {
            $date = null;
        }
        return $date;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public static function canCastToBoolean($value)
    {
        if (is_numeric($value) || is_bool($value)) {
            return true;
        }
        return false;
    }
}