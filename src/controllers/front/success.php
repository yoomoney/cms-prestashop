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

class YandexModuleSuccessModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $module;

    public function initContent()
    {
        parent::initContent();
        $log_on = Configuration::get('YA_ORG_LOGGING_ON');
        if (Tools::getValue('label')) {
            $data = explode('_', Tools::getValue('label'));
        } else {
            $data = explode('_', Tools::getValue('customerNumber'));
        }
        if (!empty($data) && isset($data[1])) {
            $ordernumber = $data['1'];
            $this->context->smarty->assign('ordernumber', $ordernumber);
            $this->context->smarty->assign('time', date('Y-m-d H:i:s '));
            if (!$ordernumber) {
                if ($log_on) {
                    $this->module->log(
                        'info',
                        'yakassa_success: Error ' . $this->module->l('Cart number is not specified')
                    );
                }
                $this->setTemplate('error.tpl');
            } else {
                $cart = new Cart((int)$ordernumber);
                $qty = $cart->nbProducts();
                $this->context->smarty->assign('nbProducts', $qty);
                if (!Validate::isLoadedObject($cart) || $qty < 1) {
                    if ($log_on) {
                        $this->module->log(
                            'info',
                            'yakassa_success: Error '.$this->module->l('Shopping cart does not exist')
                        );
                    }
                    $this->setTemplate('error.tpl');
                } else {
                    $ordernumber = (int) $cart->id;
                    if (!$ordernumber) {
                        if ($log_on) {
                            $this->module->log(
                                'info',
                                'yakassa_success: Error '.$this->module->l('Order number is not specified')
                            );
                        }
                        $this->setTemplate('error.tpl');
                    } else {
                        $order = new Order((int)Order::getOrderByCartId($cart->id));
                        $customer = new Customer((int)$order->id_customer);
                        if ($order->hasBeenPaid()) {
                            if ($log_on) {
                                $this->module->log(
                                    'info',
                                    'yakassa_success: #'.$order->id.' '.$this->module->l('Order paid')
                                );
                            }
                            Tools::redirectLink(
                                __PS_BASE_URI__.'order-confirmation.php?key='.$customer->secure_key
                                .'&id_cart='.(int)$order->id_cart.'&id_module='
                                .(int)$this->module->id.'&id_order='.(int)$order->id
                            );
                        } else {
                            if ($log_on) {
                                $this->module->log(
                                    'info',
                                    'yakassa_success: #'.$order->id.' '.$this->module->l('Order wait payment')
                                );
                            }
                            $this->setTemplate('waitingPayment.tpl');
                        }
                    }
                }
            }
        } else {
            if ($log_on) {
                $this->module->log('info', 'yakassa_success: Error '.$this->module->l('Cart number is not specified'));
            }
            $this->setTemplate('error.tpl');
        }
    }
}
