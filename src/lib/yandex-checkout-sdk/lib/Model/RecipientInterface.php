<?php

namespace YaMoney\Model;

/**
 * Interface RecipientInterface
 *
 * @package YaMoney\Model
 *
 * @property-read string $accountId Идентификатор магазина
 * @property-read string $gatewayId Идентификатор шлюза
 */
interface RecipientInterface
{
    /**
     * @return string Идентификатор магазина
     */
    function getAccountId();

    /**
     * @return string Идентификатор шлюза
     */
    function getGatewayId();
}