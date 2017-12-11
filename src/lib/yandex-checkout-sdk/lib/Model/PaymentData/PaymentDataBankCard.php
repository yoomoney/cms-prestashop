<?php

namespace YaMoney\Model\PaymentData;

use YaMoney\Common\Exceptions\InvalidPropertyValueTypeException;
use YaMoney\Model\PaymentMethodType;

/**
 * PaymentDataBankCard
 * Платежные данные для проведения оплаты при помощи банковской карты
 * 
 * @property PaymentDataBankCardCard $bankCard Данные банковской карты
 */
class PaymentDataBankCard extends AbstractPaymentData
{
    /**
     * Необходим при оплате PCI-DSS данными.
     * @var PaymentDataBankCardCard Данные банковской карты
     */
    private $_card;

    public function __construct()
    {
        $this->_setType(PaymentMethodType::BANK_CARD);
    }

    /**
     * @return PaymentDataBankCardCard Данные банковской карты
     */
    public function getBankCard()
    {
        return $this->_card;
    }

    /**
     * @param PaymentDataBankCardCard|array $value Данные банковской карты
     */
    public function setBankCard($value)
    {
        if ($value === null || $value === '' || $value === array()) {
            $this->_card = null;
        } elseif (is_object($value) && $value instanceof PaymentDataBankCardCard) {
            $this->_card = $value;
        } elseif (is_array($value) || $value instanceof \Traversable) {
            $card = new PaymentDataBankCardCard();
            foreach ($value as $property => $val) {
                $card->offsetSet($property, $val);
            }
            $this->_card = $card;
        } else {
            throw new InvalidPropertyValueTypeException(
                'Invalid card value type in PaymentDataBankCard', 0, 'PaymentDataBankCard.card', $value
            );
        }
    }
}
