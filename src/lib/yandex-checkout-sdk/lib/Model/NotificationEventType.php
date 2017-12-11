<?php

namespace YaMoney\Model;

use YaMoney\Common\AbstractEnum;

class NotificationEventType extends AbstractEnum
{
    const PAYMENT_WAITING_FOR_CAPTURE = 'payment.waiting_for_capture';

    protected static $validValues = array(
        self::PAYMENT_WAITING_FOR_CAPTURE => true,
    );
}