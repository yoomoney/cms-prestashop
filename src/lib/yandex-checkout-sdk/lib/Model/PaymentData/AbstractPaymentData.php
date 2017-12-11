<?php

namespace YaMoney\Model\PaymentData;

use YaMoney\Common\AbstractObject;
use YaMoney\Common\Exceptions\EmptyPropertyValueException;
use YaMoney\Common\Exceptions\InvalidPropertyValueException;
use YaMoney\Common\Exceptions\InvalidPropertyValueTypeException;
use YaMoney\Helpers\TypeCast;
use YaMoney\Model\PaymentMethodType;

/**
 * Данные используемые для создания метода оплаты.
 * @property string $type
 */
abstract class AbstractPaymentData extends AbstractObject
{
    /**
     * @var string
     */
    private $_type;

    /**
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * @param string $value
     */
    protected function _setType($value)
    {
        if ($value === null || $value === '') {
            throw new EmptyPropertyValueException(
                'Empty payment data type', 0, 'paymentData.type'
            );
        } elseif (TypeCast::canCastToEnumString($value)) {
            if (PaymentMethodType::valueExists($value)) {
                $this->_type = (string)$value;
            } else {
                throw new InvalidPropertyValueException(
                    'Invalid value for "type" parameter in PaymentData', 0, 'paymentData.type', $value
                );
            }
        } else {
            throw new InvalidPropertyValueTypeException(
                'Invalid value type for "type" parameter in PaymentData', 0, 'paymentData.type', $value
            );
        }
    }
}
