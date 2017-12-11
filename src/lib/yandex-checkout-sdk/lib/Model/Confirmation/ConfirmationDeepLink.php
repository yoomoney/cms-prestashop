<?php

namespace YaMoney\Model\Confirmation;

use YaMoney\Model\ConfirmationType;

/**
 * Сценарий при котором необходимо направить плательщика в приложение партнера
 *
 * @package YaMoney\Model\Confirmation
 */
class ConfirmationDeepLink extends AbstractConfirmation
{
    public function __construct()
    {
        $this->_setType(ConfirmationType::DEEPLINK);
    }
}