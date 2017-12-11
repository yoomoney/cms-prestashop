<?php

namespace YaMoney\Helpers;

class StringObject
{
    /**
     * @var string
     */
    private $value;

    /**
     * StringObject constructor.
     * @param string $value
     */
    public function __construct($value)
    {
        $this->value = (string)$value;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->value;
    }
}