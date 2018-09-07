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

require_once __DIR__ . "/YandexModuleBaseApi.php";
require_once __DIR__ . "/YandexModuleRequests.php";

class YandexModuleApi extends YandexModuleBaseApi
{
    public function __construct($access_token)
    {
        $this->access_token = $access_token;
    }
    public function sendAuthenticatedRequest($url, $options=array())
    {
        $this->checkToken();
        return self::sendRequest($url, $options, $this->access_token);
    }
    public function checkToken()
    {
        if ($this->access_token == null) {
            throw new \Exception("obtain access_token first");
        }
    }
    public function accountInfo()
    {
        return $this->sendAuthenticatedRequest("/api/account-info");
    }
    public function getAuxToken($scope)
    {
        return $this->sendAuthenticatedRequest("/api/token-aux", array(
            "scope" => implode(" ", $scope)
        ));
    }
    public function operationHistory($options=null)
    {
        return $this->sendAuthenticatedRequest("/api/operation-history", $options);
    }
    public function operationDetails($operation_id)
    {
        return $this->sendAuthenticatedRequest("/api/operation-details",
            array("operation_id" => $operation_id)
        );
    }
    public function requestPayment($options)
    {
        return $this->sendAuthenticatedRequest("/api/request-payment", $options);
    }
    public function processPayment($options)
    {
        return $this->sendAuthenticatedRequest("/api/process-payment", $options);
    }
    public function incomingTransferAccept($operation_id, $protection_code=null)
    {
        return $this->sendAuthenticatedRequest("/api/incoming-transfer-accept",
            array(
                "operation_id" => $operation_id,
                "protection_code" => $protection_code
            ));
    }
    public function incomingTransferReject($operation_id)
    {
        return $this->sendAuthenticatedRequest("/api/incoming-transfer-reject",
            array(
                "operation_id" => $operation_id,
            ));
    }
    public static function buildObtainTokenUrl($client_id, $redirect_uri, $scope)
    {
        $params = sprintf(
            "client_id=%s&response_type=%s&redirect_uri=%s&scope=%s",
            $client_id, "code", urlencode($redirect_uri), implode(" ", $scope)
        );
        return sprintf("%s/oauth/authorize?%s", self::SP_MONEY_URL, $params);
    }
    public static function getAccessToken($client_id, $code, $redirect_uri, $client_secret=null)
    {
        $full_url = self::SP_MONEY_URL . "/oauth/token";
        $result = \YandexModuleRequests::post($full_url, array(), array(
            "code" => $code,
            "client_id" => $client_id,
            "grant_type" => "authorization_code",
            "redirect_uri" => $redirect_uri,
            "client_secret" => $client_secret
        ));
        return self::processResult($result);
    }
    public static function revokeToken($token, $revoke_all=false)
    {
        return self::sendRequest("/api/revoke", array(
            "revoke-all" => $revoke_all,
        ), $token);
    }
}
