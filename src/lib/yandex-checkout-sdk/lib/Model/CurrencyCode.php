<?php

namespace YaMoney\Model;

use YaMoney\Common\AbstractEnum;

/**
 * CurrencyCode - Код валюты, ISO-4217 3-alpha currency symbol
 */
class CurrencyCode extends AbstractEnum
{
    const RUB = 'RUB';
    const USD = 'USD';
    const EUR = 'EUR';

    protected static $validValues = array(
        self::RUB => true,
        self::USD => true,
        self::EUR => true,
    );
}
