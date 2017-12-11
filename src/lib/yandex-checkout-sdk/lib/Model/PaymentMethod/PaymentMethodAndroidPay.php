<?php

namespace YaMoney\Model\PaymentMethod;
use YaMoney\Model\PaymentMethodType;

/**
 * PaymentMethodAndroidPay
 * Объект, описывающий метод оплаты, при оплате через Android Pay
 * @property string $type Тип объекта
 */
class PaymentMethodAndroidPay extends AbstractPaymentMethod
{
    public function __construct()
    {
        $this->_setType(PaymentMethodType::ANDROID_PAY);
    }
}
