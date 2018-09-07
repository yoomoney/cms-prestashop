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

class YandexModulePaymentCardModuleFrontController extends ModuleFrontController
{
    public $display_header = true;
    public $display_column_left = true;
    public $display_column_right = false;
    public $display_footer = true;
    public $ssl = true;
    public $errors;

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

        $this->module->payment_status = false;
        $requestId = $this->module->getCipher()->decrypt(urldecode($this->context->cookie->ya_encrypt_CRequestId));
        $instance_id = $this->module->getCipher()->decrypt(urldecode($this->context->cookie->ya_encrypt_CInstanceId));
        if ($this->log_on) {
            $this->module->log('info', 'payment_card: cookie_requestId'.print_r($requestId, true));
        }
        $res = new stdClass();
        $res->status = Tools::getValue('status');
        $res->error = Tools::getValue('reason');
        if ($this->log_on) {
            $this->module->log('info', "Success returnUrl: ".print_r($res, true));
        }
        if (!empty($requestId)) {
            if ($res->status == 'success') {
                //TODO Отправить повторный
                ///*
                $external_payment = new YandexModuleExternalPayment($instance_id);
                do {
                    $process_options = array(
                        "request_id" => $requestId,
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
                    if ($this->log_on) {
                        $this->module->log('info', 'final card_redirect: process '.print_r($result, true));
                    }
                } while ($result->status == "in_progress");
                if ($result->status == 'success') {
                    if ($this->log_on) {
                        $this->module->log('info', 'final card_redirect:success');
                    }
                    $this->updateStatus($result);
                    $this->error = false;
                } elseif ($result->status == 'ext_auth_required') {
                    $url = sprintf("%s?%s", $result->acs_uri, http_build_query($result->acs_params));
                    if ($this->log_on) {
                        $this->module->log('info', 'final card_redirect: request ' . print_r($process_options, true));
                        $this->module->log('info', 'final card_redirect: response ' . print_r($result, true));
                        $this->module->log(
                            'info',
                            'final card_redirect:  ' . $this->module->l('Redirect to') . ' ' . $url
                        );
                    }
                    Tools::redirect($url, '');
                    exit;
                } elseif ($result->status == 'refused') {
                    $this->errors[] = $this->module->descriptionError($result->error)
                        ? $this->module->descriptionError($result->error) : $result->error;
                    if ($this->log_on) {
                        $this->module->log(
                            'info',
                            'final card_redirect:refused ' . $this->module->descriptionError($result->error)
                                ? $this->module->descriptionError($result->error) : $result->error
                        );
                    }
                    $this->module->payment_status = 102;
                }
            } else {
                $this->error = true;
                $this->errors[] = $this->module->descriptionError($res->error);
            }
        } else {
            $this->errors[] = $this->module->l('Not received the correct data from Yandex.Money');
            if ($this->log_on) {
                $this->module->log('info', 'payment_card: Error '.$this->module->l('Invalid send data'));
            }
            return;
        }
    }

    public function updateStatus(&$resp)
    {
        $this->log_on = Configuration::get('YA_KASSA_LOGGING_ON') == 'on';
        if ($resp->status == 'success') {
            $cart = $this->context->cart;
            $link = $this->context->link->getPageLink('order-confirmation').'&id_cart='
                .$cart->id.'&id_module='.$this->module->id.'&id_order='
                .$this->module->currentOrder.'&key='.$cart->secure_key;

            if ($cart->id > 0) {
                if (!$cart->orderExists()) {
                    $this->module->validateOrder(
                        $cart->id,
                        Configuration::get('PS_OS_PAYMENT'),
                        $cart->getOrderTotal(true, Cart::BOTH),
                        $this->module->displayName." Банковская карта",
                        null,
                        array(),
                        null,
                        false,
                        $cart->secure_key
                    );
                }
                if ($this->log_on) {
                    $this->module->log(
                        'info',
                        'payment_card: #'.$this->module->currentOrder.' '.$this->module->l('Order success')
                    );
                }
                Tools::redirect($link);
            }
        }
    }


    public function initContent()
    {
        parent::initContent();
        $cart = $this->context->cart;
        $this->context->smarty->assign(array(
            'payment_status' => $this->module->payment_status,
            'nbProducts' => $cart->nbProducts(),
            'this_path' => $this->module->getPathUri(),
            'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/',
            'errors' => $this->errors
        ));

        $this->setTemplate('error.tpl');
    }
}
