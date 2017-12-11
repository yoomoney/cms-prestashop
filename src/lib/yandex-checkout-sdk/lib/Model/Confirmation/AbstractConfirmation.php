<?php

namespace YaMoney\Model\Confirmation;

use YaMoney\Common\AbstractObject;
use YaMoney\Common\Exceptions\EmptyPropertyValueException;
use YaMoney\Common\Exceptions\InvalidPropertyValueException;
use YaMoney\Common\Exceptions\InvalidPropertyValueTypeException;
use YaMoney\Helpers\TypeCast;
use YaMoney\Model\ConfirmationType;

/**
 * Способ подтверждения платежа.
 *
 * @property-read string $type
 */
abstract class AbstractConfirmation extends AbstractObject
{
    /**
     * @var string
     */
    private $_type;

    /**
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * @param string $value
     */
    protected function _setType($value)
    {
        if ($value === null || $value === '') {
            throw new EmptyPropertyValueException(
                'Empty value for "type" parameter in Confirmation', 0, 'confirmation.type'
            );
        } elseif (TypeCast::canCastToEnumString($value)) {
            if (ConfirmationType::valueExists($value)) {
                $this->_type = (string)$value;
            } else {
                throw new InvalidPropertyValueException(
                    'Invalid value for "type" parameter in Confirmation', 0, 'confirmation.type', $value
                );
            }
        } else {
            throw new InvalidPropertyValueTypeException(
                'Invalid value type for "type" parameter in Confirmation', 0, 'confirmation.type', $value
            );
        }
    }
}
