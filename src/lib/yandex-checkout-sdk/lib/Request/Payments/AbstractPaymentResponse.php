<?php

namespace YaMoney\Request\Payments;

use YaMoney\Model\AmountInterface;
use YaMoney\Model\Confirmation\ConfirmationRedirect;
use YaMoney\Model\Confirmation\ConfirmationExternal;
use YaMoney\Model\ConfirmationType;
use YaMoney\Model\Metadata;
use YaMoney\Model\MonetaryAmount;
use YaMoney\Model\Payment;
use YaMoney\Model\PaymentError;
use YaMoney\Model\PaymentInterface;
use YaMoney\Model\PaymentMethod\AbstractPaymentMethod;
use YaMoney\Model\PaymentMethod\PaymentMethodFactory;
use YaMoney\Model\Recipient;

/**
 * Абстрактный класс ответа от API, возвращающего информацию о платеже
 *
 * @package YaMoney\Request\Payments
 */
abstract class AbstractPaymentResponse extends Payment implements PaymentInterface
{
    /**
     * Конструктор, устанавливает настройки платежа из ассоциативного массива
     * @param array $paymentInfo Массив с информацией о платеже, пришедший от API
     */
    public function __construct($paymentInfo)
    {
        $this->setId($paymentInfo['id']);
        $this->setStatus($paymentInfo['status']);
        $this->setAmount($this->factoryAmount($paymentInfo['amount']));
        $this->setCreatedAt($paymentInfo['created_at']);
        $this->setPaid($paymentInfo['paid']);
        if (!empty($paymentInfo['payment_method'])) {
            $this->setPaymentMethod($this->factoryPaymentMethod($paymentInfo['payment_method']));
        }

        if (!empty($paymentInfo['error'])) {
            $error = new PaymentError();
            $error->setCode($paymentInfo['error']['code']);
            if (!empty($paymentInfo['error']['description'])) {
                $error->setDescription($paymentInfo['error']['description']);
            }
            $this->setError($error);
        }
        /* @todo не устанавливаем пока реципиента
        if (!empty($paymentInfo['recipient'])) {
            $recipient = new Recipient();
            $recipient->setAccountId($paymentInfo['recipient']['account_id']);
            $recipient->setGatewayId($paymentInfo['recipient']['gateway_id']);
            $this->setRecipient($recipient);
        }
        */
        if (!empty($paymentInfo['captured_at'])) {
            $this->setCapturedAt(strtotime($paymentInfo['captured_at']));
        }
        if (!empty($paymentInfo['confirmation'])) {
            if ($paymentInfo['confirmation']['type'] === ConfirmationType::REDIRECT) {
                $confirmation = new ConfirmationRedirect();
                $confirmation->setConfirmationUrl($paymentInfo['confirmation']['confirmation_url']);
                if (empty($paymentInfo['confirmation']['enforce'])) {
                    $confirmation->setEnforce(false);
                } else {
                    $confirmation->setEnforce($paymentInfo['confirmation']['enforce']);
                }
                if (!empty($paymentInfo['confirmation']['return_url'])) {
                    $confirmation->setReturnUrl($paymentInfo['confirmation']['return_url']);
                }
            } else {
                $confirmation = new ConfirmationExternal();
            }
            $this->setConfirmation($confirmation);
        }
        if (!empty($paymentInfo['refunded_amount'])) {
            $this->setRefundedAmount($this->factoryAmount($paymentInfo['refunded_amount']));
        }
        if (!empty($paymentInfo['receipt_registration'])) {
            $this->setReceiptRegistration($paymentInfo['receipt_registration']);
        }
        if (!empty($paymentInfo['metadata'])) {
            $metadata = new Metadata();
            foreach ($paymentInfo['metadata'] as $key => $value) {
                $metadata->offsetSet($key, $value);
            }
            $this->setMetadata($metadata);
        }
    }

    /**
     * Фабричный метод для создания способа оплаты
     * @param array $options Настройки способа оплаты в массиве
     * @return AbstractPaymentMethod Инстанс способа оплаты нужного типа
     */
    private function factoryPaymentMethod($options)
    {
        $factory = new PaymentMethodFactory();
        return $factory->factoryFromArray($options);
    }

    /**
     * Фабричный метод создания суммы
     * @param array $options Сумма в виде ассоциативного массива
     * @return AmountInterface Созданный инстанс суммы
     */
    private function factoryAmount($options)
    {
        $amount = new MonetaryAmount(null, $options['currency']);
        if ($options['value'] > 0) {
            $amount->setValue($options['value']);
        }
        return $amount;
    }
}