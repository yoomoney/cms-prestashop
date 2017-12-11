<?php

namespace YaMoney\Model;

use YaMoney\Common\AbstractEnum;

/**
 * PaymentStatus - Состояние платежа
 * |Код|Описание|
 * --- | ---
 * |pending|Ожидает оплаты покупателем|
 * |waiting_for_capture|Успешно оплачен покупателем, ожидает подтверждения магазином (capture или aviso)|
 * |succeeded|Успешно оплачен и подтвержден магазином|
 * |canceled|Неуспех оплаты или отменен магазином (cancel)|
 * 
 */
class PaymentStatus extends AbstractEnum
{
    const PENDING = 'pending';
    const WAITING_FOR_CAPTURE = 'waiting_for_capture';
    const SUCCEEDED = 'succeeded';
    const CANCELED = 'canceled';

    protected static $validValues = array(
        self::PENDING => true,
        self::WAITING_FOR_CAPTURE => true,
        self::SUCCEEDED => true,
        self::CANCELED => true,
    );
}
