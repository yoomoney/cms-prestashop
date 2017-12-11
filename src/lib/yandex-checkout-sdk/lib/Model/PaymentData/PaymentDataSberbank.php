<?php

namespace YaMoney\Model\PaymentData;

use YaMoney\Common\Exceptions\EmptyPropertyValueException;
use YaMoney\Common\Exceptions\InvalidPropertyValueException;
use YaMoney\Common\Exceptions\InvalidPropertyValueTypeException;
use YaMoney\Helpers\TypeCast;
use YaMoney\Model\PaymentMethodType;

/**
 * PaymentDataSberbank
 * Платежные данные для проведения оплаты при помощи Сбербанк Онлайн.
 * @property string $phone
 * @property string $bindId Идентификатор привязки клиента СБОЛ.
 */
class PaymentDataSberbank extends AbstractPaymentData
{
    /**
     * Номер телефона в формате ITU-T E.164 на который зарегистрирован аккаунт в Сбербанк Онлайн. Необходим для оплаты `waiting` сценарием
     * @var string
     */
    private $_phone;

    /**
     * Необходим для безакцептной оплаты привязкой созданной через deep link приложения Сбербанк Онлайн.
     * @var string Идентификатор привязки клиента СБОЛ.
     */
    private $_bindId;

    public function __construct()
    {
        $this->_setType(PaymentMethodType::SBERBANK);
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->_phone;
    }

    /**
     * @param string $value
     */
    public function setPhone($value)
    {
        if ($value === null || $value === '') {
            throw new EmptyPropertyValueException('Empty phone value', 0, 'PaymentDataSberbank.phone');
        } elseif (TypeCast::canCastToString($value)) {
            if (preg_match('/^[0-9]{4,15}$/', $value)) {
                $this->_phone = (string)$value;
            } else {
                throw new InvalidPropertyValueException(
                    'Invalid phone value', 0, 'PaymentDataSberbank.phone', $value
                );
            }
        } else {
            throw new InvalidPropertyValueTypeException(
                'Invalid phone value type', 0, 'PaymentDataSberbank.phone', $value
            );
        }
    }

    /**
     * @return string Идентификатор привязки клиента СБОЛ.
     */
    public function getBindId()
    {
        return $this->_bindId;
    }

    /**
     * @param string $value Идентификатор привязки клиента СБОЛ.
     */
    public function setBindId($value)
    {
        if ($value === null || $value === '') {
            throw new EmptyPropertyValueException('Empty bindId value', 0, 'PaymentDataSberbank.bindId');
        } elseif (TypeCast::canCastToString($value)) {
            $this->_bindId = (string)$value;
        } else {
            throw new InvalidPropertyValueTypeException(
                'Invalid bindId value type', 0, 'PaymentDataSberbank.bindId', $value
            );
        }
    }
}
