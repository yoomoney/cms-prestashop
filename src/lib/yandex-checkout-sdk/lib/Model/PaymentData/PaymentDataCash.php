<?php

namespace YaMoney\Model\PaymentData;

use YaMoney\Common\Exceptions\InvalidPropertyValueException;
use YaMoney\Common\Exceptions\InvalidPropertyValueTypeException;
use YaMoney\Helpers\TypeCast;
use YaMoney\Model\PaymentMethodType;

/**
 * PaymentDataCash
 * Платежные данные для проведения оплаты Qiwi.
 * @property string $phone
 */
class PaymentDataCash extends AbstractPaymentData
{
    /**
     * Номер телефона в формате ITU-T E.164 на который будет отправлена информация для оплаты.
     * @var string
     */
    private $_phone;

    public function __construct()
    {
        $this->_setType(PaymentMethodType::CASH);
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->_phone;
    }

    /**
     * @param string $value
     */
    public function setPhone($value)
    {
        if ($value === null || $value === '') {
            $this->_phone = null;
        } elseif (TypeCast::canCastToString($value)) {
            if (preg_match('/^[0-9]{4,15}$/', $value)) {
                $this->_phone = (string)$value;
            } else {
                throw new InvalidPropertyValueException(
                    'Invalid phone value', 0, 'PaymentDataCash.phone', $value
                );
            }
        } else {
            throw new InvalidPropertyValueTypeException(
                'Invalid phone value type', 0, 'PaymentDataCash.phone', $value
            );
        }
    }
}
