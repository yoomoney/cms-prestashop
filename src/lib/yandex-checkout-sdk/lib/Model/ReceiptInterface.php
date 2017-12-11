<?php

namespace YaMoney\Model;

/**
 * Interface ReceiptInterface
 * 
 * @package YaMoney\Model
 * 
 * @property-read ReceiptItemInterface[] $items Список товаров в заказе
 * @property-read int $taxSystemCode Код системы налогообложения. Число 1-6.
 * @property-read string $phone Номер телефона плательщика в формате ITU-T E.164 на который будет выслан чек.
 * @property-read string $email E-mail адрес плательщика на который будет выслан чек.
 */
interface ReceiptInterface
{
    /**
     * @return ReceiptItemInterface[] Список товаров в заказе
     */
    function getItems();

    /**
     * @return int Код системы налогообложения. Число 1-6.
     */
    function getTaxSystemCode();

    /**
     * @return string Номер телефона плательщика в формате ITU-T E.164 на который будет выслан чек.
     */
    function getPhone();

    /**
     * @return string E-mail адрес плательщика на который будет выслан чек.
     */
    function getEmail();

    /**
     * @return bool
     */
    function notEmpty();
}