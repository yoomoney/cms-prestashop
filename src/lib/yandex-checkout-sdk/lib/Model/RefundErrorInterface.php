<?php

namespace YaMoney\Model;

/**
 * Interface RefundErrorInterface
 *
 * @package YaMoney\Model
 *
 * @property-read string $code Код ошибки
 * @property-read string $description Дополнительное текстовое пояснение ошибки
 */
interface RefundErrorInterface
{
    /**
     * @return string Код ошибки
     */
    function getCode();

    /**
     * @return string Дополнительное текстовое пояснение ошибки
     */
    function getDescription();
}
