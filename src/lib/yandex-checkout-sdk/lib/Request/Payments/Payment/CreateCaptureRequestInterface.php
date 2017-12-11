<?php

namespace YaMoney\Request\Payments\Payment;

use YaMoney\Model\AmountInterface;
use YaMoney\Model\MonetaryAmount;

/**
 * Interface CreateCaptureRequestInterface
 *
 * @package YaMoney\Request\Payments\Payment
 *
 * @property-read MonetaryAmount $amount Подтверждаемая сумма оплаты
 */
interface CreateCaptureRequestInterface
{
    /**
     * Возвращает подтвердаемую сумму оплаты
     * @return AmountInterface Подтверждаемая сумма оплаты
     */
    function getAmount();

    /**
     * Проверяет была ли установлена сумма оплаты
     * @return bool True если сумма оплаты была установлена, false если нет
     */
    function hasAmount();
}
