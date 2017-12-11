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

class YandexModuleGenerateModuleFrontController extends ModuleFrontController
{
    public $display_header = false;
    public $display_column_left = false;
    public $display_column_right = false;
    public $display_footer = false;
    public $ssl = false;

    public function postProcess()
    {
        parent::postProcess();
        if (Tools::getValue('cron') == 1) {
            $this->module->getMarketModel()->generateXML(true);
            die('OK');
        } else {
            $this->module->getMarketModel()->generateXML(false);
        }
    }
}
