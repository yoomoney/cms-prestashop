<?php

namespace YaMoney\Common\Exceptions;

class EmptyPropertyValueException extends InvalidPropertyException
{
    /**
     * EmptyPropertyValueException constructor.
     * @param string $message
     * @param int $code
     * @param string $property
     */
    public function __construct($message = "", $code = 0, $property = "")
    {
        parent::__construct($message, $code, $property);
    }
}
