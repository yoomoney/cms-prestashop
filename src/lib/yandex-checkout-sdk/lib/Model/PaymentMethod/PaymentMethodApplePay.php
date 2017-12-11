<?php

namespace YaMoney\Model\PaymentMethod;
use YaMoney\Model\PaymentMethodType;

/**
 * PaymentMethodApplePay
 * Объект, описывающий метод оплаты, при оплате через Apple Pay
 * @property string $type Тип объекта
 */
class PaymentMethodApplePay extends AbstractPaymentMethod
{
    public function __construct()
    {
        $this->_setType(PaymentMethodType::APPLE_PAY);
    }
}
