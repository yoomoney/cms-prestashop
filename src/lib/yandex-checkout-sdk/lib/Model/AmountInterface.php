<?php

namespace YaMoney\Model;

/**
 * Interface AmountInterface
 *
 * @package YaMoney\Model
 *
 * @property-read string $value Сумма
 * @property-read string $currency Код валюты
 */
interface AmountInterface
{
    /**
     * Возвращает значение суммы
     * @return string Сумма
     */
    function getValue();

    /**
     * Возвращает сумму в копейках в виде целого числа
     * @return int Сумма в копейках/центах
     */
    function getIntegerValue();

    /**
     * Возвращает валюту
     * @return string Код валюты
     */
    function getCurrency();
}
