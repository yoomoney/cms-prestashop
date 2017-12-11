<?php


namespace YaMoney\Common\Exceptions;


class JsonException extends \UnexpectedValueException
{
    public static $errorLabels = array(
        JSON_ERROR_NONE => 'No error',
        JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
        JSON_ERROR_STATE_MISMATCH => 'State mismatch (invalid or malformed JSON)',
        JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
        JSON_ERROR_SYNTAX => 'Syntax error',
        JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded'
    );

    public function __construct($message = "", $code = 0, \Throwable $previous = null)
    {
        $errorMsg = isset(self::$errorLabels[$code]) ? self::$errorLabels[$code] : 'Unknown error';
        $message = sprintf('%s %s', $message, $errorMsg);
        parent::__construct($message, $code, $previous);
    }
}