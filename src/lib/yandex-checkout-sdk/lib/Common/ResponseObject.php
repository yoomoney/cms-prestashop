<?php

namespace YaMoney\Common;


class ResponseObject
{
    protected $code;
    protected $headers;
    protected $body;

    public function __construct($config = null)
    {
        if (isset($config['headers'])) {
            $this->headers = $config['headers'];
        }

        if (isset($config['body'])) {
            $this->body = $config['body'];
        }

        if (isset($config['code'])) {
            $this->code = $config['code'];
        }
    }

    /**
     * @return mixed
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }
}