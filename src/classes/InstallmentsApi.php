<?php


namespace YandexMoneyModule;


class InstallmentsApi
{
    private static $lastError;

    public static function creditPreSchedule($shopId, $orderSum)
    {
        $url = 'https://money.yandex.ru/credit/order/ajax/credit-pre-schedule?'.http_build_query(array(
                'shopId' => $shopId,
                'sum'    => $orderSum,
            ));

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($curl);

        if (curl_error($curl)) {
            self::$lastError = 'Error while getting response: '.curl_error($curl).'. Curl error NO: '.curl_errno($curl);
        }

        $result = json_decode($response, true);

        if (!$result) {
            self::$lastError = 'Error while parsing response: '.json_last_error_msg().'. Error code: '.json_last_error();
        }

        return $result;
    }

    public static function getLastError()
    {
        return self::$lastError;
    }
}