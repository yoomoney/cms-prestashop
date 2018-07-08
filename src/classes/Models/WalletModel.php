<?php
/**
 * @author Yandex.Money <cms@yamoney.ru>
 * @copyright © 2015-2017 NBCO Yandex.Money LLC
 * @license  https://money.yandex.ru/doc.xml?id=527052
 */

namespace YandexMoneyModule\Models;

use Configuration;
use Tools;

class WalletModel extends AbstractPaymentModel
{
    private $accountId;
    private $password;
    private $minAmount;
    private $orderStatus;

    public function initConfiguration()
    {
        $this->enabled = Configuration::get('YA_WALLET_ACTIVE') == '1';
        $this->accountId = Configuration::get('YA_WALLET_ACCOUNT_ID');
        $this->applicationId = Configuration::get('YA_WALLET_APPLICATION_ID');
        $this->password = Configuration::get('YA_WALLET_PASSWORD');
        $this->minAmount = Configuration::get('YA_WALLET_MIN_AMOUNT');
        $this->orderStatus = Configuration::get('YA_WALLET_END_STATUS');
        $this->paymentActionController = 'redirectwallet';
    }

    public function getAccountId()
    {
        return $this->accountId;
    }

    public function getApplicationId()
    {
        return $this->applicationId;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getMinimumAmount()
    {
        return $this->minAmount;
    }

    /**
     * @return mixed
     */
    public function getOrderStatus()
    {
        return $this->orderStatus;
    }

    public function validateOptions()
    {
        $errors = '';

        $this->enabled = Tools::getValue('YA_WALLET_ACTIVE') == '1';
        Configuration::UpdateValue('YA_WALLET_ACTIVE', $this->enabled ? 1 : 0);

        if ($this->enabled) {
            Configuration::UpdateValue('YA_KASSA_ACTIVE', 0);
            Configuration::UpdateValue('YA_BILLING_ACTIVE', 0);
        }

        if (trim(Tools::getValue('YA_WALLET_ACCOUNT_ID')) == '') {
            $errors .= $this->module->displayError($this->module->l('Account not specified!'));
        } else {
            $this->accountId = trim(Tools::getValue('YA_WALLET_ACCOUNT_ID'));
            Configuration::UpdateValue('YA_WALLET_ACCOUNT_ID', $this->accountId);
        }

        if (trim(Tools::getValue('YA_WALLET_PASSWORD')) == '') {
            $errors .= $this->module->displayError($this->module->l('Password not specified!'));
        } else {
            $this->password = trim(Tools::getValue('YA_WALLET_PASSWORD'));
            Configuration::UpdateValue('YA_WALLET_PASSWORD', $this->password);
        }
        Configuration::UpdateValue('YA_WALLET_LOGGING_ON', Tools::getValue('YA_WALLET_LOGGING_ON'));

        $this->minAmount = (float)Tools::getValue('YA_WALLET_MIN_AMOUNT');
        Configuration::UpdateValue('YA_WALLET_MIN_AMOUNT', $this->minAmount);

        $this->orderStatus = Tools::getValue('YA_WALLET_END_STATUS');
        Configuration::UpdateValue('YA_WALLET_END_STATUS', $this->orderStatus);

        if ($errors == '') {
            $errors = $this->module->displayConfirmation($this->module->l('Settings saved successfully!'));
        }

        return $errors;
    }

    /**
     * @param \Smarty $smarty
     * @return null|string
     */
    public function assignVariables($smarty)
    {
        $template = 'module:yandexmodule/views/templates/hook/1.7/wallet_form.tpl';
        $smarty->assign('label', $this->module->l('Выберите способ оплаты'));
        return $template;
    }
}
