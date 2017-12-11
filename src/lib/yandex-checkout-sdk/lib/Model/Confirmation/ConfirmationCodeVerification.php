<?php

namespace YaMoney\Model\Confirmation;

use YaMoney\Model\ConfirmationType;

/**
 * Сценарий при котором необходимо получить одноразовый код от плательщика для подтверждения платежа
 *
 * @package YaMoney\Model\Confirmation
 */
class ConfirmationCodeVerification extends AbstractConfirmation
{
    public function __construct()
    {
        $this->_setType(ConfirmationType::CODE_VERIFICATION);
    }
}