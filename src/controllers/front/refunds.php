<?php
/**
 * @author YooMoney <cms@yoomoney.ru>
 * @copyright © 2020 "YooMoney", NBСO LLC
 * @license  https://yoomoney.ru/doc.xml?id=527052
 */

class YooMoneyModuleRefundsModuleFrontController extends ModuleFrontController
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
