<?php

namespace YaMoney\Common\Exceptions;

class InvalidPropertyException extends \InvalidArgumentException
{
    /**
     * @var string
     */
    private $propertyName;

    /**
     * InvalidValueException constructor.
     * @param string $message
     * @param int $code
     * @param string $property
     */
    public function __construct($message = "", $code = 0, $property = "")
    {
        parent::__construct($message, $code);
        $this->propertyName = (string)$property;
    }

    /**
     * @return string
     */
    public function getProperty()
    {
        return $this->propertyName;
    }
}
