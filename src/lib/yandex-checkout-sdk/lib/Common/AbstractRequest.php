<?php

namespace YaMoney\Common;

/**
 * Базовый класс объекта запроса, передаваемого в методы клиента API
 *
 * @package YaMoney\Common
 */
abstract class AbstractRequest extends AbstractObject
{
    /**
     * @var string Последняя ошибка валидации текущего запроса
     */
    private $_validationError;

    /**
     * Валидирует текущий запрос, проверяет все ли нужные свойства установлены
     * @return bool True если запрос валиден, false если нет
     */
    abstract public function validate();

    /**
     * Очищает статус валидации текущего запроса
     */
    public function clearValidationError()
    {
        $this->_validationError = null;
    }

    /**
     * Устанавливает ошибку валидации
     * @param string $value Ошибка, произошедшая при валидации объекта
     */
    protected function setValidationError($value)
    {
        $this->_validationError = $value;
    }

    /**
     * Возвращает последнюю ошибку валидации
     * @return string Последняя произошедшая ошибка валидации
     */
    public function getLastValidationError()
    {
        return $this->_validationError;
    }
}
