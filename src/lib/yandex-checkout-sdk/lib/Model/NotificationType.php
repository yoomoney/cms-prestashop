<?php

namespace YaMoney\Model;

use YaMoney\Common\AbstractEnum;

class NotificationType extends AbstractEnum
{
    const NOTIFICATION = 'notification';

    protected static $validValues = array(
        self::NOTIFICATION => true,
    );
}