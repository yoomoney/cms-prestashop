<?php

namespace YaMoney\Model\PaymentMethod;

use YaMoney\Common\AbstractEnum;

class PaymentMethodCardType extends AbstractEnum
{
    const MASTER_CARD = 'MasterCard';
    const VISA = 'Visa';
    const MIR = 'Mir';
    const UNION_PAY = 'UnionPay';
    const JCB = 'JCB';
    const AMERICAN_EXPRESS = 'AmericanExpress';
    const UNKNOWN = 'Unknown';

    protected static $validValues = array(
        self::MASTER_CARD => true,
        self::VISA => true,
        self::MIR => true,
        self::UNION_PAY => true,
        self::JCB => true,
        self::AMERICAN_EXPRESS => true,
        self::UNKNOWN => true,
    );
}