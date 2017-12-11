<?php


namespace YaMoney\Common;


class HttpVerb extends AbstractEnum
{
    const GET = 'GET';
    const POST = 'POST';
    const PATCH = 'PATCH';
    const HEAD = 'HEAD';
    const OPTIONS = 'OPTIONS';
    const PUT = 'PUT';
    const DELETE = 'DELETE';

    protected static $validValues = array(
        'GET' => true,
        'POST' => true,
        'PATCH' => true,
        'HEAD' => true,
        'OPTIONS' => true,
        'PUT' => true,
        'DELETE' => true
    );
}