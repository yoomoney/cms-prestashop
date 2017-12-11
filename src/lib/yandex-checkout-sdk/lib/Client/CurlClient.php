<?php

namespace YaMoney\Client;

use Psr\Log\LoggerInterface;
use YaMoney\Common\Exceptions\ApiConnectionException;
use YaMoney\Common\Exceptions\ApiException;
use YaMoney\Common\Exceptions\AuthorizeException;
use YaMoney\Common\HttpVerb;
use YaMoney\Common\ResponseObject;
use YaMoney\Helpers\RawHeadersParser;

/**
 * Class CurlClient
 * @package YaMoney\Client
 */
class CurlClient implements ApiClientInterface
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var string
     */
    private $shopId;

    /**
     * @var string
     */
    private $shopPassword;

    /**
     * @var int
     */
    private $timeout = 80;

    /**
     * @var int
     */
    private $connectionTimeout = 30;

    /**
     * @var bool
     */
    private $keepAlive = true;

    /**
     * @var array
     */
    private $defaultHeaders = array(
        'Content-Type' => 'application/json',
        'Accept' => 'application/json'
    );

    /**
     * @var resource
     */
    private $curl;

    /**
     * @var LoggerInterface|null
     */
    private $logger;

    /**
     * @param LoggerInterface|null $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function call($path, $method, $queryParams, $httpBody = null, $headers = array())
    {
        if ($this->logger !== null) {
            $message = 'Send request: ' . $method . ' ' . $path;
            if (!empty($queryParams)) {
                $message .= ' with query params: ' . json_encode($queryParams);
            }
            if (!empty($httpBody)) {
                $message .= ' with body: ' . $httpBody;
            }
            if (!empty($httpBody)) {
                $message .= ' with headers: ' . json_encode($headers);
            }
            $this->logger->info($message);
        }

        $url = $this->getUrl() . $path;

        if (!empty($queryParams)) {
            $url = $url . '?' . http_build_query($queryParams);
        }

        $headers = $this->prepareHeaders($headers);

        $this->initCurl();

        $this->setCurlOption(CURLOPT_URL, $url);

        $this->setCurlOption(CURLOPT_RETURNTRANSFER, true);

        $this->setCurlOption(CURLOPT_HEADER, true);

        $this->setCurlOption(CURLOPT_BINARYTRANSFER, true);

        if (!$this->shopId || !$this->shopPassword) {
            throw new AuthorizeException('shopId or shopPassword not set');
        } else {
            $this->setCurlOption(CURLOPT_USERPWD, "{$this->shopId}:{$this->shopPassword}");
        }

        $this->setBody($method, $httpBody);

        $this->setCurlOption(CURLOPT_HTTPHEADER, $headers);

        $this->setCurlOption(CURLOPT_CONNECTTIMEOUT, $this->connectionTimeout);

        $this->setCurlOption(CURLOPT_TIMEOUT, $this->timeout);

        list($httpHeaders, $httpBody, $responseInfo) = $this->sendRequest();

        if (!$this->keepAlive) {
            $this->closeCurlConnection();
        }

        if ($this->logger !== null) {
            $message = 'Response with code ' . $responseInfo['http_code'] . ' received with headers: '
                . json_encode($httpHeaders);
            if (!empty($httpBody)) {
                $message .= ' and body: ' . $httpBody;
            }
            $this->logger->info($message);
        }

        return new ResponseObject(array(
            'code' => $responseInfo['http_code'],
            'headers' => $httpHeaders,
            'body' => $httpBody
        ));
    }

    /**
     * @param $optionName
     * @param $optionValue
     * @return bool
     */
    public function setCurlOption($optionName, $optionValue)
    {
        return curl_setopt($this->curl, $optionName, $optionValue);
    }


    /**
     * @return resource
     */
    private function initCurl()
    {
        if (!$this->curl || !$this->keepAlive) {
            $this->curl = curl_init();
        }

        return $this->curl;
    }

    /**
     * Close connection
     */
    public function closeCurlConnection()
    {
        if ($this->curl !== null) {
            curl_close($this->curl);
        }
    }

    /**
     * @return array
     */
    public function sendRequest()
    {
        $response = curl_exec($this->curl);
        $httpHeaderSize = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
        $httpHeaders = RawHeadersParser::parse(substr($response, 0, $httpHeaderSize));
        $httpBody = substr($response, $httpHeaderSize);
        $responseInfo = curl_getinfo($this->curl);
        $curlError = curl_error($this->curl);
        $curlErrno = curl_errno($this->curl);
        if ($response === false) {
            $this->handleCurlError($curlError, $curlErrno);
        }

        return array($httpHeaders, $httpBody, $responseInfo);
    }

    /**
     * @param $method
     * @param $httpBody
     * @throws ApiException
     */
    public function setBody($method, $httpBody)
    {
        switch ($method) {
            case HttpVerb::POST:
                $this->setCurlOption(CURLOPT_POST, true);
                $this->setCurlOption(CURLOPT_POSTFIELDS, $httpBody);
                break;
            case HttpVerb::PUT:
                $this->setCurlOption(CURLOPT_CUSTOMREQUEST, "PUT");
                $this->setCurlOption(CURLOPT_POSTFIELDS, $httpBody);
                break;
            case HttpVerb::DELETE:
                $this->setCurlOption(CURLOPT_CUSTOMREQUEST, "DELETE");
                $this->setCurlOption(CURLOPT_POSTFIELDS, $httpBody);
                break;
            case HttpVerb::PATCH:
                $this->setCurlOption(CURLOPT_CUSTOMREQUEST, "PATCH");
                $this->setCurlOption(CURLOPT_POSTFIELDS, $httpBody);
                break;
            case HttpVerb::OPTIONS:
                $this->setCurlOption(CURLOPT_CUSTOMREQUEST, "OPTIONS");
                $this->setCurlOption(CURLOPT_POSTFIELDS, $httpBody);
                break;
            case HttpVerb::HEAD:
                $this->setCurlOption(CURLOPT_NOBODY, true);
                break;
            case HttpVerb::GET:
                break;
            default:
                throw new ApiException('Invalid method verb: ' . $method);
        }
    }

    /**
     * @param mixed $shopId
     * @return CurlClient
     */
    public function setShopId($shopId)
    {
        $this->shopId = $shopId;
        return $this;
    }

    /**
     * @param mixed $shopPassword
     * @return CurlClient
     */
    public function setShopPassword($shopPassword)
    {
        $this->shopPassword = $shopPassword;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param mixed $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * @return mixed
     */
    public function getConnectionTimeout()
    {
        return $this->connectionTimeout;
    }

    /**
     * @param mixed $connectionTimeout
     */
    public function setConnectionTimeout($connectionTimeout)
    {
        $this->connectionTimeout = $connectionTimeout;
    }

    /**
     * @return mixed
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param mixed $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * @param string $error
     * @param int $errno
     * @throws ApiConnectionException
     */
    private function handleCurlError($error, $errno)
    {
        switch ($errno) {
            case CURLE_COULDNT_CONNECT:
            case CURLE_COULDNT_RESOLVE_HOST:
            case CURLE_OPERATION_TIMEOUTED:
                $msg = "Could not connect to Yandex Money API. Please check your internet connection and try again.";
                break;
            case CURLE_SSL_CACERT:
            case CURLE_SSL_PEER_CERTIFICATE:
                $msg = "Could not verify SSL certificate.";
                break;
            default:
                $msg = "Unexpected error communicating.";
        }
        $msg .= "\n\n(Network error [errno $errno]: $error)";
        throw new ApiConnectionException($msg);
    }

    /**
     * @return mixed
     */
    private function getUrl()
    {
        $config = $this->config;
        return $config['url'];
    }

    /**
     * @param bool $keepAlive
     * @return CurlClient
     */
    public function setKeepAlive($keepAlive)
    {
        $this->keepAlive = $keepAlive;
        return $this;
    }

    /**
     * @param $headers
     * @return array
     */
    private function prepareHeaders($headers)
    {
        $headers = array_merge($this->defaultHeaders, $headers);
        $headers = array_map(function ($key, $value) {
            return $key . ":" . $value;
        }, array_keys($headers), $headers);

        return $headers;
    }
}