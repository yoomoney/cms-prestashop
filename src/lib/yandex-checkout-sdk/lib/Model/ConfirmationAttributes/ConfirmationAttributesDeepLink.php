<?php

namespace YaMoney\Model\ConfirmationAttributes;

use YaMoney\Model\ConfirmationType;

/**
 * Сценарий при котором необходимо направить плательщика в приложение партнера
 *
 * @package YaMoney\Model\ConfirmationAttributes
 */
class ConfirmationAttributesDeepLink extends AbstractConfirmationAttributes
{
    public function __construct()
    {
        $this->_setType(ConfirmationType::DEEPLINK);
    }
}