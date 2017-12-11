<?php

namespace YaMoney\Model;

use YaMoney\Model\PaymentMethod\AbstractPaymentMethod;

/**
 * Interface PaymentInterface
 * 
 * @package YaMoney\Model
 *
 * @property-read string $id Идентификатор платежа
 * @property-read string $status Текущее состояние платежа
 * @property-read PaymentErrorInterface $error Описание ошибки, возникшей при проведении платежа
 * @property-read RecipientInterface $recipient Получатель платежа
 * @property-read AmountInterface $amount Сумма заказа
 * @property-read AbstractPaymentMethod $paymentMethod Способ проведения платежа
 * @property-read string $referenceId Идентификатор заказа
 * @property-read \DateTime $createdAt Время создания заказа
 * @property-read \DateTime $capturedAt Время подтверждения платежа магазином
 * @property-read Confirmation\AbstractConfirmation $confirmation Способ подтверждения платежа
 * @property-read AmountInterface $charge Сумма к оплате покупателем
 * @property-read AmountInterface $income Сумма к получению магазином
 * @property-read AmountInterface $refunded Сумма возвращенных средств платежа
 * @property-read bool $paid Признак оплаты заказа
 * @property-read string $receiptRegistration Состояние регистрации фискального чека
 * @property-read Metadata $metadata Метаданные платежа указанные мерчантом
 */
interface PaymentInterface
{
    /**
     * Возвращает идентификатор платежа
     * @return string Идентификатор платежа
     */
    function getId();

    /**
     * Возвращает состояние платежа
     * @return string Текущее состояние платежа
     */
    public function getStatus();

    /**
     * Возвращает описание ошибки, возникшей при проведении платежа
     * @return PaymentErrorInterface|null Описание ошибки или null если ошибка не задана
     */
    public function getError();

    /**
     * Возвращает получателя платежа
     * @return RecipientInterface|null Получатель платежа или null если получатель не задан
     */
    public function getRecipient();

    /**
     * Возвращает сумму
     * @return AmountInterface Сумма платежа
     */
    public function getAmount();

    /**
     * Возвращает используемый способ проведения платежа
     * @return AbstractPaymentMethod Способ проведения платежа
     */
    public function getPaymentMethod();

    /**
     * Возвращает время создания заказа
     * @return \DateTime Время создания заказа
     */
    public function getCreatedAt();

    /**
     * Возвращает время подтверждения платежа магазином или null если если время не задано
     * @return \DateTime|null Время подтверждения платежа магазином
     */
    public function getCapturedAt();

    /**
     * Возвращает способ подтверждения платежа
     * @return Confirmation\AbstractConfirmation Способ подтверждения платежа
     */
    public function getConfirmation();

    /**
     * Возвращает сумму возвращенных средств
     * @return AmountInterface Сумма возвращенных средств платежа
     */
    public function getRefundedAmount();

    /**
     * Проверяет был ли уже оплачен заказ
     * @return bool Признак оплаты заказа, true если заказ оплачен, false если нет
     */
    public function getPaid();

    /**
     * Возвращает состояние регистрации фискального чека
     * @return string Состояние регистрации фискального чека
     */
    public function getReceiptRegistration();

    /**
     * Возвращает метаданные платежа установленные мерчантом
     * @return Metadata Метаданные платежа указанные мерчантом
     */
    public function getMetadata();
}