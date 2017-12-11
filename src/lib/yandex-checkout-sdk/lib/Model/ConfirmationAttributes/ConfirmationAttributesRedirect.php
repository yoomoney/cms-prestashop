<?php

namespace YaMoney\Model\ConfirmationAttributes;

use YaMoney\Common\Exceptions\InvalidPropertyValueTypeException;
use YaMoney\Helpers\TypeCast;
use YaMoney\Model\ConfirmationType;

/**
 * @property bool $enforce Требование принудительного подтверждения платежа покупателем, требование 3-D Secure для
 * оплаты банковскими картами. По умолчанию определяется политикой платежной системы.
 * @property string $returnUrl URL на который вернется плательщик после подтверждения или отмены платежа
 * на странице партнера.
 */
class ConfirmationAttributesRedirect extends AbstractConfirmationAttributes
{
    /**
     * @var bool Требование принудительного подтверждения платежа покупателем, требование 3-D Secure для оплаты
     * банковскими картами. По умолчанию определяется политикой платежной системы.
     */
    private $_enforce;

    /**
     * @var string URL на который вернется плательщик после подтверждения или отмены платежа на странице партнера.
     */
    private $_returnUrl;

    public function __construct()
    {
        $this->_setType(ConfirmationType::REDIRECT);
    }

    /**
     * @return bool Требование принудительного подтверждения платежа покупателем, требование 3-D Secure для
     * оплаты банковскими картами. По умолчанию определяется политикой платежной системы.
     */
    public function getEnforce()
    {
        return $this->_enforce;
    }

    /**
     * @param bool $value Требование принудительного подтверждения платежа покупателем, требование 3-D Secure
     * для оплаты банковскими картами. По умолчанию определяется политикой платежной системы.
     */
    public function setEnforce($value)
    {
        if ($value === null || $value === '') {
            $this->_enforce = null;
        } elseif (TypeCast::canCastToBoolean($value)) {
            $this->_enforce = (bool)$value;
        } else {
            throw new InvalidPropertyValueTypeException(
                'Invalid enforce value type', 0, 'confirmationAttributesRedirect.enforce', $value
            );
        }
    }

    /**
     * @return string URL на который вернется плательщик после подтверждения или отмены платежа на странице партнера.
     */
    public function getReturnUrl()
    {
        return $this->_returnUrl;
    }

    /**
     * @param string $value URL на который вернется плательщик после подтверждения или отмены платежа
     * на странице партнера.
     */
    public function setReturnUrl($value)
    {
        if ($value === null || $value === '') {
            $this->_returnUrl = null;
        } elseif (TypeCast::canCastToString($value)) {
            $this->_returnUrl = (string)$value;
        } else {
            throw new InvalidPropertyValueTypeException(
                'Invalid returnUrl value type', 0, 'confirmationAttributesRedirect.returnUrl', $value
            );
        }
    }
}
