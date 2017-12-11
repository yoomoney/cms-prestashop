<?php

namespace YaMoney\Model;

use YaMoney\Common\AbstractEnum;

/**
 * RefundStatus - Состояние возврата платежа
 * |Код|Описание|
 * --- | ---
 * |pending|Ожидает обработки|
 * |succeeded|Успешно возвращен|
 * |canceled|В проведении возврата отказано|
 * 
 */
class RefundStatus extends AbstractEnum
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
