<?php

namespace YaMoney\Model;

/**
 * Interface ReceiptItemInterface
 *
 * @package YaMoney\Model
 *
 * @property-read string $description Наименование товара
 * @property-read int $quantity Количество
 * @property-read int $amount  Суммарная стоимость покупаемого товара в копейках/центах
 * @property-read AmountInterface $price Цена товара
 * @property-read int $vatCode Ставка НДС, число 1-6
 */
interface ReceiptItemInterface
{
    /**
     * Возвращает наименование товара
     * @return string Наименование товара
     */
    function getDescription();

    /**
     * Возвращает количество товара
     * @return float Количество купленного товара
     */
    function getQuantity();

    /**
     * Возвращает общую стоимость покупаемого товара в копейках/центах
     * @return int Сумма стоимости покупаемого товара
     */
    function getAmount();

    /**
     * Возвращает цену товара
     * @return AmountInterface Цена товара
     */
    function getPrice();

    /**
     * Возвращает ставку НДС
     * @return int|null Ставка НДС, число 1-6, или null если ставка не задана
     */
    function getVatCode();

    /**
     * Проверяет, является ли текущий элемент чека доствкой
     * @return bool True если доставка, false если обычный товар
     */
    function isShipping();
}