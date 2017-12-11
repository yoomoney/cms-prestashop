<?php

namespace YaMoney\Common\Exceptions;

class InvalidPropertyValueTypeException extends InvalidPropertyException
{
    /**
     * @var string
     */
    private $type;

    /**
     * InvalidPropertyValueTypeException constructor.
     * @param string $message
     * @param int $code
     * @param string $property
     * @param mixed $value
     */
    public function __construct($message = "", $code = 0, $property = "", $value = null)
    {
        parent::__construct($message, $code, $property);
        if ($value === null) {
            $this->type = 'null';
        } elseif (is_object($value)) {
            $this->type = get_class($value);
        } else {
            $this->type = gettype($value);
        }
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}