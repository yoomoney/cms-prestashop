<?php 
/**
* Module is prohibited to sales! Violation of this condition leads to the deprivation of the license!
*
* @category  Front Office Features
* @package   Yandex Payment Solution
* @author    Yandex.Money <cms@yamoney.ru>
* @copyright © 2015 NBCO Yandex.Money LLC
* @license   https://money.yandex.ru/doc.xml?id=527052
*/

class YandexModuleRequests
{
    public $logFile;
    
    public static function de($object, $kill = true)
    {
        echo '<xmp style="text-align: left;">';
        print_r($object);
        echo '</xmp><br />';

        if ($kill) {
            die('END');
        }

        return $object;
    }
    
    public static function post($url, $headers, $params)
    {
        $curl = curl_init($url);
        if (isset($headers['Authorization'])) {
            $token = $headers['Authorization'];
            $headers = array();
            $headers[] = 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8';
            $headers[] = 'Authorization: '.$token;
        }
        $params = http_build_query($params);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_USERAGENT, 'yamolib-php');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_TIMEOUT, 80);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        $rbody = curl_exec($curl);
        $errno = curl_errno($curl);
        $error = curl_error($curl);
        $rcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        // self::de(curl_getinfo($curl, CURLINFO_HEADER_OUT), false);
        curl_close($curl);
        $result = new stdClass();
        $result->status_code = $rcode;
        $result->body = $rbody;
        // self::de($url, false);
        // self::de($params, false);
        // self::de($headers, false);
        return $result;
    }
    
    private function _log($message)
    {
        $f = $this->logFile;
        if ($f !== null) {
            if (!file_exists($f)) {
                echo "log file $f not found";
            }
            if (!is_file($f)) {
                echo "log file $f is not a file";
            }
            if (!is_writable($f)) {
                echo "log file $f is not writable";
            }
                
            if (!$handle = fopen($f, 'a')) {
                echo "couldn't open log file $f for appending";
            }
            
            $time = '[' . date("Y-m-d H:i:s") . '] ';
            if (fwrite($handle, $time . $message . "\r\n") === false) {
                echo "couldn't fwrite message log to $f";
            }
            
            fclose($handle);
        }
    }
}
