<?php

namespace YaMoney\Model\PaymentMethod;
use YaMoney\Model\PaymentMethodType;

/**
 * PaymentMethodCash
 * Объект, описывающий метод оплаты, при оплате наличными через терминал.
 * @property string $type Тип объекта
 */
class PaymentMethodCash extends AbstractPaymentMethod
{
    public function __construct()
    {
        $this->_setType(PaymentMethodType::CASH);
    }
}
