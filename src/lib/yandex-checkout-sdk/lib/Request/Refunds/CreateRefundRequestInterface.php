<?php

namespace YaMoney\Request\Refunds;

use YaMoney\Model\AmountInterface;
use YaMoney\Model\ReceiptInterface;

/**
 * Интерфейс объекта запроса на возврат
 *
 * @package YaMoney\Request\Refunds
 *
 * @property-read string $paymentId Айди платежа для которого создаётся возврат
 * @property-read AmountInterface $amount Сумма возврата
 * @property-read string $comment Комментарий к операции возврата, основание для возврата средств покупателю.
 * @property-read ReceiptInterface|null $receipt Инстанс чека или null
 */
interface CreateRefundRequestInterface
{
    /**
     * Возвращает айди платежа для которого создаётся возврат средств
     * @return string Айди платежа для которого создаётся возврат
     */
    function getPaymentId();

    /**
     * Возвращает сумму возвращаемых средств
     * @return AmountInterface Сумма возврата
     */
    function getAmount();

    /**
     * Возвращает комментарий к возврату или null, если комментарий не задан
     * @return string Комментарий к операции возврата, основание для возврата средств покупателю.
     */
    function getComment();

    /**
     * Проверяет задан ли комментарий к создаваемому возврату
     * @return bool True если комментарий установлен, false если нет
     */
    function hasComment();

    /**
     * Возвращает инстанс чека или null если чек не задан
     * @return ReceiptInterface|null Инстанс чека или null
     */
    function getReceipt();

    /**
     * Проверяет задан ли чек
     * @return bool True если чек есть, false если нет
     */
    function hasReceipt();
}