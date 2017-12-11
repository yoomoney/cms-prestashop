<?php

namespace YaMoney\Common\Exceptions;

class InvalidPropertyValueException extends InvalidPropertyException
{
    /**
     * @var mixed
     */
    private $invalidValue;

    /**
     * InvalidPropertyValueTypeException constructor.
     * @param string $message
     * @param int $code
     * @param string $property
     * @param mixed $value
     */
    public function __construct($message = '', $code = 0, $property = '', $value = null)
    {
        parent::__construct($message, $code, $property);
        if ($value !== null) {
            $this->invalidValue = $value;
        }
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->invalidValue;
    }
}