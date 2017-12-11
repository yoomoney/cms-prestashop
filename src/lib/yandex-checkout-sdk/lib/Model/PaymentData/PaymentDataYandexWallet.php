<?php

namespace YaMoney\Model\PaymentData;

use YaMoney\Common\Exceptions\EmptyPropertyValueException;
use YaMoney\Common\Exceptions\InvalidPropertyValueException;
use YaMoney\Common\Exceptions\InvalidPropertyValueTypeException;
use YaMoney\Helpers\TypeCast;
use YaMoney\Model\PaymentMethodType;

/**
 * PaymentDataYandexWallet
 * Платежные данные для проведения оплаты при помощи Яндекс Денег
 * @property string $phone Номер телефона в формате ITU-T E.164 на который зарегестрирован аккаунт в Яндекс Денгах. Необходим для оплаты `waiting` сценарием
 * @property string $accountNumber Номер кошелька в Яндекс.Деньгах, из которого спишутся деньги при оплате. Необходим для оплаты `waiting` сценарием
 */
class PaymentDataYandexWallet extends AbstractPaymentData
{
    /**
     * @var string Номер телефона в формате ITU-T E.164 на который зарегестрирован аккаунт в Яндекс Денгах. Необходим для оплаты `waiting` сценарием
     */
    private $_phone;

    /**
     * @var string Номер кошелька в Яндекс.Деньгах, из которого спишутся деньги при оплате. Необходим для оплаты `waiting` сценарием
     */
    private $_accountNumber;

    public function __construct()
    {
        $this->_setType(PaymentMethodType::YANDEX_MONEY);
    }

    /**
     * @return string Номер телефона в формате ITU-T E.164 на который зарегестрирован аккаунт в Яндекс Денгах. Необходим для оплаты `waiting` сценарием
     */
    public function getPhone()
    {
        return $this->_phone;
    }

    /**
     * @param string $value Номер телефона в формате ITU-T E.164 на который зарегестрирован аккаунт в Яндекс Денгах. Необходим для оплаты `waiting` сценарием
     */
    public function setPhone($value)
    {
        if ($value === null || $value === '') {
            throw new EmptyPropertyValueException('Empty phone value', 0, 'PaymentDataYandexWallet.phone');
        } elseif (TypeCast::canCastToString($value)) {
            if (preg_match('/^[0-9]{4,15}$/', $value)) {
                $this->_phone = (string)$value;
            } else {
                throw new InvalidPropertyValueException(
                    'Invalid phone value', 0, 'PaymentDataYandexWallet.phone', $value
                );
            }
        } else {
            throw new InvalidPropertyValueTypeException(
                'Invalid phone value type', 0, 'PaymentDataYandexWallet.phone', $value
            );
        }
    }

    /**
     * @return string Номер кошелька в Яндекс.Деньгах, из которого спишутся деньги при оплате. Необходим для оплаты `waiting` сценарием
     */
    public function getAccountNumber()
    {
        return $this->_accountNumber;
    }

    /**
     * @param string $value Номер кошелька в Яндекс.Деньгах, из которого спишутся деньги при оплате. Необходим для оплаты `waiting` сценарием
     */
    public function setAccountNumber($value)
    {
        if ($value === null || $value === '') {
            throw new EmptyPropertyValueException(
                'Empty accountNumber value', 0, 'PaymentDataYandexWallet.accountNumber'
            );
        } elseif (TypeCast::canCastToString($value)) {
            if (preg_match('/^[0-9]{11,33}$/', $value)) {
                $this->_accountNumber = (string)$value;
            } else {
                throw new InvalidPropertyValueException(
                    'Invalid accountNumber value', 0, 'PaymentDataYandexWallet.accountNumber', $value
                );
            }
        } else {
            throw new InvalidPropertyValueTypeException(
                'Invalid accountNumber value type', 0, 'PaymentDataYandexWallet.accountNumber', $value
            );
        }
    }
}
