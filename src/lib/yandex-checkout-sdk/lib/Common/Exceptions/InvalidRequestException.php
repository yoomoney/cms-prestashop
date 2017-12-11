<?php

namespace YaMoney\Common\Exceptions;

use YaMoney\Common\AbstractRequest;

class InvalidRequestException extends \RuntimeException
{
    /**
     * @var AbstractRequest|null
     */
    private $errorRequest;

    /**
     * InvalidRequestException constructor.
     * @param AbstractRequest|string $error
     * @param int $code
     * @param null $previous
     */
    public function __construct($error, $code = 0, $previous = null)
    {
        if ($error instanceof AbstractRequest) {
            $message = 'Failed to build request "'.get_class($error).'": "'.$error->getLastValidationError().'"';
            $this->errorRequest = $error;
        } else {
            $message = $error;
        }
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return AbstractRequest|null
     */
    public function getRequestObject()
    {
        return $this->errorRequest;
    }
}