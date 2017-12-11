<?php

namespace YaMoney\Model\PaymentMethod;

use YaMoney\Common\AbstractObject;
use YaMoney\Common\Exceptions\EmptyPropertyValueException;
use YaMoney\Common\Exceptions\InvalidPropertyValueException;
use YaMoney\Common\Exceptions\InvalidPropertyValueTypeException;
use YaMoney\Helpers\TypeCast;
use YaMoney\Model\PaymentMethodType;

/**
 * @property string $id Идентификатор записи о сохраненных платежных данных
 * @property bool $saved Возможность многократного использования
 * @property string $title Название метода оплаты
 */
abstract class AbstractPaymentMethod extends AbstractObject
{
    /**
     * @var string Идентификатор записи о сохраненных платежных данных
     */
    private $_id;

    /**
     * @var string Тип объекта
     */
    private $_type;

    /**
     * @var bool Возможность многократного использования
     */
    private $_saved = false;

    /**
     * @var string Название метода оплаты
     */
    private $_title;

    /**
     * @return string Тип объекта
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * @param string $value Тип объекта
     */
    protected function _setType($value)
    {
        if ($value === null || $value === '') {
            throw new EmptyPropertyValueException(
                'Empty payment data type', 0, 'PaymentMethod.type'
            );
        } elseif (TypeCast::canCastToEnumString($value)) {
            if (PaymentMethodType::valueExists($value)) {
                $this->_type = (string)$value;
            } else {
                throw new InvalidPropertyValueException(
                    'Invalid value for "type" parameter in PaymentMethod', 0, 'PaymentMethod.type', $value
                );
            }
        } else {
            throw new InvalidPropertyValueTypeException(
                'Invalid value type for "type" parameter in PaymentMethod', 0, 'PaymentMethod.type', $value
            );
        }
    }

    /**
     * @return string Идентификатор записи о сохраненных платежных данных
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param string $value Идентификатор записи о сохраненных платежных данных
     */
    public function setId($value)
    {
        if ($value === null || $value === '') {
            $this->_id = null;
        } elseif (TypeCast::canCastToString($value)) {
            $this->_id = (string)$value;
        } else {
            throw new InvalidPropertyValueTypeException('Invalid id value type', 0, 'PaymentMethod.id', $value);
        }
    }

    /**
     * @return bool Возможность многократного использования
     */
    public function getSaved()
    {
        return $this->_saved;
    }

    /**
     * @param bool $value Возможность многократного использования
     */
    public function setSaved($value)
    {
        if ($value === null || $value === '') {
            throw new EmptyPropertyValueException('Empty saved value', 0, 'PaymentMethod.saved');
        } elseif (TypeCast::canCastToBoolean($value)) {
            $this->_saved = (bool)$value;
        } else {
            throw new InvalidPropertyValueTypeException(
                'Invalid saved value type', 0, 'PaymentMethod.saved', $value
            );
        }
    }

    /**
     * @return string Название метода оплаты
     */
    public function getTitle()
    {
        return $this->_title;
    }

    /**
     * @param string $value Название метода оплаты
     */
    public function setTitle($value)
    {
        if ($value === null || $value === '') {
            $this->_title = null;
        } elseif (TypeCast::canCastToString($value)) {
            $this->_title = (string)$value;
        } else {
            throw new InvalidPropertyValueTypeException('Invalid title value type', 0, 'PaymentMethod.title', $value);
        }
    }
}
