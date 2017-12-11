<?php

namespace YaMoney\Request;

use YaMoney\Common\AbstractObject;
use YaMoney\Model\AmountInterface;
use YaMoney\Model\ConfirmationType;
use YaMoney\Model\MonetaryAmount;
use YaMoney\Model\PaymentMethodType;

/**
 * Класс способов оплаты, возвращаемых API при запросе возможных способов оплаты
 *
 * @package YaMoney\Request
 *
 * @property-read string $paymentMethodType Тип источника средств для проведения платежа
 * @property-read string[] $confirmationTypes Список возможных сценариев подтверждения платежа
 * @property-read AmountInterface $charge Сумма платежа
 * @property-read AmountInterface $fee Сумма комиссии
 * @property-read bool $extraFee Признак присутствия дополнительной комиссии на стороне партнера
 */
class PaymentOptionsResponseItem extends AbstractObject
{
    /**
     * @var string Тип источника средств для проведения платежа
     */
    private $_paymentMethodType;

    /**
     * @var string[] Список возможных сценариев подтверждения платежа
     */
    private $_confirmationTypes;

    /**
     * @var AmountInterface Сумма платежа
     */
    private $_charge;

    /**
     * @var AmountInterface Сумма дополнительной комиссии при проведении платежа с помощью текущего способа оплаты
     */
    private $_fee;

    /**
     * @var bool Признак присутствия дополнительной комиссии на стороне партнера
     */
    private $_extraFee;

    public function __construct($options)
    {
        $this->_paymentMethodType = $options['payment_method_type'];
        $this->_confirmationTypes = array();
        foreach ($options['confirmation_types'] as $opt) {
            $this->_confirmationTypes[] = $opt;
        }

        $this->_charge = new MonetaryAmount($options['charge']['value'], $options['charge']['currency']);
        $this->_fee = new MonetaryAmount();
        if (!empty($options['fee'])) {
            $this->_fee->setValue($options['fee']['value']);
            $this->_fee->setCurrency($options['fee']['currency']);
        } else {
            $this->_fee->setCurrency($options['charge']['currency']);
        }

        $this->_extraFee = false;
        if (!empty($options['extra_fee'])) {
            $this->_extraFee = (bool)$options['extra_fee'];
        }
    }

    /**
     * Возвращает тип источника средств для проведения платежа
     * @return string Тип источника средств для проведения платежа
     * @see PaymentMethodType
     */
    public function getPaymentMethodType()
    {
        return $this->_paymentMethodType;
    }

    /**
     * Возвращает список возможных сценариев подтверждения платежа
     * @return string[] Список возможных сценариев подтверждения платежа
     * @see ConfirmationType
     */
    public function getConfirmationTypes()
    {
        return $this->_confirmationTypes;
    }

    /**
     * Возвращает сумму платежа
     * @return AmountInterface Сумма платежа
     */
    public function getCharge()
    {
        return $this->_charge;
    }

    /**
     * Возвращает сумму дополнительной комиссии при проведении платежа с помощью текущего способа оплаты
     * @return AmountInterface Сумма комиссии
     */
    public function getFee()
    {
        return $this->_fee;
    }

    /**
     * Возвращает признак присутствия дополнительной комиссии на стороне партнера
     * @return bool True если комиссия на стороне партнёра имеется, false если нет
     */
    public function getExtraFee()
    {
        return $this->_extraFee;
    }
}
