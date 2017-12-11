<?php

namespace YaMoney\Helpers;


class RawHeadersParser
{
    public static function parse($rawHeaders)
    {
        $headers = array();
        $key = '';

        foreach (explode("\n", $rawHeaders) as $headerRow) {
            if (trim($headerRow) === '') {
                break;
            }
            $headerArray = explode(':', $headerRow, 2);

            if (isset($headerArray[1])) {
                if (!isset($headers[$headerArray[0]])) {
                    $headers[trim($headerArray[0])] = trim($headerArray[1]);
                } elseif (is_array($headers[$headerArray[0]])) {
                    $headers[trim($headerArray[0])] = array_merge($headers[trim($headerArray[0])], array(trim($headerArray[1])));
                } else {
                    $headers[trim($headerArray[0])] = array_merge(array($headers[trim($headerArray[0])]), array(trim($headerArray[1])));
                }

                $key = $headerArray[0];
            } else {
                if (substr($headerArray[0], 0, 1) === "\t") {
                    $headers[$key] .= "\r\n\t" . trim($headerArray[0]);
                } elseif (!$key) {
                    $headers[0] = trim($headerArray[0]);
                }
                trim($headerArray[0]);
            }
        }

        return $headers;
    }
}