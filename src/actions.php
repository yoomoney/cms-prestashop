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

require_once(dirname(__FILE__).'/../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../init.php');
require_once(dirname(__FILE__).'/yandexmodule.php');
$module = new yandexmodule();
$return = array();
if (Tools::GetValue('action')) {
    $action = Tools::GetValue('action');
    $token = Tools::encrypt('AdminOrders'.(int)Tab::getIdFromClassName('AdminOrders').(int)Tools::getValue('idm'));
    if (strcasecmp($token, Tools::getValue('tkn')) == 0) {
        switch ($action) {
            case 'load_price':
                $return = $module->processLoadPrice();
                break;
            case 'change_order':
                $return = $module->processChangeCarrier();
                break;
        }
    } else {
        $return['errors'] = $module->l('Invalid Security Token !');
    }
    die(Tools::jsonEncode($return));
}
