<?php

namespace YaMoney\Model\PaymentData;

use YaMoney\Model\PaymentMethodType;

/**
 * PaymentDataWebmoney
 * Платежные данные для проведения оплаты Webmoney.
 */
class PaymentDataWebmoney extends AbstractPaymentData
{
    public function __construct()
    {
        $this->_setType(PaymentMethodType::WEBMONEY);
    }
}
