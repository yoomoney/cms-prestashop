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
use YandexCheckout\Model\Confirmation\ConfirmationRedirect;
use YandexCheckout\Model\PaymentMethodType;

/**
 * Class YandexModulePaymentKassaModuleFrontController
 *
 * @property yandexmodule $module
 */
class YandexModulePaymentKassaModuleFrontController extends ModuleFrontController
{
    public $display_header = true;
    public $display_column_left = true;
    public $display_column_right = false;
    public $display_footer = true;
    public $ssl = true;

    public function postProcess()
    {
        $cart = $this->context->cart;
        if ($cart->id_customer <= 0) {
            $this->errorRedirect('empty customer id');
        }
        if ($cart->id_address_delivery <= 0) {
            $this->errorRedirect('delivery address not specified');
        }
        if ($cart->id_address_invoice <= 0) {
            $this->errorRedirect('invoice address not specified');
        }
        if (!$this->module->active) {
            $this->errorRedirect('module not active');
        }

        // Check that this payment option is still available in case the customer changed his address just before the
        // end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'yandexmodule') {
                $authorized = true;
                break;
            }
        }
        if (!$authorized) {
            $this->module->log('info', 'Payment page error: payment module not available');
            die($this->module->l('This payment method is not available.'));
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            $this->errorRedirect('customer with id#'.$cart->id_customer.' not exists');
        }

        $kassa = $this->module->getKassaModel();

        $paymentMethod = Tools::getValue('payment_method', '');
        if (empty($paymentMethod)) {
            if (!$kassa->getEPL()) {
                $this->errorRedirect($this->module->l('payment is empty, but epl disabled'),
                    'index.php?controller=order&step=3');
            }
        } else {
            $paymentMethodInfo = $kassa->getPaymentMethodInfo($paymentMethod);
            if ($paymentMethod === PaymentMethodType::ALFABANK) {
                $login = trim(Tools::getValue('alfaLogin'));
                if (empty($login)) {
                    $this->errorRedirect($this->module->l('Alfa login is empty'), 'index.php?controller=order&step=3');
                }
            }
            if ($paymentMethod === PaymentMethodType::QIWI) {
                $phone = preg_replace('/[^\d]+/', '', Tools::getValue('qiwiPhone'));
                if (empty($phone)) {
                    $this->errorRedirect($this->module->l('Qiwi phone is empty'), 'index.php?controller=order&step=3');
                }
            }
        }

        $currency = $this->context->currency;
        $total    = (float)$cart->getOrderTotal(true, Cart::BOTH);
        if (isset($paymentMethodInfo)) {
            $paymentLabel          = $paymentMethodInfo['value'] == PaymentMethodType::INSTALLMENTS
                ? $this->module->l('Installments')
                : $paymentMethodInfo['name'];
            $paymentMethodInfoName = ': '.$paymentLabel;
        } else {
            $paymentMethodInfoName = '';
        }


        $isOrderValid = $this->module->validateOrder(
            $cart->id,
            $kassa->getCreateStatusId(),
            $total,
            $this->module->l('Payment via Yandex.Checkout')
            .$paymentMethodInfoName,
            null,
            null,
            (int)$currency->id,
            false,
            $customer->secure_key
        );
        if (!$isOrderValid) {
            $this->errorRedirect($this->module->l('Failed to validate order'), 'index.php?controller=order&step=1');
        }

        $returnUrl = 'index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id
                     .'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key;
        $payment   = $kassa->createPayment(
            $this->context,
            $cart,
            $paymentMethod,
            Tools::getShopDomain(true).__PS_BASE_URI__.$returnUrl
        );
        $errorUrl  = 'index.php?controller=order&submitReorder=&id_order='.$this->module->currentOrder;
        if ($payment === null) {
            $this->errorRedirect('payment creation failed', $errorUrl);
        }

        $confirmation = $payment->getConfirmation();
        if ($confirmation instanceof ConfirmationRedirect) {
            $this->module->log('info', 'Redirect user to payment page '.$confirmation->getConfirmationUrl());
            Tools::redirect($confirmation->getConfirmationUrl());
        } else {
            $this->module->log('info', 'Redirect user to confirmation page '.$returnUrl);
            Tools::redirect($returnUrl);
        }
    }

    private function errorRedirect($message, $link = null)
    {
        $this->module->log('info', 'Redirect from payment page: '.$message);
        if ($link === null) {
            $link = 'index.php?controller=order&step=1';
        }
        Tools::redirect($link);
    }
}
