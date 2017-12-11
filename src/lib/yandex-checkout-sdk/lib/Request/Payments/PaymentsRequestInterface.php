<?php

namespace YaMoney\Request\Payments;

/**
 * Interface PaymentsRequestInterface
 *
 * @package YaMoney\Request\Payments
 *
 * @property-read string|null $paymentId Идентификатор платежа
 * @property-read string|null $accountId Идентификатор магазина
 * @property-read string|null $gatewayId Идентификатор шлюза
 * @property-read \DateTime|null $createdGte Время создания, от (включительно)
 * @property-read \DateTime|null $createdGt Время создания, от (не включая)
 * @property-read \DateTime|null $createdLte Время создания, до (включительно)
 * @property-read \DateTime|null $createdLt Время создания, до (не включая)
 * @property-read \DateTime|null $authorizedGte Время проведения операции, от (включительно)
 * @property-read \DateTime|null $authorizedGt Время проведения операции, от (не включая)
 * @property-read \DateTime|null $authorizedLte Время проведения, до (включительно)
 * @property-read \DateTime|null $authorizedLt Время проведения, до (не включая)
 * @property-read string|null $status Статус платежа
 * @property-read string|null $nextPage Токен для получения следующей страницы выборки
 */
interface PaymentsRequestInterface
{
    /**
     * Возвращает идентификатор платежа если он задан или null
     * @return string|null Идентификатор платежа
     */
    function getPaymentId();

    /**
     * Проверяет, был ли задан идентификатор платежа
     * @return bool True если идентификатор был задан, false если нет
     */
    function hasPaymentId();

    /**
     * Возвращает идентификатор магазина, если он был задан
     * @return string|null Идентификатор магазина
     */
    function getAccountId();

    /**
     * Проверяет, был ли установлен идентификатор магазина
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
     * Возвращает дату создания от которой будут возвращены платежи или null если дата не была установлена
     * @return \DateTime|null Время создания, от (включительно)
     */
    function getCreatedGte();

    /**
     * Проверяет была ли установлена дата создания от которой выбираются платежи
     * @return bool True если дата была установлена, false если нет
     */
    function hasCreatedGte();

    /**
     * Возвращает дату создания от которой будут возвращены платежи или null если дата не была установлена
     * @return \DateTime|null Время создания, от (не включая)
     */
    function getCreatedGt();

    /**
     * Проверяет была ли установлена дата создания от которой выбираются платежи
     * @return bool True если дата была установлена, false если нет
     */
    function hasCreatedGt();

    /**
     * Возвращает дату создания до которой будут возвращены платежи или null если дата не была установлена
     * @return \DateTime|null Время создания, до (включительно)
     */
    function getCreatedLte();

    /**
     * Проверяет была ли установлена дата создания до которой выбираются платежи
     * @return bool True если дата была установлена, false если нет
     */
    function hasCreatedLte();

    /**
     * Возвращает дату создания до которой будут возвращены платежи или null если дата не была установлена
     * @return \DateTime|null Время создания, до (не включая)
     */
    function getCreatedLt();

    /**
     * Проверяет была ли установлена дата создания до которой выбираются платежи
     * @return bool True если дата была установлена, false если нет
     */
    function hasCreatedLt();

    /**
     * Возвращает дату проведения от которой будут возвращены платежи или null если дата не была установлена
     * @return \DateTime|null Время проведения операции, от (включительно)
     */
    function getAuthorizedGte();

    /**
     * Проверяет была ли установлена дата проведения от которой выбираются платежи
     * @return bool True если дата была установлена, false если нет
     */
    function hasAuthorizedGte();

    /**
     * Возвращает дату проведения от которой будут возвращены платежи или null если дата не была установлена
     * @return \DateTime|null Время проведения операции, от (не включая)
     */
    function getAuthorizedGt();

    /**
     * Проверяет была ли установлена дата проведения от которой выбираются платежи
     * @return bool True если дата была установлена, false если нет
     */
    function hasAuthorizedGt();

    /**
     * Возвращает дату проведения до которой будут возвращены платежи или null если дата не была установлена
     * @return \DateTime|null Время проведения, до (включительно)
     */
    function getAuthorizedLte();

    /**
     * Проверяет была ли установлена дата проведения до которой выбираются платежи
     * @return bool True если дата была установлена, false если нет
     */
    function hasAuthorizedLte();

    /**
     * Возвращает дату проведения до которой будут возвращены платежи платежи или null если она не была установлена
     * @return \DateTime|null Время проведения, до (не включая)
     */
    function getAuthorizedLt();

    /**
     * Проверяет была ли установлена дата проведения до которой выбираются
     * @return bool True если дата была установлена, false если нет
     */
    function hasAuthorizedLt();

    /**
     * Возвращает статус выбираемых платежей или null если он до этого не был установлен
     * @return string|null Статус выбираемых платежей
     */
    function getStatus();

    /**
     * Проверяет был ли установлен статус выбираемых платежей
     * @return bool True если статус был установлен, false если нет
     */
    function hasStatus();

    /**
     * Возвращает токен для получения следующей страницы выборки
     * @return string|null Токен для получения следующей страницы выборки
     */
    function getNextPage();

    /**
     * Проверяет был ли установлен токен следующей страницы
     * @return bool True если токен был установлен, false если нет
     */
    function hasNextPage();
}
