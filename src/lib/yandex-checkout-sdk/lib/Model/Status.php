<?php

namespace YaMoney\Model;

use YaMoney\Common\AbstractEnum;

/**
 * Статус платежа
 */
class Status extends AbstractEnum
{
    const SUCCEEDED = 'succeeded';
    const PENDING = 'pending';
    const CANCELED = 'canceled';

    protected static $validValues = array(
        self::SUCCEEDED => true,
        self::PENDING => true,
        self::CANCELED => true,
    );
}
