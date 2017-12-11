<?php

namespace YaMoney\Model\PaymentData;

use YaMoney\Common\AbstractObject;
use YaMoney\Common\Exceptions\EmptyPropertyValueException;
use YaMoney\Common\Exceptions\InvalidPropertyValueException;
use YaMoney\Common\Exceptions\InvalidPropertyValueTypeException;
use YaMoney\Helpers\TypeCast;

/**
 * Данные банковской карты
 * Необходим при оплате PCI-DSS данными.
 * @property string $number Номер банковской карты
 * @property string $expiryYear Срок действия, год, YY
 * @property string $expiryMonth Срок действия, месяц, MM
 * @property string $csc CVV2/CVC2 код
 * @property string $cardholder Имя держателя карты
 */
class PaymentDataBankCardCard extends AbstractObject
{
    /**
     * @var string Номер банковской карты
     */
    private $_number;

    /**
     * @var string Срок действия, год, YY
     */
    private $_expiryYear;

    /**
     * @var string Срок действия, месяц, MM
     */
    private $_expiryMonth;

    /**
     * @var string CVV2/CVC2 код
     */
    private $_csc;

    /**
     * @var string Имя держателя карты
     */
    private $_cardholder;

    /**
     * @return string Номер банковской карты
     */
    public function getNumber()
    {
        return $this->_number;
    }

    /**
     * @param string $value Номер банковской карты
     */
    public function setNumber($value)
    {
        if ($value === null || $value === '') {
            throw new EmptyPropertyValueException('Empty card number value', 0, 'PaymentDataBankCardCard.number');
        } elseif (TypeCast::canCastToString($value)) {
            if (preg_match('/^[0-9]{16,19}$/', (string)$value)) {
                $this->_number = (string)$value;
            } else {
                throw new InvalidPropertyValueException(
                    'Invalid card number value', 0, 'PaymentDataBankCardCard.number', $value
                );
            }
        } else {
            throw new InvalidPropertyValueTypeException(
                'Invalid card number value type', 0, 'PaymentDataBankCardCard.number', $value
            );
        }
    }

    /**
     * @return string Срок действия, год, YYYY
     */
    public function getExpiryYear()
    {
        return $this->_expiryYear;
    }

    /**
     * @param string $value Срок действия, год, YYYY
     */
    public function setExpiryYear($value)
    {
        if ($value === null || $value === '') {
            throw new EmptyPropertyValueException(
                'Empty card expiry year value', 0, 'PaymentDataBankCardCard.expiryYear'
            );
        } elseif (is_numeric($value)) {
            if (!preg_match('/^\d\d\d\d$/', $value) || $value < 2000 || $value > 2200) {
                throw new InvalidPropertyValueException(
                    'Invalid card expiry year value', 0, 'PaymentDataBankCardCard.expiryYear', $value
                );
            }
            $this->_expiryYear = (string)$value;
        } else {
            throw new InvalidPropertyValueException(
                'Invalid card expiry year value', 0, 'PaymentDataBankCardCard.expiryYear', $value
            );
        }
    }

    /**
     * @return string Срок действия, месяц, MM
     */
    public function getExpiryMonth()
    {
        return $this->_expiryMonth;
    }

    /**
     * @param string $value Срок действия, месяц, MM
     */
    public function setExpiryMonth($value)
    {
        if ($value === null || $value === '') {
            throw new EmptyPropertyValueException(
                'Empty card expiry month value', 0, 'PaymentDataBankCardCard.expiryMonth'
            );
        } elseif (is_numeric($value)) {
            if (!preg_match('/^\d\d$/', $value)) {
                throw new InvalidPropertyValueException(
                    'Invalid card expiry month value', 0, 'PaymentDataBankCardCard.expiryMonth', $value
                );
            }
            if (is_string($value) && $value[0] == '0') {
                $month = (int)($value[1]);
            } else {
                $month = (int)$value;
            }
            if ($month < 1 || $month > 12) {
                throw new InvalidPropertyValueException(
                    'Invalid card expiry month value', 0, 'PaymentDataBankCardCard.expiryMonth', $value
                );
            } else {
                $this->_expiryMonth = (string)$value;
            }
        } else {
            throw new InvalidPropertyValueException(
                'Invalid card expiry month value', 0, 'PaymentDataBankCardCard.expiryMonth', $value
            );
        }
    }

    /**
     * @return string CVV2/CVC2 код
     */
    public function getCsc()
    {
        return $this->_csc;
    }

    /**
     * @param string $value CVV2/CVC2 код
     */
    public function setCsc($value)
    {
        if ($value === null || $value === '') {
            throw new EmptyPropertyValueException(
                'Empty card CSC code value', 0, 'PaymentDataBankCardCard.csc'
            );
        } elseif (is_numeric($value)) {
            if (preg_match('/^\d{3,4}$/', $value)) {
                $this->_csc = (string)$value;
            } else {
                throw new InvalidPropertyValueException(
                    'Invalid card CSC code value', 0, 'PaymentDataBankCardCard.csc', $value
                );
            }
        } else {
            throw new InvalidPropertyValueException(
                'Invalid card CSC code value', 0, 'PaymentDataBankCardCard.csc', $value
            );
        }
    }

    /**
     * @return string Имя держателя карты
     */
    public function getCardholder()
    {
        return $this->_cardholder;
    }

    /**
     * @param string $value Имя держателя карты
     */
    public function setCardholder($value)
    {
        if ($value === null || $value === '') {
            throw new EmptyPropertyValueException(
                'Empty card holder value', 0, 'PaymentDataBankCardCard.cardholder'
            );
        } elseif (TypeCast::canCastToString($value)) {
            if (preg_match('/^[a-zA-Z\s]{1,26}$/', $value)) {
                $this->_cardholder = (string)$value;
            } else {
                throw new InvalidPropertyValueException(
                    'Invalid card holder value', 0, 'PaymentDataBankCardCard.cardholder', $value
                );
            }
        } else {
            throw new InvalidPropertyValueException(
                'Invalid card holder value', 0, 'PaymentDataBankCardCard.cardholder', $value
            );
        }
    }
}
