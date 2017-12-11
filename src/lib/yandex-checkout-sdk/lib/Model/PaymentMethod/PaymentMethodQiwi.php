<?php

namespace YaMoney\Model\PaymentMethod;
use YaMoney\Model\PaymentMethodType;

/**
 * PaymentMethodQiwi
 * Объект, описывающий метод оплаты, при оплате через Qiwi.
 * @property string $type Тип объекта
 */
class PaymentMethodQiwi extends AbstractPaymentMethod
{
    public function __construct()
    {
        $this->_setType(PaymentMethodType::QIWI);
    }
}
