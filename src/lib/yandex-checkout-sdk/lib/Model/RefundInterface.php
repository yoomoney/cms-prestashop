<?php

namespace YaMoney\Model;

/**
 * Interface RefundInterface
 *
 * @package YaMoney\Model
 *
 * @property-read string $id Идентификатор возврата платежа
 * @property-read string $paymentId Идентификатор платежа
 * @property-read string $status Статус возврата
 * @property-read RefundErrorInterface|null $error Описание ошибки или null если ошибок нет
 * @property-read \DateTime $createdAt Время создания возврата
 * @property-read \DateTime|null $authorizedAt Время проведения возврата
 * @property-read AmountInterface $amount Сумма возврата
 * @property-read string $receiptRegistration Статус регистрации чека
 * @property-read string $comment Комментарий, основание для возврата средств покупателю
 */
interface RefundInterface
{
    /**
     * Возвращает идентификатор возврата платежа
     * @return string Идентификатор возврата
     */
    function getId();

    /**
     * Возвращает идентификатор платежа
     * @return string Идентификатор платежа
     */
    function getPaymentId();

    /**
     * Возвращает статус текущего возврата
     * @return string Статус возврата
     */
    function getStatus();

    /**
     * Возвращает описание ошибки, если она есть, либо null
     * @return RefundErrorInterface Инстанс объекта с описанием ошибки или null
     */
    function getError();

    /**
     * Возвращает дату создания возврата
     * @return \DateTime Время создания возврата
     */
    function getCreatedAt();

    /**
     * Возвращает дату проведения возврата
     * @return \DateTime|null Время проведения возврата
     */
    function getAuthorizedAt();

    /**
     * Возвращает сумму возврата
     * @return AmountInterface Сумма возврата
     */
    function getAmount();

    /**
     * Возвращает статус регистрации чека
     * @return string Статус регистрации чека
     */
    function getReceiptRegistration();

    /**
     * Возвращает комментарий к возврату
     * @return string Комментарий, основание для возврата средств покупателю
     */
    function getComment();
}
