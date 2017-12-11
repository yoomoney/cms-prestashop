<?php

namespace YaMoney\Model\PaymentData;

use YaMoney\Common\Exceptions\EmptyPropertyValueException;
use YaMoney\Common\Exceptions\InvalidPropertyValueTypeException;
use YaMoney\Helpers\TypeCast;
use YaMoney\Model\PaymentMethodType;

/**
 * PaymentDataAndroidPay
 * Платежные данные для проведения оплаты при помощи Android Pay.
 * @property string $paymentData содержимое поля paymentData объекта PKPaymentToken, закодированное в Base64
 */
class PaymentDataAndroidPay extends AbstractPaymentData
{
    /**
     * @var string содержимое поля paymentData объекта PKPaymentToken, закодированное в Base64
     */
    private $_paymentData;

    public function __construct()
    {
        $this->_setType(PaymentMethodType::ANDROID_PAY);
    }

    /**
     * @return string содержимое поля paymentData объекта PKPaymentToken, закодированное в Base64
     */
    public function getPaymentData()
    {
        return $this->_paymentData;
    }

    /**
     * @param string $value содержимое поля paymentData объекта PKPaymentToken, закодированное в Base64
     */
    public function setPaymentData($value)
    {
        if ($value === null || $value === '') {
            throw new EmptyPropertyValueException(
                'Empty value for paymentData', 0, 'PaymentDataAndroidPay.paymentData'
            );
        } elseif (TypeCast::canCastToString($value)) {
            $this->_paymentData = (string)$value;
        } else {
            throw new InvalidPropertyValueTypeException(
                'Invalid value type for paymentData', 0, 'PaymentDataAndroidPay.paymentData', $value
            );
        }
    }
}
