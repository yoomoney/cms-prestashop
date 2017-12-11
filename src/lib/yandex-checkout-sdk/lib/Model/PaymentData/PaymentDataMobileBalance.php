<?php

namespace YaMoney\Model\PaymentData;

use YaMoney\Common\Exceptions\EmptyPropertyValueException;
use YaMoney\Common\Exceptions\InvalidPropertyValueException;
use YaMoney\Common\Exceptions\InvalidPropertyValueTypeException;
use YaMoney\Helpers\TypeCast;
use YaMoney\Model\PaymentMethodType;

/**
 * PaymentDataMobileBalance
 * Платежные данные для проведения оплаты Qiwi.
 * @property string $phone
 */
class PaymentDataMobileBalance extends AbstractPaymentData
{
    /**
     * Номер телефона в формате ITU-T E.164 с которого плательщик собирается произвести оплату.
     * @var string
     */
    private $_phone;

    public function __construct()
    {
        $this->_setType(PaymentMethodType::MOBILE_BALANCE);
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
            throw new EmptyPropertyValueException('Empty phone value', 0, 'PaymentDataMobileBalance.phone');
        } elseif (TypeCast::canCastToString($value)) {
            if (preg_match('/^[0-9]{4,15}$/', $value)) {
                $this->_phone = (string)$value;
            } else {
                throw new InvalidPropertyValueException(
                    'Invalid phone value', 0, 'PaymentDataMobileBalance.phone', $value
                );
            }
        } else {
            throw new InvalidPropertyValueTypeException(
                'Invalid phone value type', 0, 'PaymentDataMobileBalance.phone', $value
            );
        }
    }
}
