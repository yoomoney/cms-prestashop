<?php

namespace YaMoney\Request;

/**
 * Интерфейс объекта запроса на получение списка возможных способов оплаты от API
 * 
 * @package YaMoney\Request
 * 
 * @property-read string $accountId Идентификатор магазина
 * @property-read string $gatewayId Идентификатор шлюза
 * @property-read string $amount Сумма заказа
 * @property-read string $currency Код валюты
 * @property-read string $confirmationType Сценарий подтверждения платежа
 */
interface PaymentOptionsRequestInterface
{
    /**
     * Возвращает идентификатор магазина для которого требуется провести платёж
     * @return string Идентификатор магазина
     */
    function getAccountId();

    /**
     * Проверяет был ли установлен идентификатор магазина
     * @return bool True если идентификатор магазина был установлен, false если нет
     */
    function hasAccountId();

    /**
     * Возвращает идентификатор шлюза
     * @return string|null Идентификатор шлюза
     */
    function getGatewayId();

    /**
     * Проверяет был ли установлен идентификатор шлюза
     * @return bool True если идентификатор шлюза был установлен, false если нет
     */
    function hasGatewayId();

    /**
     * Возвращает сумму заказа
     * @return string Сумма заказа
     */
    function getAmount();

    /**
     * Проверяет была ли установлена сумма заказа
     * @return bool True если сумма заказа была установлена, false если нет
     */
    function hasAmount();

    /**
     * Возвращает код валюты, в которой осуществляется покупка
     * @return string Код валюты
     */
    function getCurrency();

    /**
     * Проверяет был ли установлен код валюты
     * @return bool True если код валюты был установлен, false если нет
     */
    function hasCurrency();

    /**
     * Возвращает сценарий подтверждения платежа, для которого запрашивается список способов оплаты
     * @return string Сценарий подтверждения платежа
     */
    function getConfirmationType();

    /**
     * Проверяет был ли установлен способ подтверждения платежа
     * @return bool True если способ подтверждения платежа был установлен, false если нет
     */
    function hasConfirmationType();
}