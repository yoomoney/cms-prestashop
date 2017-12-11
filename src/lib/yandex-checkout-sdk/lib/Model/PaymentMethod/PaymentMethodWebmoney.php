<?php

namespace YaMoney\Model\PaymentMethod;
use YaMoney\Model\PaymentMethodType;

/**
 * PaymentMethodWebmoney
 * Объект, описывающий метод оплаты, при оплате через Webmoney.
 * @property string $type Тип объекта
 */
class PaymentMethodWebmoney extends AbstractPaymentMethod
{
    public function __construct()
    {
        $this->_setType(PaymentMethodType::WEBMONEY);
    }
}
