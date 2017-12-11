<?php

namespace YaMoney\Model\PaymentMethod;
use YaMoney\Common\Exceptions\EmptyPropertyValueException;
use YaMoney\Common\Exceptions\InvalidPropertyValueException;
use YaMoney\Common\Exceptions\InvalidPropertyValueTypeException;
use YaMoney\Helpers\TypeCast;
use YaMoney\Model\PaymentMethodType;

/**
 * PaymentMethodYandexWallet
 * Объект, описывающий метод оплаты, при оплате через Яндекс Деньги
 * @property string $type Тип объекта
 * @property string $phone Номер телефона в формате ITU-T E.164 с которого была произведена оплата.
 * @property string $accountNumber Номер кошелька в Яндекс.Деньгах с которого была произведена оплата.
 */
class PaymentMethodYandexWallet extends AbstractPaymentMethod
{
    /**
     * @var string Номер телефона в формате ITU-T E.164 с которого была произведена оплата.
     */
    private $_phone;

    /**
     * @var string Номер кошелька в Яндекс.Деньгах с которого была произведена оплата.
     */
    private $_accountNumber;

    public function __construct()
    {
        $this->_setType(PaymentMethodType::YANDEX_MONEY);
    }

    /**
     * @return string Номер телефона в формате ITU-T E.164 с которого была произведена оплата.
     */
    public function getPhone()
    {
        return $this->_phone;
    }

    /**
     * @param string $value Номер телефона в формате ITU-T E.164 с которого была произведена оплата.
     */
    public function setPhone($value)
    {
        if ($value === null || $value === '') {
            throw new EmptyPropertyValueException('Empty phone value', 0, 'PaymentMethodYandexWallet.phone');
        } elseif (TypeCast::canCastToString($value)) {
            if (preg_match('/^[0-9]{4,15}$/', $value)) {
                $this->_phone = (string)$value;
            } else {
                throw new InvalidPropertyValueException(
                    'Invalid phone value', 0, 'PaymentMethodYandexWallet.phone', $value
                );
            }
        } else {
            throw new InvalidPropertyValueTypeException(
                'Invalid phone value type', 0, 'PaymentMethodYandexWallet.phone', $value
            );
        }
    }

    /**
     * @return string Номер кошелька в Яндекс.Деньгах с которого была произведена оплата.
     */
    public function getAccountNumber()
    {
        return $this->_accountNumber;
    }

    /**
     * @param string $value Номер кошелька в Яндекс.Деньгах с которого была произведена оплата.
     */
    public function setAccountNumber($value)
    {
        if ($value === null || $value === '') {
            throw new EmptyPropertyValueException(
                'Empty accountNumber value', 0, 'PaymentMethodYandexWallet.accountNumber'
            );
        } elseif (TypeCast::canCastToString($value)) {
            if (preg_match('/^[0-9]{11,33}$/', $value)) {
                $this->_accountNumber = (string)$value;
            } else {
                throw new InvalidPropertyValueException(
                    'Invalid accountNumber value', 0, 'PaymentMethodYandexWallet.accountNumber', $value
                );
            }
        } else {
            throw new InvalidPropertyValueTypeException(
                'Invalid accountNumber value type', 0, 'PaymentMethodYandexWallet.accountNumber', $value
            );
        }
    }
}
