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

use YandexMoneyModule\Partner;

class YandexModuleMarketOrdersModuleFrontController extends ModuleFrontController
{
    public $display_header = false;
    public $display_column_left = false;
    public $display_column_right = false;
    public $display_footer = false;
    public $ssl = true;

    public function postProcess()
    {
        parent::postProcess();
        $type = Tools::getValue('type');
        $func = Tools::getValue('func');
        $arr = array($type, $func);
        $arr = array_merge($arr, $_REQUEST);
        $dd = serialize($arr);
        $this->module->log('info', 'market_orders '.$dd);
        $key = Tools::getValue('auth-token');
        $sign = Configuration::get('YA_MARKET_ORDERS_TOKEN');
        if (Tools::strtoupper($sign) != Tools::strtoupper($key)) {
            header('HTTP/1.1 403 Forbidden');
            header('Status: 403 Forbidden');
            echo '<h1>Wrong token</h1>';
            exit;
        } else {
            $json = Tools::file_get_contents("php://input");
            $this->module->log('info', 'market_orders'.$json);
            if (!$json) {
                header('HTTP/1.1 403 Forbidden');
                header('Status: 403 Forbidden');
                echo '<h1>No data posted</h1>';
                exit;
            } else {
                header('Content-type:application/json;  charset=utf-8');
                $partner = new Partner();
                $data = Tools::jsonDecode($json);
                if ($type == 'cart') {
                    $partner->requestItems($data);
                } elseif ($type == 'order') {
                    if ($func == 'accept') {
                        $partner->orderAccept($data);
                    } elseif ($func == 'status') {
                        $partner->alertOrderStatus($data);
                    }
                } else {
                    header('HTTP/1.0 404 Not Found');
                    echo '<h1>Wrong controller</h1>';
                    exit;
                }
            }
        }
    }
}
