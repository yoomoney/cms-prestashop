<?php
/**
 * @author Yandex.Money <cms@yamoney.ru>
 * @copyright © 2015-2017 NBCO Yandex.Money LLC
 * @license  https://money.yandex.ru/doc.xml?id=527052
 */

class YandexModuleRefundsModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        if (!$this->module->active) {
            return;
        }

        parent::initContent();

        $this->setTemplate('error.tpl');
    }
}
