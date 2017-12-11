<?php

namespace YaMoney\Model\Confirmation;

use YaMoney\Model\ConfirmationType;

/**
 * Сценарий при котором необходимо ожидать пока пользователь самостоятельно подтвердит платеж. Например,
 * пользователь подтверждает платеж ответом на SMS или в приложении партнера
 *
 * @package YaMoney\Model\Confirmation
 */
class ConfirmationExternal extends AbstractConfirmation
{
    public function __construct()
    {
        $this->_setType(ConfirmationType::EXTERNAL);
    }
}
