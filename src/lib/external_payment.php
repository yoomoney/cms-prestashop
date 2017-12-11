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

require_once __DIR__ . "/base.php";

class ExternalPayment extends BaseAPI
{
    public function __construct($instance_id)
    {
        $this->instance_id = $instance_id;
    }
    public static function getInstanceId($client_id)
    {
        return self::sendRequest("/api/instance-id",
            array("client_id" => $client_id));
    }
    public function request($payment_options)
    {
        $payment_options['instance_id']= $this->instance_id;
        return self::sendRequest("/api/request-external-payment",
            $payment_options);
    }
    public function process($payment_options)
    {
        $payment_options['instance_id']= $this->instance_id;
        return self::sendRequest("/api/process-external-payment",
            $payment_options);
    }
}
