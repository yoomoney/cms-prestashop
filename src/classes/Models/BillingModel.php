<?php
/**
 * @author Yandex.Money <cms@yamoney.ru>
 * @copyright © 2015-2017 NBCO Yandex.Money LLC
 * @license  https://money.yandex.ru/doc.xml?id=527052
 */

namespace YandexMoneyModule\Models;

use Configuration;
use Context;
use Customer;
use Tools;
use Validate;

class BillingModel extends AbstractPaymentModel
{
    private $formId;
    private $purpose;
    private $orderStatus;

    public function initConfiguration()
    {
        $this->enabled = Configuration::get('YA_BILLING_ACTIVE') == '1';
        $this->formId = Configuration::get('YA_BILLING_ID');
        $this->purpose = Configuration::get('YA_BILLING_PURPOSE');
        $this->orderStatus = Configuration::get('YA_BILLING_END_STATUS');

        $this->paymentActionController = 'redirectbilling';
    }

    public function getFormId()
    {
        return $this->formId;
    }

    public function getPurpose()
    {
        return $this->purpose;
    }

    public function getOrderStatus()
    {
        return $this->orderStatus;
    }

    public function validateOptions()
    {
        $errors = '';

        $this->enabled = Tools::getValue('YA_BILLING_ACTIVE') == '1';
        Configuration::UpdateValue('YA_BILLING_ACTIVE', $this->enabled ? 1 : 0);

        if ($this->enabled) {
            Configuration::UpdateValue('YA_KASSA_ACTIVE', 0);
            Configuration::UpdateValue('YA_WALLET_ACTIVE', 0);
        }

        if (trim(Tools::getValue('YA_BILLING_ID')) == '') {
            $errors .= $this->module->displayError($this->module->l('Form id not specified!'));
        } else {
            $this->formId = trim(Tools::getValue('YA_BILLING_ID'));
            Configuration::UpdateValue('YA_BILLING_ID', $this->formId);
        }

        if (trim(Tools::getValue('YA_BILLING_PURPOSE')) == '') {
            $errors .= $this->module->displayError($this->module->l('Purpose not specified!'));
        } else {
            $this->purpose = trim(Tools::getValue('YA_BILLING_PURPOSE'));
            Configuration::UpdateValue('YA_BILLING_PURPOSE', $this->purpose);
        }

        $this->orderStatus = Tools::getValue('YA_BILLING_END_STATUS');
        Configuration::UpdateValue('YA_BILLING_END_STATUS', $this->orderStatus);

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
        $cart = Context::getContext()->cart;

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $fio = $customer->lastname . ' ' . $customer->firstname;
        if (isset($customer->middlename)) {
            $fio .= ' ' . $customer->middlename;
        }
        $smarty->assign('fio', $fio);
        return 'module:yandexmodule/views/templates/hook/1.7/billing_form.tpl';
    }
}
