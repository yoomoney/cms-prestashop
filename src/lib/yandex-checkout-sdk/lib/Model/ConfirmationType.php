<?php

namespace YaMoney\Model;

use YaMoney\Common\AbstractEnum;

/**
 * ConfirmationType - Тип пользовательского процесса подтверждения платежа
 * |Код|Описание|
 * --- | ---
 * |redirect|Необходимо направить плательщика на страницу партнера|
 * |external|Необходимо ождать пока плательщик самостоятельно подтвердит платеж|
 * |deeplink|Необходимо направить плательщика в приложение партнера|
 * |code_verification|Необходимо получить одноразовый код от плательщика для подтверждения платежа|
 * 
 */
class ConfirmationType extends AbstractEnum
{
    const REDIRECT = 'redirect';
    const EXTERNAL = 'external';
    const DEEPLINK = 'deeplink';
    const CODE_VERIFICATION = 'code_verification';

    protected static $validValues = array(
        self::REDIRECT => true,
        self::EXTERNAL => true,
        self::DEEPLINK => false,
        self::CODE_VERIFICATION => false,
    );
}
