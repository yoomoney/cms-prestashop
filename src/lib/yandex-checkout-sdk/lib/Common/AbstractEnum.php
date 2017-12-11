<?php

namespace YaMoney\Common;

/**
 * Базовый класс генерируемых enum'ов
 *
 * @package YaMoney\Common
 */
abstract class AbstractEnum
{
    /**
     * @var array Массив принимаемых enum'ом значений
     */
    protected static $validValues = array();

    /**
     * Проверяет наличие значения в enum'e
     * @param mixed $value Проверяемое значение
     * @return bool True если значение имеется, false если нет
     */
    public static function valueExists($value)
    {
        return array_key_exists($value, static::$validValues);
    }

    /**
     * Возвращает все значения в enum'e
     * @return array Массив значений в перечислении
     */
    public static function getValidValues()
    {
        return array_keys(static::$validValues);
    }

    /**
     * Возвращает значения в enum'е значения которых разрешены
     * @return string[] Массив разрешённых значений
     */
    public static function getEnabledValues()
    {
        $result = array();
        foreach (static::$validValues as $key => $enabled) {
            if ($enabled) {
                $result[] = $key;
            }
        }
        return $result;
    }
}
