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

use YandexMoneyModule\Metrics;

require_once(dirname(__FILE__) . '/../../config/config.inc.php');
require_once(dirname(__FILE__) . '/../../init.php');
require_once(dirname(__FILE__) . '/yandexmodule.php');

$module = new yandexmodule();

$m = new Metrics();
$code = Tools::getValue('code');
$error = Tools::getValue('error');
$state = base64_decode(Tools::getValue('state'));
$response = $m->run();
if ($error == '') {
    $state = explode('_', $module->getCipher()->decrypt($state));
    $m->code = $code;
    $m->getToken();
    if ($m->editCounter()) {
        $counter = $m->getCounter();
        if (!empty($counter->counter->code)) {
            Configuration::UpdateValue('YA_METRICS_CODE', $counter->counter->code, true);
        }
    }
    Tools::redirect(
        _PS_BASE_URL_.__PS_BASE_URI__.$state[0].'/?controller=AdminModules'
        .($m->errors ? '&error='.$module->getCipher()->encrypt($m->errors) : '')
        .'&configure=yandexmodule&tab_module=payments_gateways&module_name=yandexmodule&token='
        .Tools::getAdminToken('AdminModules'.(int)Tab::getIdFromClassName('AdminModules').(int)$state[1])
    );
} else {
    die('error #'.$error.' error description: '.Tools::getValue('error_description'));
}
