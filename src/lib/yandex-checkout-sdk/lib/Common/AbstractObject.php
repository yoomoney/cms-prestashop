<?php

namespace YaMoney\Common;

/**
 * Базовый класс генерируемых объектов
 *
 * @package YaMoney\Common
 */
abstract class AbstractObject implements \ArrayAccess
{
    /**
     * @var array Свойства установленные пользователем
     */
    private $unknownProperties = array();

    /**
     * Проверяет наличие свойства
     * @param string $offset Имя проверяемого свойства
     * @return bool True если свойство имеется, false если нет
     */
    public function offsetExists($offset)
    {
        $method = 'get' . ucfirst($offset);
        if (method_exists($this, $method)) {
            return true;
        }
        return array_key_exists($offset, $this->unknownProperties);
    }

    /**
     * Возвращает значение свойства
     * @param string $offset Имя свойства
     * @return mixed Значение свойства
     */
    public function offsetGet($offset)
    {
        $method = 'get' . ucfirst($offset);
        if (method_exists($this, $method)) {
            return $this->{$method} ();
        }
        return array_key_exists($offset, $this->unknownProperties) ? $this->unknownProperties[$offset] : null;
    }

    /**
     * Устанавливает значение свойства
     * @param string $offset Имя свойства
     * @param mixed $value Значение свойства
     */
    public function offsetSet($offset, $value)
    {
        $method = 'set' . ucfirst($offset);
        if (method_exists($this, $method)) {
            $this->{$method} ($value);
        } else {
            $this->unknownProperties[$offset] = $value;
        }
    }

    /**
     * Удаляет свойство
     * @param string $offset Имя удаляемого свойства
     */
    public function offsetUnset($offset)
    {
        $method = 'set' . ucfirst($offset);
        if (method_exists($this, $method)) {
            $this->{$method} (null);
        } else {
            unset($this->unknownProperties[$offset]);
        }
    }

    /**
     * Возвращает значение свойства
     * @param string $propertyName Имя свойства
     * @return mixed Значение свойства
     */
    public function __get($propertyName)
    {
        return $this->offsetGet($propertyName);
    }

    /**
     * Устанавливает значение свойства
     * @param string $propertyName Имя свойства
     * @param mixed $value Значение свойства
     */
    public function __set($propertyName, $value)
    {
        $this->offsetSet($propertyName, $value);
    }

    /**
     * Проверяет наличие свойства
     * @param string $propertyName Имя проверяемого свойства
     * @return bool True если свойство имеется, false если нет
     */
    public function __isset($propertyName)
    {
        return $this->offsetExists($propertyName);
    }

    /**
     * Удаляет свойство
     * @param string $propertyName Имя удаляемого свойства
     */
    public function __unset($propertyName)
    {
        $this->offsetUnset($propertyName);
    }

    /**
     * @return array
     */
    protected function getUnknownProperties()
    {
        return $this->unknownProperties;
    }
}
