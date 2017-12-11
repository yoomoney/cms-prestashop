<?php

namespace YaMoney\Request\Payments;

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
 * Класс объекта ответа от API со списком платежей магазина
 *
 * @package YaMoney\Request\Payments
 */
class PaymentsResponse
{
    /**
     * @var PaymentInterface[] Массив платежей
     */
    private $items;

    /**
     * @var string|null Токен следующей страницы
     */
    private $nextPage;

    /**
     * Конструктор, устанавливает свойства объекта из пришедшего из API ассоциативного массива
     * @param array $options Массив настроек, пришедший от API
     */
    public function __construct($options)
    {
        $this->items = array();
        foreach ($options['items'] as $paymentInfo) {
            $payment = new Payment();
            $payment->setId($paymentInfo['id']);
            $payment->setStatus($paymentInfo['status']);
            $payment->setAmount(new MonetaryAmount(
                $paymentInfo['amount']['value'],
                $paymentInfo['amount']['currency']
            ));
            $payment->setCreatedAt(strtotime($paymentInfo['created_at']));
            $payment->setPaymentMethod($this->factoryPaymentMethod($paymentInfo['payment_method']));
            $payment->setPaid($paymentInfo['paid']);

            if (!empty($paymentInfo['error'])) {
                $error = new PaymentError();
                $error->setCode($paymentInfo['error']['code']);
                if (!empty($paymentInfo['error']['description'])) {
                    $error->setDescription($paymentInfo['error']['description']);
                }
                $payment->setError($error);

            }
            if (!empty($paymentInfo['recipient'])) {
                $recipient = new Recipient();
                $recipient->setAccountId($paymentInfo['recipient']['account_id']);
                $recipient->setGatewayId($paymentInfo['recipient']['gateway_id']);
                $payment->setRecipient($recipient);
            }
            if (!empty($paymentInfo['captured_at'])) {
                $payment->setCapturedAt(strtotime($paymentInfo['captured_at']));
            }
            if (!empty($paymentInfo['confirmation'])) {
                if ($paymentInfo['confirmation']['type'] === ConfirmationType::REDIRECT) {
                    $confirmation = new ConfirmationRedirect();
                    $confirmation->setConfirmationUrl($paymentInfo['confirmation']['confirmation_url']);
                    $confirmation->setEnforce($paymentInfo['confirmation']['enforce']);
                    $confirmation->setReturnUrl($paymentInfo['confirmation']['return_url']);
                } else {
                    $confirmation = new ConfirmationExternal();
                }
                $payment->setConfirmation($confirmation);
            }
            if (!empty($paymentInfo['refunded_amount'])) {
                $payment->setRefundedAmount(new MonetaryAmount(
                    $paymentInfo['refunded_amount']['value'], $paymentInfo['refunded_amount']['currency']
                ));
            }
            if (!empty($paymentInfo['receipt_registration'])) {
                $payment->setReceiptRegistration($paymentInfo['receipt_registration']);
            }
            if (!empty($paymentInfo['metadata'])) {
                $metadata = new Metadata();
                foreach ($paymentInfo['metadata'] as $key => $value) {
                    $metadata->offsetSet($key, $value);
                }
                $payment->setMetadata($metadata);
            }
            $this->items[] = $payment;
        }
        if (!empty($options['next_page'])) {
            $this->nextPage = $options['next_page'];
        }
    }

    /**
     * Возвращает список платежей
     * @return PaymentInterface[] Список платежей
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Возвращает токен следующей страницы, если он задан, или null
     * @return string|null Токен следующей страницы
     */
    public function getNextPage()
    {
        return $this->nextPage;
    }

    /**
     * Проверяет имееотся ли в ответе токен следующей страницы
     * @return bool True если токен следующей страницы есть, false если нет
     */
    public function hasNextPage()
    {
        return $this->nextPage !== null;
    }

    /**
     * Фабричный метод для создания объектов методов оплаты
     * @param array $options Массив настроек метода оплаты
     * @return AbstractPaymentMethod Используемый способ оплаты
     */
    private function factoryPaymentMethod($options)
    {
        $factory = new PaymentMethodFactory();
        return $factory->factoryFromArray($options);
    }
}
