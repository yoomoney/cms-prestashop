<?php

namespace YaMoney\Model\PaymentData;

use YaMoney\Common\Exceptions\EmptyPropertyValueException;
use YaMoney\Common\Exceptions\InvalidPropertyValueTypeException;
use YaMoney\Helpers\TypeCast;
use YaMoney\Model\PaymentMethodType;

/**
 * PaymentDataAlfabank
 * Платежные данные для проведения оплаты через Альфа Клик или Альфа Молнию.
 * @property string $login Имя пользователя в Альфа-Клике
 */
class PaymentDataAlfabank extends AbstractPaymentData
{
    /**
     * @var string Имя пользователя в Альфа-Клике
     */
    private $_login;

    public function __construct()
    {
        $this->_setType(PaymentMethodType::ALFABANK);
    }

    /**
     * @return string Имя пользователя в Альфа-Клике
     */
    public function getLogin()
    {
        return $this->_login;
    }

    /**
     * @param string $value Имя пользователя в Альфа-Клике
     */
    public function setLogin($value)
    {
        if ($value === null || $value === '') {
            throw new EmptyPropertyValueException('Empty login value', 0, 'PaymentDataAlfabank.login');
        } elseif (TypeCast::canCastToString($value)) {
            $this->_login = (string)$value;
        } else {
            throw new InvalidPropertyValueTypeException(
                'Invalid login value type', 0, 'PaymentDataAlfabank.login', $value
            );
        }
    }
}
