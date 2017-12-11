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

class YandexModule extends PaymentModule
{
    private $p2p_status = '';
    private $org_status = '';
    private $market_status = '';
    private $metrics_status = '';
    private $market_orders_status = '';
    private $billing_status = '';
    private $metrika_valid;
    private $update_status;

    /**
     * @var YandexMoneyModule\Models\KassaModel
     */
    private $kassaModel;

    /**
     * @var \YandexMoneyModule\Models\WalletModel
     */
    private $walletModel;

    /**
     * @var \YandexMoneyModule\Models\BillingModel
     */
    private $billingModel;

    /**
     * @var YandexMoneyModule\Models\OrderModel
     */
    private $orderModel;

    /**
     * @var YandexMoneyModule\Models\MarketModel
     */
    private $marketModel;

    /**
     * @var YandexMoneyModule\Models\MetricsModel
     */
    private $metricsModel;

    /**
     * @var PhpEncryptionEngineCore
     */
    private $cipher;

    public $status = array(
        'DELIVERY' => 1900,
        'CANCELLED' => 1901,
        'PICKUP' => 1902,
        'PROCESSING' => 1903,
        'DELIVERED' => 1904,
        'UNPAID' => 1905,
        'RESERVATION_EXPIRED' => 1906,
        'RESERVATION' => 1907
    );

    private static $moduleRoutes = array(
        'market_orders_cart' => array(
            'controller' => 'marketorders',
            'rule' =>  'yandexmodule/{controller}/{type}',
            'keywords' => array(
                'type'   => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'type'),
                'module'  => array('regexp' => '[\w]+', 'param' => 'module'),
                'controller' => array('regexp' => '[\w]+',  'param' => 'controller')
            ),
            'params' => array(
                'fc' => 'module',
                'module' => 'yandexmodule',
                'controller' => 'marketorders'
            )
        ),
        'market_order' => array(
            'controller' => 'marketorders',
            'rule' =>  'yandexmodule/{controller}/{type}/{func}',
            'keywords' => array(
                'type'   => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'type'),
                'func'   => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'func'),
                'module'  => array('regexp' => '[\w]+', 'param' => 'module'),
                'controller' => array('regexp' => '[\w]+',  'param' => 'controller')
            ),
            'params' => array(
                'fc' => 'module',
                'module' => 'yandexmodule',
                'controller' => 'marketorders'
            )
        ),
        'generate_price' => array(
            'controller' => null,
            'rule' =>  'yandexmodule/{controller}',
            'keywords' => array(
                'controller' => array('regexp' => '[\w]+',  'param' => 'controller')
            ),
            'params' => array(
                'fc' => 'module',
                'module' => 'yandexmodule',
            )
        ),
    );

    public function hookModuleRoutes()
    {
        return self::$moduleRoutes;
    }

    public function __construct()
    {
        if (!defined('_PS_VERSION_')) {
            exit;
        }

        include_once(dirname(__FILE__) . '/lib/autoload.php');

        include_once(dirname(__FILE__).'/lib/YandexApi.php');
        include_once(dirname(__FILE__).'/lib/external_payment.php');

        $this->name = 'yandexmodule';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.1';
        $this->author = 'Яндекс.Деньги';
        $this->need_instance = 1;
        $this->bootstrap = 1;
        $this->module_key = 'f51f5c45095c7d4eec9d2266901d793e';
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Y.CMS 2.0 Prestashop');
        $this->description = $this->l(
            'Yandex.Money, Yandex.Service, Yandex.Metrika, Yandex.Market Orders in the Market'
        );
        $this->confirmUninstall = $this->l('Really uninstall the module?');
        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->l('There is no set currency for your module!');
        }
        $this->ps_versions_compliancy = array('min' => '1.6.0.0', 'max' => _PS_VERSION_);
    }

    public function getCipher()
    {
        if ($this->cipher === null) {
            if (version_compare(_PS_VERSION_, '1.7.0') > 0) {
                if (!Configuration::get('PS_CIPHER_ALGORITHM') || !defined('_RIJNDAEL_KEY_')) {
                    $this->cipher = new PhpEncryptionLegacyEngine(_COOKIE_KEY_, _COOKIE_IV_);
                } else {
                    $this->cipher = new PhpEncryptionLegacyEngine(_RIJNDAEL_KEY_, _RIJNDAEL_IV_);
                }
            } else {
                if (!Configuration::get('PS_CIPHER_ALGORITHM') || !defined('_RIJNDAEL_KEY_')) {
                    $this->cipher = new Blowfish(_COOKIE_KEY_, _COOKIE_IV_);
                } else {
                    $this->cipher = new Rijndael(_RIJNDAEL_KEY_, _RIJNDAEL_IV_);
                }
            }
        }
        return $this->cipher;
    }

    public function multiLangField($str)
    {
        $languages = Language::getLanguages(false);
        $data = array();
        foreach ($languages as $lang) {
            $data[$lang['id_lang']] = $str;
        }
        return $data;
    }

    /**
     * Метод вызываемый при установке модуля
     * @return bool True если модуль был установлен успешно, false если что-то
     * пошло не так
     */
    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        $hooks = array(
            'displayPaymentReturn',
            'displayFooter',
            'displayHeader',
            'ModuleRoutes',
            'displayOrderConfirmation',
            'displayAdminOrder',
            'actionOrderStatusUpdate',
        );
        if (version_compare(_PS_VERSION_, '1.7.0') > 0) {
            $hooks[] = 'paymentOptions';
        } else {
            $hooks[] = 'displayPayment';
        }
        foreach ($hooks as $hook) {
            if (!$this->registerHook($hook)) {
                $this->log('error', 'Failed to register "' . $hook . '" hook');
                return false;
            }
        }

        $installer = new \YandexMoneyModule\Installer($this);
        $installer->addDatabaseTables();
        $installer->addServiceCustomer();
        $installer->addOrderStatuses();
        return true;
    }

    /**
     * Метод вызываемый при удалении модуля
     * @return bool True если модуль был успешно удален, false если удалить
     * модуль не удалось
     */
    public function uninstall()
    {
        $installer = new \YandexMoneyModule\Installer($this);
        $installer->removeOrderStatuses($this->status);
        $installer->removeDatabaseTables();
        $installer->removeServiceCustomer();
        return parent::uninstall();
    }

    public function hookDisplayAdminOrder($params)
    {
        $ya_order_db = $this->getOrderModel()->getMarketOrderByOrderId($params['id_order']);
        $ht = '';
        if ($ya_order_db['id_market_order']) {
            $partner = new YandexMoneyModule\Partner();
            $ya_order = $partner->getOrder($ya_order_db['id_market_order']);
            if ($ya_order) {
                $array = array();
                $state = $ya_order->order->status;
                if ($state == 'PROCESSING') {
                    $array = array(
                        $this->status['RESERVATION_EXPIRED'],
                        $this->status['RESERVATION'],
                        $this->status['PROCESSING'],
                        $this->status['DELIVERED'],
                        $this->status['PICKUP'],
                        $this->status['UNPAID']
                    );
                } elseif ($state == 'DELIVERY') {
                    $array = array(
                        $this->status['RESERVATION_EXPIRED'],
                        $this->status['RESERVATION'],
                        $this->status['PROCESSING'],
                        $this->status['DELIVERY'],
                        $this->status['UNPAID']
                    );
                    if (!isset($ya_order->order->delivery->outletId)
                        || $ya_order->order->delivery->outletId < 1
                        || $ya_order->order->delivery->outletId == ''
                    ) {
                        $array[] = $this->status['PICKUP'];
                    }
                } elseif ($state == 'PICKUP') {
                    $array = array(
                        $this->status['RESERVATION_EXPIRED'],
                        $this->status['RESERVATION'],
                        $this->status['PROCESSING'],
                        $this->status['PICKUP'],
                        $this->status['DELIVERY'],
                        $this->status['UNPAID']
                    );
                } else {
                    $array = array(
                        $this->status['RESERVATION_EXPIRED'],
                        $this->status['RESERVATION'],
                        $this->status['PROCESSING'],
                        $this->status['DELIVERED'],
                        $this->status['PICKUP'],
                        $this->status['CANCELLED'],
                        $this->status['DELIVERY'],
                        $this->status['UNPAID']
                    );
                }
            }
        } else {
            $array = array(
                $this->status['RESERVATION_EXPIRED'],
                $this->status['RESERVATION'],
                $this->status['PROCESSING'],
                $this->status['DELIVERED'],
                $this->status['PICKUP'],
                $this->status['CANCELLED'],
                $this->status['DELIVERY'],
                $this->status['UNPAID']
            );
        }

        if (Tools::version_compare(_PS_VERSION_, '1.7.0') < 0) {
            $array = Tools::jsonEncode($array);
        } else {
            $array = json_encode($array);
        }
        $ht .= '<script type="text/javascript">
            $(document).ready(function(){
                var array = JSON.parse("'.$array.'");
                for(var k in array){
                    $("#id_order_state option[value="+ array[k] +"]").attr({disabled: "disabled"});
                };

                $("#id_order_state").trigger("chosen:updated");
            });
        </script>';

        // if(Configuration::get('YA_MARKET_ORDERS_SET_CHANGEC') && $ya_order->order->paymentType != 'PREPAID')
        if (Configuration::get('YA_MARKET_ORDERS_SET_CHANGEC')) {
            $ht .= $this->displayTabContent((int) $params['id_order']);
        }

        return $ht;
    }

    public function hookActionOrderStatusUpdate($params)
    {
        $new_os = $params['newOrderStatus'];
        $status_flip = array_flip($this->status);
        if (in_array($new_os->id, $this->status)) {
            $ya_order_db = $this->getOrderModel()->getMarketOrderByOrderId($params['id_order']);
            $id_ya_order = $ya_order_db['id_market_order'];
            if ($id_ya_order) {
                $partner = new YandexMoneyModule\Partner();
                $ya_order = $partner->getOrder($id_ya_order);
                $state = $ya_order->order->status;
                if ($state == 'PROCESSING'
                    && ($new_os->id == $this->status['DELIVERY']
                        || $new_os->id == $this->status['CANCELLED'])
                ) {
                    $partner->sendOrder($status_flip[$new_os->id], $id_ya_order);
                } elseif ($state == 'DELIVERY'
                    && ($new_os->id == $this->status['DELIVERED']
                        || $new_os->id == $this->status['PICKUP']
                        || $new_os->id == $this->status['CANCELLED'])
                ) {
                    $partner->sendOrder($status_flip[$new_os->id], $id_ya_order);
                } elseif ($state == 'PICKUP'
                    && ($new_os->id == $this->status['DELIVERED'] || $new_os->id == $this->status['CANCELLED'])
                ) {
                    $partner->sendOrder($status_flip[$new_os->id], $id_ya_order);
                } elseif ($state == 'RESERVATION_EXPIRED' || $state == 'RESERVATION') {
                    return false;
                } else {
                    return false;
                }
            }
        }
    }

    public function displayTabContent($id)
    {
        $partner = new YandexMoneyModule\Partner();
        $order_ya_db = $this->getOrderModel()->getMarketOrderByOrderId($id);
        $ht = '';
        if ($order_ya_db['id_market_order']) {
            $ya_order = $partner->getOrder($order_ya_db['id_market_order']);
            $types = unserialize(Configuration::get('YA_MARKET_ORDERS_CARRIER_SERIALIZE'));
            $state = $ya_order->order->status;
            $st = array('PROCESSING', 'DELIVERY', 'PICKUP');
            // Tools::d($ya_order);
            if (!in_array($state, $st)) {
                return false;
            }

            $this->context->controller->AddJS($this->_path.'/views/js/back.js');
            $this->context->controller->AddCss($this->_path.'/views/css/back.css');
            $order = new Order($id);
            $cart = new Cart($order->id_cart);
            if (Tools::version_compare(_PS_VERSION_, '1.7.0') < 0) {
                $carriers = $cart->simulateCarriersOutput();
            } else {
                $carriers = $cart->getDeliveryOptionList();
            }
            $ht = '';
            $i = 1;
            $tmp = array();
            $tmp[0]['id_carrier'] = 0;
            $tmp[0]['name'] = $this->l('-- Please select carrier --');
            foreach ($carriers as $c) {
                $id = str_replace(',', '', Cart::desintifier($c['id_carrier']));
                $type = isset($types[$id]) ? $types[$id] : 'POST';
                if (!Configuration::get('YA_MARKET_SET_ROZNICA') && $type == 'PICKUP') {
                    continue;
                }

                $tmp[$i]['id_carrier'] = $id;
                $tmp[$i]['name'] = $c['name'];
                $i++;
            }

            if (count($tmp) <= 1) {
                return false;
            }

            $fields_form = array(
                'form' => array(
                    'legend' => array(
                        'title' => $this->l('Carrier Available'),
                        'icon' => 'icon-cogs'
                    ),
                    'input' => array(
                        'sel_delivery' => array(
                            'type' => 'select',
                            'label' => $this->l('Carrier'),
                            'name' => 'new_carrier',
                            'required' => true,
                            'default_value' => 0,
                            'class' => 't sel_delivery',
                            'options' => array(
                                'query' => $tmp,
                                'id' => 'id_carrier',
                                'name' => 'name'
                            )
                        ),
                        array(
                            'col' => 3,
                            'class' => 't pr_in',
                            'type' => 'text',
                            'desc' => $this->l('Carrier price tax incl.'),
                            'name' => 'price_incl',
                            'label' => $this->l('Price tax incl.'),
                        ),
                        array(
                            'col' => 3,
                            'class' => 't pr_ex',
                            'type' => 'text',
                            'desc' => $this->l('Carrier price tax excl.'),
                            'name' => 'price_excl',
                            'label' => $this->l('Price tax excl.'),
                        ),
                    ),
                    'buttons' => array(
                        'updcarrier' => array(
                            'title' => $this->l('Update carrier'),
                            'name' => 'updcarrier',
                            'type' => 'button',
                            'class' => 'btn btn-default pull-right changec_submit',
                            'icon' => 'process-icon-refresh'
                        )
                    )
                ),
            );

            $helper = new HelperForm();
            $helper->show_toolbar = false;
            $helper->table = $this->table;
            $helper->module = $this;
            $helper->identifier = $this->identifier;
            $helper->submit_action = 'submitChangeCarrier';
            $helper->currentIndex = AdminController::$currentIndex.'?id_order='.$order->id
                .'&vieworder&token='.Tools::getAdminTokenLite('AdminOrders');
            $helper->token = Tools::getAdminTokenLite('AdminOrders');
            $helper->tpl_vars['fields_value']['price_excl'] = '';
            $helper->tpl_vars['fields_value']['price_incl'] = '';
            $helper->tpl_vars['fields_value']['new_carrier'] = 0;
            $path_module_http = __PS_BASE_URI__.'modules/yandexmodule/';

            $this->context->smarty->assign('employee_id', $this->context->employee->id);
            $this->context->smarty->assign('path_module_http', $path_module_http);
            $this->context->smarty->assign('token_lite', Tools::getAdminTokenLite('AdminOrders'));
            $this->context->smarty->assign('orderid', $order->id);

            $ht .= $this->context->smarty->fetch(dirname(__FILE__).'\views\templates\front\carrier.tpl');

            $ht .= $helper->generateForm(array($fields_form)).'</div>';
        }

        return $ht;
    }

    public function processLoadPrice()
    {
        $id_order = (int)Tools::getValue('id_o');
        $id_new_carrier = (int)Tools::getValue('new_carrier');
        $order = new Order($id_order);
        $cart = new Cart($order->id_cart);
        $carrier_list = $cart->getDeliveryOptionList();
        if (isset($carrier_list[$order->id_address_delivery][$id_new_carrier.',']['carrier_list'][$id_new_carrier])) {
            $carrier = $carrier_list[$order->id_address_delivery][$id_new_carrier.',']['carrier_list'][$id_new_carrier];
            $pr_incl = $carrier['price_with_tax'];
            $pr_excl = $carrier['price_without_tax'];
            $result = array(
                'price_without_tax' => $pr_excl,
                'price_with_tax' => $pr_incl
            );
        } else {
            $result = array('error' => $this->l('Wrong carrier'));
        }

        return $result;
    }

    public function processChangeCarrier()
    {
        $id_order = (int)Tools::getValue('id_o');
        $id_new_carrier = (int)Tools::getValue('new_carrier');
        $price_incl = (float)Tools::getValue('pr_incl');
        $price_excl = (float)Tools::getValue('pr_excl');
        $order = new Order($id_order);
        $result = array();
        $result['error'] = '';
        if ($id_new_carrier == 0) {
            $result['error'] = $this->l('Error: cannot select carrier');
        } else {
            if ($order->id < 1) {
                $result['error'] = $this->l('Error: cannot find order');
            } else {
                $total_carrierwt = (float)$order->total_products_wt + (float)$price_incl;
                $total_carrier = (float)$order->total_products + (float)$price_excl;

                $order->total_paid = (float)$total_carrierwt;
                $order->total_paid_tax_incl = (float)$total_carrierwt;
                $order->total_paid_tax_excl =(float)$total_carrier;
                $order->total_paid_real = (float)$total_carrierwt;
                $order->total_shipping = (float)$price_incl;
                $order->total_shipping_tax_excl = (float)$price_excl;
                $order->total_shipping_tax_incl = (float)$price_incl;
                $order->carrier_tax_rate = (float)$order->carrier_tax_rate;
                $order->id_carrier = (int)$id_new_carrier;
                if (!$order->update()) {
                    $result['error'] = $this->l('Error: cannot update order');
                    $result['status'] = false;
                } else {
                    if ($order->invoice_number > 0) {
                        $order_invoice = new OrderInvoice($order->invoice_number);
                        $order_invoice->total_paid_tax_incl =(float)$total_carrierwt;
                        $order_invoice->total_paid_tax_excl =(float)$total_carrier;
                        $order_invoice->total_shipping_tax_excl =(float)$price_excl;
                        $order_invoice->total_shipping_tax_incl =(float)$price_incl;
                        if (!$order_invoice->update()) {
                            $result['error'] = $this->l('Error: cannot update order invoice');
                            $result['status'] = false;
                        }
                    }

                    $id_order_carrier = Db::getInstance()->getValue('
                            SELECT `id_order_carrier`
                            FROM `'._DB_PREFIX_.'order_carrier`
                            WHERE `id_order` = '.(int) $order->id);

                    if ($id_order_carrier) {
                        $order_carrier = new OrderCarrier($id_order_carrier);
                        $order_carrier->id_carrier = $order->id_carrier;
                        $order_carrier->shipping_cost_tax_excl = (float)$price_excl;
                        $order_carrier->shipping_cost_tax_incl = (float)$price_incl;
                        if (!$order_carrier->update()) {
                            $result['error'] = $this->l('Error: cannot update order carrier');
                            $result['status'] = false;
                        }
                    }

                    $result['status'] = true;
                }
            }
        }

        if ($result['status']) {
            $this->getOrderModel()->sendCarrierToYandex($order, $this->status);
        }

        return $result;
    }

    public function hookDisplayFooter($params)
    {
        $data = '';
        if (!Configuration::get('YA_METRICS_ACTIVE')) {
            $data .= 'var celi_order = false;';
            $data .= 'var celi_cart = false;';
            $data .= 'var celi_wishlist = false;';
            return '<p style="display:none;"><script type="text/javascript">'.$data.'</script></p>';
        }

        if (Configuration::get('YA_METRICS_CELI_ORDER')) {
            $data .= 'var celi_order = true;';
        } else {
            $data .= 'var celi_order = false;';
        }

        if (Configuration::get('YA_METRICS_CELI_CART')) {
            $data .= 'var celi_cart = true;';
        } else {
            $data .= 'var celi_cart = false;';
        }

        if (Configuration::get('YA_METRICS_CELI_WISHLIST')) {
            $data .= 'var celi_wishlist = true;';
        } else {
            $data .= 'var celi_wishlist = false;';
        }

        if (Configuration::get('YA_METRICS_CODE') != '') {
            return '<p style="display:none;"><script type="text/javascript">'.$data
            .'</script>'.Configuration::get('YA_METRICS_CODE').'</p>';
        }
    }

    public function selfPostProcess()
    {
        $error = Tools::getValue('error');
        if (!empty($error)) {
            $this->metrika_error = $this->displayError($this->getCipher()->decrypt($error));
        }

        if (Tools::getIsset('generatemanual')) {
            $this->getMarketModel()->generateXML(false);
        }
        if (Tools::getIsset('downloadlog')) {
            $this->downloadLog();
        }

        if (Tools::isSubmit('submitmetrikaModule')) {
            $this->metrics_status = $this->getMetricsModel()->validateOptions();
            if ($this->metrika_valid && Configuration::get('YA_METRICS_ACTIVE')) {
                $this->getMetricsModel()->sendData();
            } elseif ($this->metrika_valid && !Configuration::get('YA_METRICS_ACTIVE')) {
                $this->metrics_status .= $this->displayError(
                    $this->l(
                        'The changes have saved but not sent! Turn On The Metric!'
                    )
                );
            }
            $this->update_status = $this->sendStatistics();
        } elseif (Tools::isSubmit('submitorgModule')) {
            $this->org_status = $this->getKassaModel()->validateOptions();
            $this->update_status = $this->sendStatistics();
        } elseif (Tools::isSubmit('submitp2pModule')) {
            $this->p2p_status = $this->getWalletModel()->validateOptions();
        } elseif (Tools::isSubmit('submitbilling_formModule')) {
            $this->billing_status = $this->getBillingModel()->validateOptions();
        } elseif (Tools::isSubmit('submitMarket_ordersModule')) {
            $this->market_orders_status = $this->getOrderModel()->validateOptions();
            $this->update_status = $this->sendStatistics();
        } elseif (Tools::isSubmit('submitmarketModule')) {
            $this->market_status = $this->getMarketModel()->validateOptions();
            $this->update_status = $this->sendStatistics();
        }
    }

    public function sendStatistics()
    {
        $headers = array();
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';

        $array = array(
            'url' => Tools::getShopDomainSsl(true),
            'cms' => 'prestashop',
            'version' => _PS_VERSION_,
            'ver_mod' => $this->version,
            'email' => $this->context->employee->email,
            'shopid' => Configuration::get('YA_ORG_SHOP_ID'),
            'settings' => array(
                'kassa' => (bool) Configuration::get('YA_KASSA_ACTIVE'),
                'p2p' => (bool) Configuration::get('YA_WALLET_ACTIVE'),
                'metrika' =>(bool) Configuration::get('YA_METRICS_ACTIVE'),
                'billing' => (bool) Configuration::get('YA_BILLING_ACTIVE'),
            )
        );

        $array_crypt = base64_encode(serialize($array));

        $url = 'https://statcms.yamoney.ru/v2/';
        $curlOpt = array(
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_POST => true,
        );

        $curlOpt[CURLOPT_HTTPHEADER] = $headers;
        $curlOpt[CURLOPT_POSTFIELDS] = http_build_query(array('data' => $array_crypt, 'lbl'=>0));

        $curl = curl_init($url);
        curl_setopt_array($curl, $curlOpt);
        curl_exec($curl);
        //$rcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        /*
          $json=json_decode($rbody);
            if ($rcode==200 && isset($json->new_version)){
                return $json->new_version;
            }else{*/
        return false;
        // }
    }

    public function getContent()
    {
        $this->context->controller->addJS(__PS_BASE_URI__.'modules/'.$this->name.'/views/js/main.js');
        $this->context->controller->addJS(__PS_BASE_URI__.'modules/'.$this->name.'/views/js/jquery.total-storage.js');
        $this->context->controller->addCSS($this->_path.'views/css/admin.css');
        $this->selfPostProcess();
        $this->context->controller->addJqueryUI('ui.tabs');

        $kassa = $this->getKassaModel();
        $vars_org = Configuration::getMultiple(
            array_merge(
                array(
                    'YA_KASSA_PAYMENT_MODE_ON',
                    'YA_KASSA_PAY_LOGO_ON',
                    'YA_KASSA_LOGGING_ON',
                ),
                $kassa->getTaxesArray(),
                array_values($kassa->getPaymentMethods())
            )
        );
        $vars_org['kassa'] = $kassa;
        $shopId = $kassa->getShopId();
        $vars_org['YA_KASSA_NOTIFICATION_URL'] = $this->context->link->getModuleLink('yandexmodule', 'notifycapture');

        $vars_p2p = array(
            'wallet' => $this->getWalletModel(),
        );
        $vars_metrika = Configuration::getMultiple(array(
            'YA_METRICS_PASSWORD_APPLICATION',
            'YA_METRICS_ID_APPLICATION',
            'YA_METRICS_SET_WEBVIZOR',
            'YA_METRICS_SET_CLICKMAP',
            'YA_METRICS_SET_OUTLINK',
            'YA_METRICS_SET_OTKAZI',
            'YA_METRICS_SET_HASH',
            'YA_METRICS_ACTIVE',
            'YA_METRICS_TOKEN',
            'YA_METRICS_NUMBER',
            'YA_METRICS_CELI_CART',
            'YA_METRICS_CELI_ORDER',
            'YA_METRICS_CELI_WISHLIST'
        ));
        $vars_billing = array(
            'billing' => $this->getBillingModel(),
        );
        $vars_market_orders = Configuration::getMultiple(array(
            'YA_MARKET_ORDERS_PUNKT',
            'YA_MARKET_ORDERS_TOKEN',
            'YA_MARKET_ORDERS_PREDOPLATA_YANDEX',
            'YA_MARKET_ORDERS_PREDOPLATA_SHOP_PREPAID',
            'YA_MARKET_ORDERS_POSTOPLATA_CASH_ON_DELIVERY',
            'YA_MARKET_ORDERS_POSTOPLATA_CARD_ON_DELIVERY',
            'YA_MARKET_ORDERS_APIURL',
            'YA_MARKET_ORDERS_SET_CHANGEC',
            'YA_MARKET_ORDERS_NC',
            'YA_MARKET_ORDERS_LOGIN',
            'YA_MARKET_ORDERS_ID',
            'YA_MARKET_ORDERS_PW',
            'YA_MARKET_ORDERS_YATOKEN',
        ));
        $vars_market = Configuration::getMultiple(array(
            'YA_MARKET_SET_ALLCURRENCY',
            'YA_MARKET_NAME',
            'YA_MARKET_SET_AVAILABLE',
            'YA_MARKET_SET_NACTIVECAT',
            //'YA_MARKET_SET_HOMECARRIER',
            'YA_MARKET_SET_COMBINATIONS',
            'YA_MARKET_CATALL',
            'YA_MARKET_SET_DIMENSIONS',
            'YA_MARKET_SET_SAMOVIVOZ',
            'YA_MARKET_SET_DOST',
            'YA_MARKET_SET_ROZNICA',
            'YA_MARKET_DELIVERY',
            'YA_MARKET_MK',
            'YA_MARKET_SHORT',
            'YA_MARKET_HKP',
            'YA_MARKET_DOSTUPNOST',
            'YA_MARKET_SET_GZIP',
            'YA_MARKET_DESC_TYPE',
        ));

        $cats = array();
        if ($c = Configuration::get('YA_MARKET_CATEGORIES')) {
            $uc = unserialize($c);
            if (is_array($uc)) {
                $cats = $uc;
            }
        }

        $forms = new YandexMoneyModule\FormHelper();
        $forms->cats = $cats;

        $vars_market_orders['YA_MARKET_ORDERS_FD'] = 'JSON';
        $vars_market_orders['YA_MARKET_ORDERS_TA'] = 'URL';
        $vars_org['YA_ORG_TEXT_INSIDE'] = "Shop ID, scid, ShopPassword можно посмотреть".
            " в <a href='https://money.yandex.ru/joinups' target='_blank'>личном кабинете</a>".
            " после подключения Яндекс.Кассы.";
        $vars_p2p['YA_WALLET_TEXT_INSIDE'] = "ID и секретное слово вы получите после".
            " <a href='https://sp-money.yandex.ru/myservices/new.xml'".
            " target='_blank'>регистрации приложения</a>".
            " на сайте Яндекс.Денег";
        $this->context->smarty->assign(array(
            'ya_version' => $this->version,
            'orders_link' => $this->context->link->getAdminLink('AdminOrders', false)
                .'&token='.Tools::getAdminTokenLite('AdminOrders'),
            'ajax_limk_ym' => $this->context->link->getAdminLink('AdminModules', false)
                .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='
                .$this->name.'&token='.Tools::getAdminTokenLite('AdminModules'),
            'this_path' => $this->_path,
            'update_status' => $this->update_status,
            'metrika_status' => $this->metrics_status,
            'market_status' => $this->market_status,
            'market_orders_status' => $this->market_orders_status,
            'billing_status' => $this->billing_status,
            'p2p_status' => $this->p2p_status,
            'org_status' => $this->org_status,
            'money_p2p' => $this->renderForm('p2p', $vars_p2p, $forms->getWalletForm($this->getWalletModel())),
            'money_org' => $this->renderForm('org', $vars_org, $forms->getKassaForm($kassa)),
            'kassa' => $kassa,
            'emptyShopId' => empty($shopId),
            'money_metrika' => $this->renderForm('metrika', $vars_metrika, $forms->getFormYandexMetrics()),
            'money_market' => $this->renderForm('market', $vars_market, $forms->getFormYamoneyMarket()),
            'money_marketp' => $this->renderForm('market_orders', $vars_market_orders, $forms->getMarketOrdersForm()),
            'billing_form' => $this->renderForm(
                'billing_form',
                $vars_billing,
                $forms->getBillingForm($this->getBillingModel())
            ),
        ));
        return $this->display(__FILE__, 'admin.tpl');
    }

    public function displayReturnsContent($params)
    {
        $errors = array();

        /** @var Order $order */
        $order = $params['order'];

        /** @var \YandexMoneyModule\Models\KassaModel $paymentModel */
        $paymentModel = $this->getPaymentModel();
        $orderId = $order->id;
        $payment = null;
        if (!($paymentModel instanceof \YandexMoneyModule\Models\KassaModel)) {
            $errors[] = $this->l('Module Yandex.Cash is disabled');
        } else {
            $payment = $paymentModel->findOrderPayment($orderId);
            if ($payment === null) {
                $errors[] = $this->l('Payment for order not exists');
            }
        }
        $refunds = $paymentModel->findRefunds($orderId);
        $totalRefunded = 0;
        foreach ($refunds as $refund) {
            $totalRefunded += $refund['amount'];
        }
        $refundableAmount = $payment->getAmount()->getValue() - $totalRefunded;
        if ($refundableAmount < 0) {
            $refundableAmount = 0;
        }

        if (empty($errors) && Tools::isSubmit('return_amount')) {
            $cause = Tools::getValue('return_comment');
            $amount = Tools::getValue('return_amount');
            $amount = number_format((float)$amount, 2, '.', '');

            if (Tools::strlen($cause) > 250 || Tools::strlen($cause) < 3) {
                $errors[] = $this->l('Return reason can not be empty or exceed a length of 250 characters');
            }
            if ($amount > $refundableAmount) {
                $errors[] = $this->l('The refund amount cannot exceed the amount of the payment');
            }

            if (!$errors) {
                $refund = $paymentModel->createRefund($payment, $order, $amount, $cause);
                if ($refund === null) {
                    $errors[] = $this->l('Failed to create refund');
                } else {
                    $refundableAmount -= $refund->getAmount()->getValue();
                    $refunds[] = array(
                        'refund_id' => $refund->getId(),
                        'status' => $refund->getStatus(),
                        'amount' => $refund->getAmount()->getValue(),
                        'comment' => $cause,
                        'created_at' => $refund->getCreatedAt()->format('Y-m-d H:i:s'),
                    );
                }
            }
        }

        $customer = new Customer($params['order']->id_customer);

        $names = array(
            \YaMoney\Model\PaymentMethodType::BANK_CARD => 'Банковские карты',
            \YaMoney\Model\PaymentMethodType::YANDEX_MONEY => 'Яндекс.Деньги',
            \YaMoney\Model\PaymentMethodType::SBERBANK => 'Сбербанк Онлайн',
            \YaMoney\Model\PaymentMethodType::QIWI => 'QIWI Wallet',
            \YaMoney\Model\PaymentMethodType::WEBMONEY => 'Webmoney',
            \YaMoney\Model\PaymentMethodType::CASH => 'Наличные через терминалы',
            \YaMoney\Model\PaymentMethodType::MOBILE_BALANCE => 'Баланс мобильного',
            \YaMoney\Model\PaymentMethodType::ALFABANK => 'Альфа-Клик',
        );
        $paymentType = 'Способ оплаты не определён';
        $additionalPaymentInfo = '';
        if ($payment->getPaymentMethod() !== null) {
            $method = $payment->getPaymentMethod();
            if (isset($names[$method->getType()])) {
                $paymentType = $names[$payment->getPaymentMethod()->getType()];
                if ($method instanceof \YaMoney\Model\PaymentMethod\PaymentMethodYandexWallet) {
                    $additionalPaymentInfo = 'номер кошелька: ' . $method->getAccountNumber();
                } elseif ($method instanceof \YaMoney\Model\PaymentMethod\PaymentMethodAlfaBank) {
                    $additionalPaymentInfo = 'логин в Альфа-клике: ' . $method->getLogin();
                } elseif ($method instanceof \YaMoney\Model\PaymentMethod\PaymentMethodSberbank) {
                    $additionalPaymentInfo = 'телефон: ' . $method->getPhone();
                }
            }
        }

        $carrier = new Carrier($params['order']->id_carrier);
        $this->context->smarty->assign(array(
            'email' => $customer->email,
            'orderId' => $orderId,
            'returnTotal' => Tools::displayPrice($totalRefunded),
            'refundableAmount' => $refundableAmount,
            'payment' => $payment,
            'paymentType' => $paymentType,
            'additionalPaymentInfo' => $additionalPaymentInfo,
            'text_success' => $this->l('The payment is successfully returned'),
            'refunds' => $refunds,
            'return_errors' => $errors,
            'dname' => $carrier->name
        ));
        $html = $this->display(__FILE__, 'kassa_returns_content.tpl');
        return $html;
    }

    public function displayReturnsContentTabs()
    {
        $html = $this->display(__FILE__, 'kassa_returns_tabs.tpl');

        return $html;
    }

    /**
     * Записывает в лог сообщение
     * Если логгирование отключено, ничего не делает
     * @param string $level Тип сообщения (debug|info|notice|warning|error|critical|alert|emergency)
     * @param string $message Текст сообщения
     */
    public function log($level, $message)
    {
        if (Configuration::get('YA_KASSA_LOGGING_ON') != 'on') {
            return;
        }
        $logDirName = 'log_files';
        $path = _PS_MODULE_DIR_.'/yandexmodule/'.$logDirName;
        if (!is_dir($path)) {
            if (!mkdir($path, 0777)) {
                return;
            }
        } else {
            chmod($path, 0777);
        }
        $fileName = $path.'/module.log';
        $fd = @fopen($fileName, 'a');
        if (!$fd) {
            return;
        }
        if (!flock($fd, LOCK_EX)) {
            return;
        }
        fwrite($fd, date('Y-m-d H:i:s ').' '.$level.' ['.addslashes($_SERVER['REMOTE_ADDR']).'] '.$message."\n");
        flock($fd, LOCK_UN);
        fclose($fd);
    }

    private function downloadLog()
    {
        $logDirName = 'log_files';
        $path = _PS_MODULE_DIR_.'/yandexmodule/'.$logDirName.'/module.log';
        if (file_exists($path)) {
            $content = Tools::file_get_contents($path);
        } else {
            $content = '';
        }
        header('Pragma: public');
        header('Expires: 0');
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="yandex-money_' . date('Y-m-d_H-i-s') . '.log"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . Tools::strlen($content));
        echo $content;
        exit();
    }

    /**
     * Hook what called to display payment method for customer on PrestaShop 1.7
     * @param array $params Array with order information
     * @return PrestaShop\PrestaShop\Core\Payment\PaymentOption[]|null Array with payment options or null if module
     * disabled or payments with Yandex.Kassa option not enabled
     */
    public function hookPaymentOptions($params)
    {
        if (!$this->checkCurrency($params['cart'])) {
            // если корзины в параметрах нет - ничего не делаем
            $this->log('debug', 'Invalid customer currency');
            return null;
        }
        $model = $this->getPaymentModel();
        if ($model === null) {
            // если отключен приём платежей - ничего не делаем
            $this->log('debug', 'Payment module disabled');
            return null;
        }
        $this->context->smarty->assign(array(
            'model' => $model,
            'shop_name' => Configuration::get('PS_SHOP_NAME'),
            'image_dir' => $this->getPathUri() . '/views/img/',
            'action' => $this->context->link->getModuleLink(
                $this->name,
                $model->getPaymentActionController(),
                array(),
                true
            ),
        ));
        $template = $model->assignVariables($this->context->smarty);
        if ($template === null) {
            $this->log('debug', 'Template is empty');
            return null;
        }

        // получаем отображаемый контент
        $display = $this->fetch($template);
        if (empty($display)) {
            // if payment options content is empty, do not show module payment options
            $this->log('warning', 'Empty payment template');
            return null;
        }
        // генерируем настройки для отображения способов оплаты
        $paymentOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $paymentOption->setModuleName($this->name)
            ->setCallToActionText(
                $this->trans(
                    'Яндекс.Касса (банковские карты, электронные деньги и другое)',
                    array(),
                    'Modules.YandexModule.Shop'
                )
            )
            ->setForm($display);

        $this->log('debug', 'Payment_options: ' . json_encode($paymentOption));

        return array(
            $paymentOption,
        );
    }

    public function hookDisplayPayment($params)
    {
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }
        $cart = $this->context->cart;
        $total_to_pay = $cart->getOrderTotal(true);
        $rub_currency_id = Currency::getIdByIsoCode('RUB');
        if ($cart->id_currency != $rub_currency_id) {
            $from_currency = new Currency($cart->id_curre1ncy);
            $to_currency = new Currency($rub_currency_id);
            $total_to_pay = Tools::convertPriceFull($total_to_pay, $from_currency, $to_currency);
        }
        $this->context->smarty->assign(array(
            'summ' => number_format($total_to_pay, 2, '.', ''),
            'this_path' => $this->_path,
            'this_path_ssl' => Tools::getHttpHost(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/'
        ));
        $display = '';
        if ($this->getKassaModel()->isEnabled()) {
            $kassa = $this->getKassaModel();
            $this->context->smarty->assign(array(
                'model' => $kassa,
                'shop_name' => Configuration::get('PS_SHOP_NAME'),
                'image_dir' => $this->getPathUri() . '/views/img/',
                'action' => $this->context->link->getModuleLink($this->name, 'paymentkassa', array(), true),
                'buttontext' => $this->l('Yandex.Kassa (bank cards, e-money and other)'),
            ));
            if ($kassa->getEPL()) {
                // for epl show template without payment type options
                $this->log('debug', 'Show EPL page');
                $template = 'kassa_epl_form.tpl';
            } else {
                // add payment type options to template variables
                $this->log('debug', 'Show select payment method page');
                $methods = $kassa->getEnabledPaymentMethods();
                if (empty($methods)) {
                    // without any payment method do not display module template
                    $this->log('warning', 'Empty payment method list');
                    return null;
                }
                $template = 'kassa_form.tpl';
                $this->smarty->assign('label', 'Выберите способ оплаты');
                $this->smarty->assign('payment_methods', $methods);
            }
            // получаем отображаемый контент
            $display = $this->display(__FILE__, '1.6/' . $template);
            if (empty($display)) {
                // if template is empty - go away
                $this->log('warning', 'Empty payment template');
                return null;
            }
        } elseif ($this->getWalletModel()->isEnabled()) {
            $this->context->smarty->assign(array(
                'wallet' => $this->getWalletModel(),
                'price' => number_format($total_to_pay, 2, '.', ''),
                'cart' => $this->context->cart
            ));
            $display .= $this->display(__FILE__, '1.6/wallet_form.tpl');
        } elseif ($this->getBillingModel()->isEnabled()) {
            $this->context->smarty->assign(array(
                'billing' => $this->getBillingModel(),
                'price' => number_format($total_to_pay, 2, '.', ''),
                'cart' => $this->context->cart
            ));
            $display .= $this->display(__FILE__, '1.6/billing_form.tpl');
        }

        return $display;
    }

    /**
     * Хук вызываемый при возврате пользователя из кассы
     * @param array $params Массив с контекстом информации о платеже
     * @return string Текст для вставки в страницу подтверждения заказа
     */
    public function hookDisplayPaymentReturn($params)
    {
        if (!$this->active) {
            // если модуль отключён, ничего не делаем
            $this->log('debug', 'Module is not active on confirmation page');
            return '';
        }
        if (Configuration::get('YA_KASSA_ACTIVE') != '1') {
            $this->log('debug', 'Payment module disabled on confirmation page');
            return '';
        }
        if (version_compare(_PS_VERSION_, '1.7.0') > 0) {
            $key = 'order';
        } else {
            $key = 'objOrder';
        }
        if (empty($params[$key]) || !($params[$key] instanceof Order)) {
            // return if order not exists in method argument
            $this->log(
                'warning',
                (empty($params['order']) ? 'Empty' : 'Invalid') . ' order in return hook: ' . json_encode($params)
            );
            return '';
        }

        /** @var Order $order Инстанс заказа, который оплачивал пользователь */
        $order = $params[$key];
        $kassa = $this->getKassaModel();
        $success = false;
        if ($order->getCurrentState() == $kassa->getCreateStatusId()) {
            // обрабатываем только платежи, которые только что созданы
            $this->log('debug', 'Payment return: ' . $order->id);
            // получаем связанный с обрабатываемым заказом платёж
            $payment = $kassa->findOrderPayment($order->id, $order->getOrdersTotalPaid());
            if ($payment !== null) {
                $success = true;
                // обновляем статус платежа в локальной базе данных
                $kassa->updatePaymentStatus($payment);
                // проверяем состояние платежа
                if ($payment->getStatus() === YaMoney\Model\PaymentStatus::WAITING_FOR_CAPTURE) {
                    // если платёж уже ожидает подтверждения - подтверждаем
                    $this->log('debug', 'Confirm payment for order#' . $order->id);
                    $response = $kassa->capturePayment($payment);
                    if ($response === null) {
                        $success = false;
                        $this->smarty->assign('message', 'Не удалось провести платёж');
                    } else {
                        $orderStatusId = $kassa->getSuccessStatusId();
                    }
                } elseif ($payment->getStatus() === YaMoney\Model\PaymentStatus::CANCELED) {
                    // если платёж был отменён, сообщаем об этом
                    $this->log('debug', 'Order#' . $order->id . ' is cancelled');
                    $success = false;
                    $this->smarty->assign('message', 'Платёж был отменён');
                } elseif ($payment->getStatus() === \YaMoney\Model\PaymentStatus::SUCCEEDED) {
                    $orderStatusId = $kassa->getSuccessStatusId();
                }
                if (isset($orderStatusId)) {
                    // если установлен статус, меняем на него статус заказа
                    $history = new OrderHistory();
                    $history->id_order = $order->id;
                    $history->changeIdOrderState($orderStatusId, $order->id);
                    $history->addWithemail(true);
                    // обновляем номер транзакции, привязанной к заказу
                    $this->getKassaModel()->updateOrderPaymentId($order->id, $payment);
                }
            } else {
                $message = 'Payment for order#' . $order->id . ' not exists';
                $this->log('warning', $message);
                $this->smarty->assign('message', $message);
            }
        } else {
            // получаем связанный с обрабатываемым заказом платёж
            $payment = $kassa->findOrderPayment($order->id, $order->getOrdersTotalPaid());
            if ($payment !== null && $payment->getPaid()) {
                $success = true;
            } else {
                $message = 'Order#' . $order->id . ' payment state is ' . $order->getCurrentState()
                    . ' != ' . $kassa->getCreateStatusId();
                $this->log('info', $message);
                $this->smarty->assign('message', $message);
            }
        }
        if ($success) {
            $this->smarty->assign(array(
                'shop_name' => $this->context->shop->name,
                'total' => Tools::displayPrice(
                    $order->getOrdersTotalPaid(),
                    new Currency($order->id_currency),
                    false
                ),
                'reference' => $order->reference,
                'contact_url' => $this->context->link->getPageLink('contact', true)
            ));
        }
        $template = 'kassa_payment_' . ($success ? 'success' : 'failure');
        if (version_compare(_PS_VERSION_, '1.7.0') > 0) {
            return $this->fetch('module:yandexmodule/views/templates/hook/' . $template . '.tpl');
        } else {
            return $this->display(__FILE__, $template . '.tpl');
        }
    }

    /**
     * @param \YaMoney\Model\PaymentInterface $payment
     * @return bool
     */
    public function capturePayment($payment)
    {
        $kassa = $this->getKassaModel();
        $payment = $kassa->getPayment($payment->getId());
        if ($payment === null) {
            $this->log('error', 'Failed to fetch payment object in notification');
            return false;
        }
        if ($payment->getStatus() !== \YaMoney\Model\PaymentStatus::WAITING_FOR_CAPTURE) {
            return $payment->getStatus() === \YaMoney\Model\PaymentStatus::SUCCEEDED;
        }
        $kassa->updatePaymentStatus($payment);
        $orderId = $kassa->getOrderIdByPayment($payment);
        $success = false;
        if ($orderId > 0) {
            if ($payment->getStatus() === \YaMoney\Model\PaymentStatus::SUCCEEDED) {
                // change order status to "success" if payment already succeeded
                $orderStatusId = $kassa->getSuccessStatusId();
                $success = true;
            } elseif ($payment->getStatus() === \YaMoney\Model\PaymentStatus::WAITING_FOR_CAPTURE) {
                // если платёж ожидает подтверждения - подтверждаем
                $this->log('debug', 'Capture payment for order#' . $orderId);
                $response = $kassa->capturePayment($payment);
                if ($response === null || $response->getStatus() !== \YaMoney\Model\PaymentStatus::SUCCEEDED) {
                    // failure order if payment not captured
                    $this->log('error', 'Failed to capture payment ' . $payment->getId());
                    $orderStatusId = $kassa->getFailureStatusId();
                } else {
                    $orderStatusId = $kassa->getSuccessStatusId();
                    $success = true;
                }
            }
            if (isset($orderStatusId)) {
                // если установлен статус, меняем на него статус заказа
                $history = new OrderHistory();
                $history->id_order = $orderId;
                $history->changeIdOrderState($orderStatusId, $orderId);
                $history->addWithemail(true);
                // обновляем номер транзакции, привязанной к заказу
                $this->getKassaModel()->updateOrderPaymentId($orderId, $payment);
            }
        }
        return $success;
    }

    public function hookDisplayOrderConfirmation($params)
    {
        if (!Configuration::get('YA_METRICS_ACTIVE')) {
            return false;
        }

        $ret = array();

        if (version_compare(_PS_VERSION_, '1.7.0') < 0) {
            $ret['order_price'] = $params['total_to_pay'] . ' ' . $params['currency'];
            $ret['order_id'] = $params['objOrder']->id;
            $ret['currency'] = $params['currencyObj']->iso_code;
            $ret['payment'] = $params['objOrder']->payment;
            $products = array();
            foreach ($params['objOrder']->getCartProducts() as $k => $product) {
                $products[$k]['id'] = $product['product_id'];
                $products[$k]['name'] = $product['product_name'];
                $products[$k]['quantity'] = $product['product_quantity'];
                $products[$k]['price'] = $product['product_price'];
            }
        } else {
            /** @var Order $order */
            $order = $params['order'];
            $currency = new Currency($order->id_currency);
            $ret['order_price'] = $order->total_paid . ' ' . $currency->iso_code;
            $ret['order_id'] = $order->id;
            $ret['currency'] = $currency->iso_code;
            $ret['payment'] = $order->payment;
            $products = array();
            /** @var Cart $cart */
            $cart = $params['cart'];
            foreach ($cart->getProducts() as $k => $product) {
                $products[$k]['id'] = $product['product_id'];
                $products[$k]['name'] = $product['product_name'];
                $products[$k]['quantity'] = $product['product_quantity'];
                $products[$k]['price'] = $product['product_price'];
            }
        }

        $ret['goods'] = $products;
        $data = '<script>
                $(window).load(function() {
                    if(celi_order)
                        metrikaReach(\'metrikaOrder\', '.Tools::jsonEncode($ret).');
                });
                </script>
        ';

        return $data;
    }

    public function hookDisplayHeader()
    {
        $this->context->controller->addCSS($this->_path.'/views/css/main.css');
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
    }

    protected function renderForm($mod, $vars, $form)
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit'.$mod.'Module';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        foreach ($form['form']['input'] as $item) {
            if (isset($item['value'])) {
                $vars[$item['name']] = $item['value'];
            }
        }
        $helper->fields_value = $vars;
        $p2p_redirect = $this->context->link->getModuleLink($this->name, 'redirect');
        $httpsBasePath = str_replace('http://', 'https://', _PS_BASE_URL_.__PS_BASE_URI__);
        $api_market_orders = $httpsBasePath . 'yandexmodule/marketorders';
        $redir = $httpsBasePath . 'modules/yandexmodule/callback.php';
        $market_list = $this->context->link->getModuleLink($this->name, 'generate');
        $helper->fields_value['YA_MARKET_YML'] = $market_list;
        $helper->fields_value['YA_WALLET_REDIRECT'] = $p2p_redirect;
        $helper->fields_value['YA_MARKET_ORDERS_APISHOP'] = $api_market_orders;
        $helper->fields_value['YA_MARKET_REDIRECT'] = $helper->fields_value['YA_METRICS_REDIRECT'] = $redir;
        if ($mod == 'market_orders') {
            $carriers = Carrier::getCarriers(Context::getContext()->language->id, true, false, false, null, 5);
            foreach ($carriers as $a) {
                $array = unserialize(Configuration::get('YA_MARKET_ORDERS_CARRIER_SERIALIZE'));
                $helper->fields_value['YA_MARKET_ORDERS_DELIVERY_'.$a['id_carrier']]
                    = isset($array[$a['id_carrier']]) ? $array[$a['id_carrier']] : 'POST';
            }
        }
        return $helper->generateForm(array($form));
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency((int)$cart->id_currency);
        $currencies_module = $this->getCurrency();

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
    }

    public function descriptionError($error)
    {
        $error_array = array(
            'invalid_request' => $this->l(
                'Your request is missing required parameters or settings are incorrect or invalid values'
            ),
            'invalid_scope' => $this->l(
                'The scope parameter is missing or has an invalid value or a logical contradiction'
            ),
            'unauthorized_client' => $this->l(
                'Invalid parameter client_id, or the application does not have the'.
                ' right to request authorization (such as its client_id blocked Yandex.Money)'
            ),
            'access_denied' => $this->l('Has declined a request authorization application'),
            'invalid_grant' => $this->l(
                'The issue access_token denied. Issued a temporary token is not '.
                'Google search or expired, or on the temporary token is issued access_token (second '.
                'request authorization token with the same time token)'
            ),
            'illegal_params' => $this->l('Required payment options are not available or have invalid values.'),
            'illegal_param_label' => $this->l('Invalid parameter value label'),
            'phone_unknown' => $this->l('A phone number is not associated with a user account or payee'),
            'payment_refused' => $this->l(
                'The store refused to accept payment (for example, a user tried '.
                'to pay for a product that isn\'t in the store)'
            ),
            'limit_exceeded' => $this->l(
                'Exceeded one of the limits on operations: on the amount of the '.
                'transaction for authorization token issued; transaction amount for the period of time'.
                ' for the token issued by the authorization; Yandeks.Deneg restrictions '.
                'for different types of operations.'
            ),
            'authorization_reject' => $this->l(
                'In payment authorization is denied. Possible reasons are:'.
                ' transaction with the current parameters is not available to the user; person does not'.
                ' accept the Agreement on the use of the service "shops".'
            ),
            'contract_not_found' => $this->l('None exhibited a contract with a given request_id'),
            'not_enough_funds' => $this->l(
                'Insufficient funds in the account of the payer. '.
                'Need to recharge and carry out a new delivery'
            ),
            'not-enough-funds' => $this->l(
                'Insufficient funds in the account of the payer.'.
                ' Need to recharge and carry out a new delivery'
            ),
            'money_source_not_available' => $this->l(
                'The requested method of payment (money_source) '.
                'is not available for this payment'
            ),
            'illegal_param_csc' => $this->l('Tsutstvuet or an invalid parameter value cs'),
            'payment_refused' => $this->l('Shop for whatever reason, refused to accept payment.')
        );
        if (array_key_exists($error, $error_array)) {
            $return = $error_array[$error];
        } else {
            $return = $error;
        }
        return $return;
    }

    /**
     * @return YandexMoneyModule\Models\KassaModel
     */
    public function getKassaModel()
    {
        if ($this->kassaModel === null) {
            $this->kassaModel = new YandexMoneyModule\Models\KassaModel($this);
            $this->kassaModel->initConfiguration();
        }
        return $this->kassaModel;
    }

    /**
     * @return \YandexMoneyModule\Models\WalletModel
     */
    public function getWalletModel()
    {
        if ($this->walletModel === null) {
            $this->walletModel = new \YandexMoneyModule\Models\WalletModel($this);
            $this->walletModel->initConfiguration();
        }
        return $this->walletModel;
    }

    /**
     * @return \YandexMoneyModule\Models\BillingModel
     */
    public function getBillingModel()
    {
        if ($this->billingModel === null) {
            $this->billingModel = new \YandexMoneyModule\Models\BillingModel($this);
            $this->billingModel->initConfiguration();
        }
        return $this->billingModel;
    }

    /**
     * @return YandexMoneyModule\Models\OrderModel
     */
    public function getOrderModel()
    {
        if ($this->orderModel === null) {
            $this->orderModel = new YandexMoneyModule\Models\OrderModel($this);
            $this->orderModel->initConfiguration();
        }
        return $this->orderModel;
    }

    /**
     * @return \YandexMoneyModule\Models\MarketModel
     */
    public function getMarketModel()
    {
        if ($this->marketModel === null) {
            $this->marketModel = new YandexMoneyModule\Models\MarketModel($this);
            $this->marketModel->initConfiguration();
        }
        return $this->marketModel;
    }

    /**
     * @return \YandexMoneyModule\Models\MetricsModel
     */
    public function getMetricsModel()
    {
        if ($this->metricsModel === null) {
            $this->metricsModel = new YandexMoneyModule\Models\MetricsModel($this);
            $this->metricsModel->initConfiguration();
        }
        return $this->metricsModel;
    }

    /**
     * @return \YandexMoneyModule\Models\AbstractPaymentModel|null
     */
    public function getPaymentModel()
    {
        if ($this->getKassaModel()->isEnabled()) {
            return $this->getKassaModel();
        } elseif ($this->getWalletModel()->isEnabled()) {
            return $this->getWalletModel();
        } elseif ($this->getBillingModel()->isEnabled()) {
            return $this->getBillingModel();
        } else {
            return null;
        }
    }
}
