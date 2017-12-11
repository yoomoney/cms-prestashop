<?php

namespace YaMoney\Model;

use YaMoney\Common\AbstractEnum;

/**
 * PaymentMethodType - Тип источника средств для проведения платежа
 * |Код|Описание|
 * --- | ---
 * |yandex_money|Платеж из кошелька Яндекс.Деньги|
 * |bank_card|Платеж с произвольной банковской карты|
 * |sberbank|Платеж СбербанкОнлайн|
 * |cash|Платеж наличными|
 * |mobile_balance|Платеж с баланса мобильного телефона|
 * |apple_pay|Платеж ApplePay|
 * |android_pay|Платеж AndroidPay|
 * |qiwi|Платеж из кошелька Qiwi|
 * 
 */
class PaymentMethodType extends AbstractEnum
{
    const YANDEX_MONEY = 'yandex_money';
    const BANK_CARD = 'bank_card';
    const SBERBANK = 'sberbank';
    const CASH = 'cash';
    const MOBILE_BALANCE = 'mobile_balance';
    const APPLE_PAY = 'apple_pay';
    const ANDROID_PAY = 'android_pay';
    const QIWI = 'qiwi';
    const WEBMONEY = 'webmoney';
    const ALFABANK = 'alfabank';

    protected static $validValues = array(
        self::YANDEX_MONEY => true,
        self::BANK_CARD => true,
        self::SBERBANK => true,
        self::CASH => true,
        self::MOBILE_BALANCE => false,
        self::APPLE_PAY => false,
        self::ANDROID_PAY => false,
        self::QIWI => true,
        self::ALFABANK => true,
        self::WEBMONEY => true,
    );
}
