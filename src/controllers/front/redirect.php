<?php
/**
 * Module is prohibited to sales! Violation of this condition leads to the deprivation of the license!
 *
 * @author    YooMoney <cms@yoomoney.ru>
 * @copyright © 2020 "YooMoney", NBСO LLC
 * @license   https://yoomoney.ru/doc.xml?id=527052
 *
 * @category  Front Office Features
 * @package   YooMoney Payment Solution
 */

class YooMoneyModuleRedirectModuleFrontController extends ModuleFrontController
{
    public $display_header = true;
    public $display_column_left = true;
    public $display_column_right = false;
    public $display_footer = true;
    public $ssl = true;

    public function initContent()
    {
        parent::initContent();
        $cart = $this->context->cart;
        $this->context->smarty->assign(array(
            'payment_link' => '',
            'nbProducts' => $cart->nbProducts(),
            'cust_currency' => $cart->id_currency,
            'currencies' => $this->module->getCurrency((int)$cart->id_currency),
            'total' => $cart->getOrderTotal(true, Cart::BOTH),
            'this_path' => $this->module->getPathUri(),
            'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/'
        ));

        $this->setTemplate('redirect.tpl');
    }

    public function postProcess()
    {
        parent::postProcess();
        $cart = $this->context->cart;
        if ($cart->id_customer == 0
            || $cart->id_address_delivery == 0
            || $cart->id_address_invoice == 0
            || !$this->module->active
        ) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }
                
        $this->myCart=$this->context->cart;
        $this->module->payment_status = '';

    }
}
