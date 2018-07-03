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
class YandexModuleRedirectWalletModuleFrontController extends ModuleFrontController
{
    public $display_header = true;
    public $display_column_left = true;
    public $display_column_right = false;
    public $display_footer = true;
    public $ssl = true;
    public $error;

    public function postProcess()
    {
        parent::postProcess();

        $cart        = $this->context->cart;
        $totalAmount = $cart->getOrderTotal(true);
        $type        = Tools::getValue('type');

        if ($type == 'wallet') {
            $paymentType = 'PC';
        } elseif ($type == 'card') {
            $paymentType = 'AC';
        } else {
            Tools::redirect('index.php?controller=order&step=3');
        }

        $result = $this->module->validateOrder(
            (int)$this->context->cart->id,
            Configuration::get('PS_OS_PREPARATION'),
            $this->context->cart->getOrderTotal(true, Cart::BOTH),
            'Yandex.Деньги',
            null,
            array(),
            null,
            false,
            $this->context->cart->secure_key
        );

        if ($result) {
            $link  = $this->context->link->getPageLink('order-confirmation').'&id_cart='
                     .$cart->id.'&id_module='.$this->module->id.'&id_order='
                     .$this->module->currentOrder.'&key='.$cart->secure_key;
            $paymentTarget = $this->module->l('Payment for Order N. '.(int)$this->module->currentOrder);
            $this->context->smarty->assign(array(
                'receiver'     => Configuration::get('YA_WALLET_ACCOUNT_ID'),
                'amount'       => $totalAmount,
                'paymentType'  => $paymentType,
                'formcomment'  => '',
                'short-dest'   => '',
                'targets'      => $paymentTarget,
                'orderId'      => (int)$this->module->currentOrder,
                'comment'      => '',
                'need-fio'     => '',
                'need-email'   => '',
                'need-address' => '',
                'successURL'   => $link,
            ));

            if (version_compare(_PS_VERSION_, '1.7.0') < 0) {
                $this->setTemplate('wallet_redirect.tpl');
            } else {
                $this->setTemplate('module:yandexmodule/views/templates/front/wallet_redirect_17.tpl');
            }
        } else {
            Tools::redirect('index.php?controller=order&step=3');
        }
    }
}
