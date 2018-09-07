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

class YandexModuleRedirectCardModuleFrontController extends ModuleFrontController
{
    public $display_header = true;
    public $display_column_left = true;
    public $display_column_right = false;
    public $display_footer = true;
    public $ssl = true;

    public function postProcess()
    {
        parent::postProcess();
        $this->log_on = Configuration::get('YA_KASSA_LOGGING_ON') == 'on';
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
        $total_to_pay = $cart->getOrderTotal(true);
        $rub_currency_id = Currency::getIdByIsoCode('RUB');
        if ($cart->id_currency != $rub_currency_id) {
            $from_currency = new Currency($cart->id_currency);
            $to_currency = new Currency($rub_currency_id);
            $total_to_pay = Tools::convertPriceFull($total_to_pay, $from_currency, $to_currency);
        }

        $total_to_pay = number_format($total_to_pay, 2, '.', '');
        $this->module->payment_status = false;
        $code = Tools::getValue('code');
        $cnf = Tools::getValue('cnf');
        if (empty($code)) {
            Tools::redirect('index.php?controller=order&step=3');
        } elseif (!empty($code) && $cnf) {
            $comment = $message = $this->module->l('Total:').$total_to_pay.$this->module->l(' rub');
            $response = YandexModuleExternalPayment::getInstanceId(Configuration::get('YA_WALLET_APPLICATION_ID'));
            if ($response->status == "success") {
                if ($this->log_on) {
                    $this->module->log(
                        'info',
                        'card_redirect:  '.$this->module->l('Get instance success') . print_r($response, true)
                    );
                }
                $instance_id = $response->instance_id;
                $external_payment = new YandexModuleExternalPayment($instance_id);
                $payment_options = array(
                    "pattern_id" => "p2p",
                    "to" => Configuration::get('YA_WALLET_ACCOUNT_ID'),
                    "amount_due" => $total_to_pay,
                    "comment" => trim($comment),
                    "message" => trim($message),
                    "label" => $this->context->cart->id,
                    // "test_payment" => true,
                    // "test_card" => 'available',
                    // "test_result" => 'in_progress',
                );
                $response = $external_payment->request($payment_options);
                if ($response->status == "success") {
                    if ($this->log_on) {
                        $this->module->log('info', 'card_redirect:  '.$this->module->l('Request success'));
                    }
                    $request_id = $response->request_id;
                    $this->context->cookie->ya_encrypt_CRequestId
                        = urlencode($this->module->getCipher()->encrypt($request_id));
                    $this->context->cookie->ya_encrypt_CInstanceId
                        = urlencode($this->module->getCipher()->encrypt($instance_id));
                    $this->context->cookie->write();
                    
                    do {
                        $process_options = array(
                            "request_id" => $request_id,
                            'ext_auth_success_uri' => $this->context->link->getModuleLink(
                                'yandexmodule',
                                'paymentcard',
                                array(),
                                true
                            ),
                            'ext_auth_fail_uri' => $this->context->link->getModuleLink(
                                'yandexmodule',
                                'paymentcard',
                                array(),
                                true
                            )
                        );
                        
                        $result = $external_payment->process($process_options);
                        if ($result->status == "in_progress") {
                            sleep(1);
                        }
                    } while ($result->status == "in_progress");

                    if ($result->status == 'success') {
                        $this->updateStatus($result);
                        $this->error = false;
                    } elseif ($result->status == 'ext_auth_required') {
                        $url = sprintf("%s?%s", $result->acs_uri, http_build_query($result->acs_params));
                        if ($this->log_on) {
                            $this->module->log('info', 'card_redirect: request '.print_r($process_options, true));
                            $this->module->log('info', 'card_redirect: response '.print_r($result, true));
                            $this->module->log('info', 'card_redirect:  '.$this->module->l('Redirect to').' '.$url);
                        }
                        Tools::redirect($url, '');
                        exit;
                    } elseif ($result->status == 'refused') {
                        $this->errors[] = $this->module->descriptionError($result->error)
                            ? $this->module->descriptionError($result->error) : $result->error;
                        if ($this->log_on) {
                            $this->module->log(
                                'info',
                                'card_redirect:refused '.$this->module->descriptionError($result->error)
                                ? $this->module->descriptionError($result->error) : $result->error
                            );
                        }
                        $this->module->payment_status = 102;
                    }
                }
            }
        }
    }
    
    public function updateStatus(&$resp)
    {
        $this->log_on = Configuration::get('YA_KASSA_LOGGING_ON') == 'on';
        if ($resp->status == 'success') {
            $cart = $this->context->cart;
            if ($cart->orderExists()) {
                $ord = new Order((int)Order::getOrderByCartId($cart->id));
            } else {
                if ($this->module->validateOrder(
                    $cart->id,
                    _PS_OS_PREPARATION_,
                    $cart->getOrderTotal(true, Cart::BOTH),
                    $this->module->displayName." Банковская карта",
                    null,
                    array(),
                    null,
                    false,
                    $cart->secure_key
                )) {
                    $ord = new Order((int)$this->module->currentOrder);
                } else {
                    $ord = false;
                }
            }

            if ($ord) {
                $history = new OrderHistory();
                $history->id_order = $ord->id;
                $history->changeIdOrderState(Configuration::get('PS_OS_PAYMENT'), $ord->id);
                $history->addWithemail(true);
            }
            if ($this->log_on) {
                $this->module->log(
                    'info',
                    'payment_card: #'.$this->module->currentOrder.' '.$this->module->l('Order success')
                );
            }
            Tools::redirect(
                $this->context->link->getPageLink('order-confirmation').'&id_cart='
                .$this->context->cart->id.'&id_module='.$this->module->id
                .'&id_order='.$this->module->currentOrder.'&key='
                .$this->context->cart->secure_key
            );
        }
    }

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

        $this->setTemplate('card.tpl');
    }
}
