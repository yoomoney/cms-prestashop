<?php
/**
 * Module is prohibited to sales! Violation of this condition leads to the deprivation of the license!
 *
 * @author    Yandex.Money <cms@yamoney.ru>
 * @copyright © 2015-2017 NBCO Yandex.Money LLC
 * @license   https://money.yandex.ru/doc.xml?id=527052
 *
 * @category  Front Office Features
 * @package   Yandex Payment Solution
 */

namespace YandexMoneyModule;

use Configuration;
use Context;
use Module;
use PrestaShopException;
use Tools;
use yandexmodule;

class Metrics
{
    public $url = 'https://oauth.yandex.ru/';
    public $url_api = 'https://api-metrika.yandex.ru/management/v1/';
    public $client_id;
    public $errors;
    public $number;
    public $client_secret;
    public $code;
    public $token;
    public $context;

    /**
     * @var yandexmodule
     */
    private $module;
    
    public function __construct()
    {
        $this->context = Context::getContext();
        $this->client_id = Configuration::get('YA_METRICS_ID_APPLICATION');
        $this->number = Configuration::get('YA_METRICS_NUMBER');
        $this->client_secret = Configuration::get('YA_METRICS_PASSWORD_APPLICATION');
        $this->token = Configuration::get('YA_METRICS_TOKEN') ? Configuration::get('YA_METRICS_TOKEN') : '';
        $this->module = Module::getInstanceByName('yandexmodule');
    }
    
    public function run()
    {
        $this->code = Tools::getValue('code');
        $error = Tools::getValue('error');
        if ($error == '') {
            if (empty($this->token)) {
                $this->errors = 'Пустой Токен!';
                return false;
            } else {
                return true;
            }
        } else {
            $this->errors = 'error #'.$error.' error description: '.Tools::getValue('error_description');
            return false;
        }
    }
    
    public function getToken()
    {
        $params = array(
            'grant_type' => 'authorization_code',
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'code' => $this->code
        );
        $response = $this->post($this->url.'token', array(), $params, 'POST');
        $data = Tools::jsonDecode($response->body);
        if ($response->status_code == 200) {
            $this->token = $data->access_token;
            Configuration::updateValue('YA_METRICS_TOKEN', $this->token);
        } else {
            $this->errors = 'error #'.$response->status_code
                .' error description: '.$data->error_description.' '.$data->error;
        }
    }
    
    // Все счётчики
    public function getAllCounters()
    {
        return $this->sendResponse('counters', array(), array(), 'GET');
    }
    
    // Конкретный счётчик
    public function getCounter()
    {
        return $this->sendResponse('counter/'.$this->number, array(), array(), 'GET');
    }
    
    // Проверка кода счётчика
    public function getCounterCheck()
    {
        return $this->sendResponse('counter/'.$this->number.'/check', array(), array(), 'GET');
    }
    
    // Все цели счётчика
    public function getCounterGoals()
    {
        return $this->sendResponse('counter/'.$this->number.'/goals', array(), array(), 'GET');
    }
    
    // Конкретная цель
    public function getCounterGoal($goal)
    {
        return $this->sendResponse('counter/'.$this->number.'/goal/'.$goal, array(), array(), 'GET');
    }
    
    // Добавление цели
    public function addCounterGoal($params)
    {
        return $this->sendResponse('counter/'.$this->number.'/goals', array(), $params, 'POSTJSON');
    }
    
    // Удаление цели
    public function deleteCounterGoal($goal)
    {
        return $this->sendResponse('counter/'.$this->number.'/goal/'.$goal, array(), array(), 'DELETE');
    }
    
    // Редактирование счётчика
    public function editCounter()
    {
        $params = array(
            'counter' => array(
                'goals_remove' => 0,
                'code_options' => array(
                    'clickmap'   => Configuration::get('YA_METRICS_SET_CLICKMAP') ? 1 : 0,
                    'visor'      => Configuration::get('YA_METRICS_SET_WEBVIZOR') ? 1 : 0,
                    'track_hash' => Configuration::get('YA_METRICS_SET_HASH') ? 1 : 0,
                    'ecommerce'  => 1,
                    'informer'   => array(
                        'enabled' => 0,
                    ),
                )
            )
        );

        return $this->sendResponse('counter/'.$this->number, array(), $params, 'PUT');
    }

    /**
     * @param $to
     * @param $headers
     * @param $params
     * @param $type
     * @param int $pretty
     * @return null
     */
    public function sendResponse($to, $headers, $params, $type, $pretty = 1)
    {
        $headers[] = 'Authorization: OAuth '.$this->token;
        $response = $this->post(
            $this->url_api.$to.'?pretty='.$pretty,
            $headers,
            $params,
            $type
        );

        $data = Tools::jsonDecode($response->body);
        if ($response->status_code == 200) {
            return $data;
        } else {
            $this->module->log('info', 'Failed to send request: response is ' . $response->body);
            return null;
        }
    }
    
    public static function post($url, $headers, $params, $type)
    {
        $curlOpt = array(
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLINFO_HEADER_OUT => 1,
            CURLOPT_USERAGENT => 'php-market',
        );
        
        switch (Tools::strtoupper($type)) {
            case 'DELETE':
                $curlOpt[CURLOPT_CUSTOMREQUEST] = "DELETE";
                break;
            case 'GET':
                if (!empty($params)) {
                    $url .= (strpos($url, '?')===false ? '?' : '&') . http_build_query($params);
                }
                break;
            case 'PUT':
                $headers[] = 'Content-Type: application/x-yametrika+json';
                $body = Tools::jsonEncode($params);
                $fp = fopen('php://temp/maxmemory:256000', 'w');
                if (!$fp) {
                    throw new PrestaShopException('Could not open temp memory data');
                }
                fwrite($fp, $body);
                fseek($fp, 0);
                $curlOpt[CURLOPT_PUT] = 1;
                $curlOpt[CURLOPT_BINARYTRANSFER] = 1;
                $curlOpt[CURLOPT_INFILE] = $fp; // file pointer
                $curlOpt[CURLOPT_INFILESIZE] = Tools::strlen($body);
                break;
            case 'POST':
                $headers[] = 'Content-Type: application/x-www-form-urlencoded';
                $curlOpt[CURLOPT_POST] = true;
                $curlOpt[CURLOPT_POSTFIELDS] = http_build_query($params);
                break;
            case 'POSTJSON':
                $headers[] = 'Content-Type: application/x-yametrika+json';

                $curlOpt[CURLOPT_POST] = true;
                $curlOpt[CURLOPT_POSTFIELDS] = Tools::jsonEncode($params);
                break;
        }
        $curlOpt[CURLOPT_HTTPHEADER] = $headers;
        $curl = curl_init($url);
        curl_setopt_array($curl, $curlOpt);
        $rbody = curl_exec($curl);
        $rcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        // Tools::d(curl_getinfo($curl, CURLINFO_HEADER_OUT));
        curl_close($curl);
        $result = new \stdClass();
        $result->status_code = $rcode;
        $result->body = $rbody;
        return $result;
    }
}
