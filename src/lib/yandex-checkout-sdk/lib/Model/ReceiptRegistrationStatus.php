<?php

namespace YaMoney\Model;

use YaMoney\Common\AbstractEnum;

/**
 * ReceiptRegistrationStatus - Состояние регистрации фискального чека
 * |Код|Описание|
 * --- | ---
 * |pending|Чек ожидает доставки|
 * |succeeded|Успешно доставлен|
 * |canceled|Чек не доставлен|
 * 
 */
class ReceiptRegistrationStatus extends AbstractEnum
{
    const PENDING = 'pending';
    const SUCCEEDED = 'succeeded';
    const CANCELED = 'canceled';

    protected static $validValues = array(
        self::PENDING => true,
        self::SUCCEEDED => true,
        self::CANCELED => true,
    );
}
