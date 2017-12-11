<?php

namespace YaMoney\Model\ConfirmationAttributes;

use YaMoney\Model\ConfirmationType;

/**
 * Сценарий при котором необходимо ожидать пока пользователь самостоятельно подтвердит платеж. Например,
 * пользователь подтверждает платеж ответом на SMS или в приложении партнера
 * @package YaMoney\Model\ConfirmationAttributes
 */
class ConfirmationAttributesExternal extends AbstractConfirmationAttributes
{
    public function __construct()
    {
        $this->_setType(ConfirmationType::EXTERNAL);
    }
}