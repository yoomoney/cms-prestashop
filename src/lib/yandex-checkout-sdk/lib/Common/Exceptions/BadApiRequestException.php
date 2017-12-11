<?php


namespace YaMoney\Common\Exceptions;


class BadApiRequestException extends ApiException
{
    const HTTP_CODE = 400;

    public $type;

    public $retryAfter;

    public function __construct($responseHeaders = array(), $responseBody = null)
    {
        $errorData = json_decode($responseBody, true);
        $message   = '';

        if (isset($errorData['description'])) {
            $message .= $errorData['description'].'.';
        }

        if (isset($errorData['code'])) {
            $message .= sprintf('Error code: %s.', $errorData['description']);
        }

        if (isset($errorData['parameter'])) {
            $message .= sprintf('Parameter name: %s.', $errorData['parameter']);
        }

        if (isset($errorData['retry_after'])) {
            $this->retryAfter = $errorData['retry_after'];
        }

        if (isset($errorData['type'])) {
            $this->retryAfter = $errorData['type'];
        }

        parent::__construct($message, self::HTTP_CODE, $responseHeaders, $responseBody);
    }
}