<?php

namespace YaMoney\Request\Refunds;

use YaMoney\Model\MonetaryAmount;
use YaMoney\Model\Refund;
use YaMoney\Model\RefundError;

/**
 * Абстрактный класс ответа от API с информацией о возврате
 *
 * @package YaMoney\Request\Refunds
 */
abstract class AbstractRefundResponse extends Refund
{
    /**
     * Конструктор
     * @param array $options Ассоциативный массив с информацией, вернувшейся от API
     */
    public function __construct(array $options)
    {
        $this->setId(empty($options['id']) ? null : $options['id']);
        $this->setPaymentId(empty($options['payment_id']) ? null : $options['payment_id']);
        $this->setStatus(empty($options['status']) ? null : $options['status']);
        $this->setCreatedAt(empty($options['created_at']) ? null : $options['created_at']);
        $this->setAmount(new MonetaryAmount($options['amount']['value'], $options['amount']['currency']));

        if (!empty($options['error'])) {
            $error = new RefundError();
            $error->setCode($options['error']['code']);
            if (!empty($options['error']['description'])) {
                $error->setDescription($options['error']['description']);
            }
            $this->setError($error);
        }

        if (!empty($options['authorized_at'])) {
            $this->setAuthorizedAt($options['authorized_at']);
        }

        if (!empty($options['receipt_registration'])) {
            $this->setReceiptRegistration($options['receipt_registration']);
        }

        if (!empty($options['comment'])) {
            $this->setComment($options['comment']);
        }
    }
}
