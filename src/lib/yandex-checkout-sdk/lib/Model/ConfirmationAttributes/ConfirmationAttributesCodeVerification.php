<?php

namespace YaMoney\Model\ConfirmationAttributes;

use YaMoney\Model\ConfirmationType;

/**
 * Сценарий при котором необходимо получить одноразовый код от плательщика для подтверждения платежа
 *
 * @package YaMoney\Model\ConfirmationAttributes
 */
class ConfirmationAttributesCodeVerification extends AbstractConfirmationAttributes
{
    public function __construct()
    {
        $this->_setType(ConfirmationType::CODE_VERIFICATION);
    }
}
