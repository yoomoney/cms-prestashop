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
    const ADMIN_CONTROLLER = 'AdminYandexModule';

    const NPS_RETRY_AFTER_DAYS = 90;

    private $p2p_status = '';
    private $org_status = '';
    private $market_status = '';
    private $metrics_status = '';
    private $billing_status = '';
    private $metrika_valid;
    private $update_status;
    private $nps_block = '';

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


    private static $moduleRoutes = array(
        'generate_price'     => array(
            'controller' => null,
            'rule'       => 'yandexmodule/{controller}',
            'keywords'   => array(
                'controller' => array('regexp' => '[\w]+', 'param' => 'controller'),
            ),
            'params'     => array(
                'fc'     => 'module',
                'module' => 'yandexmodule',
            ),
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

        include_once(dirname(__FILE__).'/lib/autoload.php');

        include_once(dirname(__FILE__).'/lib/YandexModuleApi.php');
        include_once(dirname(__FILE__).'/lib/YandexModuleExternalPayment.php');
        include_once(dirname(__FILE__).'/lib/helpers.php');

        $this->name            = 'yandexmodule';
        $this->tab             = 'payments_gateways';
        $this->version         = '1.1.7';
        $this->author          = $this->l('Yandex.Money');
        $this->need_instance   = 1;
        $this->bootstrap       = 1;
        $this->module_key      = 'f51f5c45095c7d4eec9d2266901d793e';
        $this->currencies      = true;
        $this->currencies_mode = 'checkbox';

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName      = $this->l('Y.CMS 2.0 Prestashop');
        $this->description      = $this->l(
            'Yandex.Money, Yandex.Service, Yandex.Metrika, Yandex.Market'
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
            if (version_compare(_PS_VERSION_, '1.7.0') >= 0) {
                $this->cipher = new PhpEncryption(_NEW_COOKIE_KEY_);
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
        $data      = array();
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
            'displayFooterProduct',
        );
        if (version_compare(_PS_VERSION_, '1.7.0') > 0) {
            $hooks[] = 'paymentOptions';
        } else {
            $hooks[] = 'displayPayment';
        }
        foreach ($hooks as $hook) {
            if (!$this->registerHook($hook)) {
                $this->log('error', 'Failed to register "'.$hook.'" hook');

                return false;
            }
        }

        $installer = new \YandexMoneyModule\Installer($this);
        $installer->addDatabaseTables();
        $installer->addServiceCustomer();
        $installer->installTab();

        return true;
    }

    public function installTabIfNeeded()
    {
        $installer = new \YandexMoneyModule\Installer($this);
        if (!$installer->issetTab()) {
            $installer->installTab();
        }
    }

    /**
     * Метод вызываемый при удалении модуля
     * @return bool True если модуль был успешно удален, false если удалить
     * модуль не удалось
     */
    public function uninstall()
    {
        $installer = new \YandexMoneyModule\Installer($this);
        $installer->removeDatabaseTables();
        $installer->removeServiceCustomer();
        $installer->uninstallTab();

        return parent::uninstall();
    }

    public function hookDisplayFooter($params)
    {
        return Configuration::get('YA_METRICS_CODE');
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
            $prevOptions = $this->getMetricsModel()->getOptionValues();
            $this->metrics_status = $this->getMetricsModel()->validateOptions();
            $this->metrika_valid  = $this->getMetricsModel()->isValid();
            if ($this->getMetricsModel()->isNeedUpdateToken($prevOptions)) {
                $dir = _PS_ADMIN_DIR_;
                $dir = explode(DIRECTORY_SEPARATOR, $dir);
                $dir = base64_encode(
                    $this->getCipher()->encrypt(
                        end($dir).'_'.Context::getContext()->cookie->id_employee.'_metrika'
                    )
                );
                $this->getMetricsModel()->redirectToOAuth($dir);
            }
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
            $this->org_status    = $this->getKassaModel()->validateOptions();
            $this->update_status = $this->sendStatistics();
        } elseif (Tools::isSubmit('submitp2pModule')) {
            $this->p2p_status = $this->getWalletModel()->validateOptions();
        } elseif (Tools::isSubmit('submitbilling_formModule')) {
            $this->billing_status = $this->getBillingModel()->validateOptions();
        } elseif (Tools::isSubmit('submitmarketModule')) {
            $this->market_status = $this->getMarketModel()->validateOptions();
            $this->update_status = $this->sendStatistics();
        }
        $this->nps_block = $this->getKassaModel()->getNpsBlock($this->context->language->iso_code);
    }

    public function sendStatistics()
    {
        $headers   = array();
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';

        $array = array(

            'url'      => Tools::getShopDomainSsl(true),
            'cms'      => 'api-prestashop',
            'version'  => _PS_VERSION_,
            'ver_mod'  => $this->version,
            'email'    => $this->context->employee->email,
            'shopid'   => Configuration::get('YA_ORG_SHOP_ID'),
            'settings' => array(
                'kassa'   => (bool)Configuration::get('YA_KASSA_ACTIVE'),
                'p2p'     => (bool)Configuration::get('YA_WALLET_ACTIVE'),
                'metrika' => (bool)Configuration::get('YA_METRICS_ACTIVE'),
                'billing' => (bool)Configuration::get('YA_BILLING_ACTIVE'),
            ),
        );

        $array_crypt = base64_encode(serialize($array));

        $url     = 'https://statcms.yamoney.ru/v2/';
        $curlOpt = array(
            CURLOPT_HEADER         => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLINFO_HEADER_OUT    => true,
            CURLOPT_POST           => true,
        );

        $curlOpt[CURLOPT_HTTPHEADER] = $headers;
        $curlOpt[CURLOPT_POSTFIELDS] = http_build_query(array('data' => $array_crypt, 'lbl' => 0));

        $curl = curl_init($url);
        curl_setopt_array($curl, $curlOpt);
        curl_exec($curl);
        curl_close($curl);

        return false;

    }

    public function getContent()
    {
        $this->context->controller->addJS($this->_path.'views/js/main.js');
        $this->context->controller->addJS($this->_path.'views/js/jquery.total-storage.js');
        $this->context->controller->addCSS($this->_path.'views/css/admin.css');
        $this->context->controller->addCSS($this->_path.'views/css/market.css');
        $this->context->controller->addJS($this->_path.'views/js/market.js');
        $this->selfPostProcess();
        $this->context->controller->addJqueryUI('ui.tabs');

        $kassa                                 = $this->getKassaModel();
        $vars_org                              = Configuration::getMultiple(
            array_merge(
                array(
                    'YA_KASSA_PAYMENT_MODE_ON',
                    'YA_KASSA_PAY_LOGO_ON',
                    'YA_KASSA_INSTALLMENTS_BUTTON_ON',
                    'YA_KASSA_LOGGING_ON',
                    'YA_KASSA_ENABLE_HOLD_MODE_ON',
                ),
                $kassa->getTaxesArray(),
                array_values($kassa->getPaymentMethods())
            )
        );
        $vars_org['kassa']                     = $kassa;
        $shopId                                = $kassa->getShopId();
        $vars_org['YA_KASSA_NOTIFICATION_URL'] = $this->context->link->getModuleLink('yandexmodule', 'notifycapture');

        $vars_p2p           = array(
            'wallet' => $this->getWalletModel(),
        );
        $vars_metrika       = Configuration::getMultiple(array(
            'YA_METRICS_PASSWORD_APPLICATION',
            'YA_METRICS_ID_APPLICATION',
            'YA_METRICS_SET_WEBVIZOR',
            'YA_METRICS_SET_CLICKMAP',
            'YA_METRICS_SET_OTKAZI',
            'YA_METRICS_SET_HASH',
            'YA_METRICS_ACTIVE',
            'YA_METRICS_TOKEN',
            'YA_METRICS_NUMBER',
        ));
        $vars_billing       = array(
            'billing' => $this->getBillingModel(),
        );

        $settings = Configuration::getMultiple(array(
            'YA_MARKET_SHOP_NAME',
            'YA_MARKET_FULL_SHOP_NAME',
            'YA_MARKET_CURRENCY_ENABLED',
            'YA_MARKET_CURRENCY_RATE',
            'YA_MARKET_CURRENCY_PLUS',
            'YA_MARKET_CATEGORY_ALL_ENABLED',
            'YA_MARKET_CATEGORY_LIST',
            'YA_MARKET_DELIVERY_ENABLED',
            'YA_MARKET_DELIVERY_COST',
            'YA_MARKET_DELIVERY_DAYS_FROM',
            'YA_MARKET_DELIVERY_DAYS_TO',
            'YA_MARKET_DELIVERY_ORDER_BEFORE',
            'YA_MARKET_OFFER_TYPE_SIMPLE',
            'YA_MARKET_OFFER_TYPE_NAME_TEMPLATE',
            'YA_MARKET_AVAILABLE_ENABLED',
            'YA_MARKET_AVAILABLE_AVAILABLE',
            'YA_MARKET_AVAILABLE_DELIVERY',
            'YA_MARKET_AVAILABLE_PICKUP',
            'YA_MARKET_AVAILABLE_STORE',
            'YA_MARKET_VAT_ENABLED',
            'YA_MARKET_VAT_LIST',
            'YA_MARKET_COMBINATION_EXPORT_ALL',
            'YA_MARKET_OFFER_OPTIONS_EXPORT_PARAMS',
            'YA_MARKET_OFFER_OPTIONS_EXPORT_DIMENSION',
            'YA_MARKET_ADDITIONAL_CONDITION_ENABLED',
            'YA_MARKET_ADDITIONAL_CONDITION_NAME',
            'YA_MARKET_ADDITIONAL_CONDITION_TAG',
            'YA_MARKET_ADDITIONAL_CONDITION_TYPE_VALUE',
            'YA_MARKET_ADDITIONAL_CONDITION_STATIC_VALUE',
            'YA_MARKET_ADDITIONAL_CONDITION_DATA_VALUE',
            'YA_MARKET_ADDITIONAL_CONDITION_FOR_ALL_CAT',
            'YA_MARKET_ADDITIONAL_CONDITION_JOIN',
            'YA_MARKET_ADDITIONAL_CONDITION_CATEGORIES',
        ));
        if (!$settings['YA_MARKET_SHOP_NAME']) {
            $settings['YA_MARKET_SHOP_NAME'] = Configuration::get('PS_SHOP_NAME');
        }
        if (!$settings['YA_MARKET_OFFER_TYPE_NAME_TEMPLATE']) {
            $settings['YA_MARKET_OFFER_TYPE_NAME_TEMPLATE'] = '%name% %manufacturer_name%';
        }
        $settings['YA_MARKET_EXPORT_LINK_URL'] = str_replace('http://', 'https://',
            $this->context->link->getModuleLink($this->name, 'generate'));

        $forms = new YandexMoneyModule\FormHelper();

        $vars_org['YA_ORG_TEXT_INSIDE']    = $this->l('You can find your shopID and codeword in your')."<a href='https://money.yandex.ru/joinups' target='_blank'>".$this->l('Merchant Profile')."</a>".$this->l('after signing up for Yandex.Checkout.');
        $vars_p2p['YA_WALLET_LOGGING_ON']  = Configuration::get('YA_WALLET_LOGGING_ON');
        $this->context->smarty->assign(array(
            'ya_version'           => $this->version,
            'orders_link'          => $this->context->link->getAdminLink('AdminOrders', false)
                                      .'&token='.Tools::getAdminTokenLite('AdminOrders'),
            'ajax_limk_ym'         => $this->context->link->getAdminLink('AdminModules', false)
                                      .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='
                                      .$this->name.'&token='.Tools::getAdminTokenLite('AdminModules'),
            'this_path'            => $this->_path,
            'update_status'        => $this->update_status,
            'metrika_status'       => $this->metrics_status,
            'market_status'        => $this->market_status,
            'billing_status'       => $this->billing_status,
            'p2p_status'           => $this->p2p_status,
            'org_status'           => $this->org_status,
            'money_p2p'            => $this->renderForm('p2p', $vars_p2p,
                $forms->getWalletForm($this->getWalletModel())),
            'money_org'            => $this->renderForm('org', $vars_org, $forms->getKassaForm($kassa)),
            'kassa'                => $kassa,
            'emptyShopId'          => empty($shopId),
            'money_metrika'        => $this->renderForm('metrika', $vars_metrika, $forms->getFormYandexMetrics()),
            'money_market'         => $this->renderForm(
                'market',
                $settings, $forms->getFormYamoneyMarket($settings)
            ),
            'billing_form'         => $this->renderForm(
                'billing_form',
                $vars_billing,
                $forms->getBillingForm($this->getBillingModel())
            ),
            'nps_block'           => $this->nps_block,
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
        $orderId      = $order->id;
        $payment      = null;
        if (!($paymentModel instanceof \YandexMoneyModule\Models\KassaModel)) {
            $errors[] = $this->l('Module Yandex.Cash is disabled');
        } else {
            $payment = $paymentModel->findOrderPayment($orderId);
            if ($payment === null) {
                $errors[] = $this->l('Payment for order not exists');
            }
        }
        $refunds       = $paymentModel->findRefunds($orderId);
        $totalRefunded = 0;
        foreach ($refunds as $refund) {
            $totalRefunded += $refund['amount'];
        }
        $refundableAmount = $payment->getAmount()->getValue() - $totalRefunded;
        if ($refundableAmount < 0) {
            $refundableAmount = 0;
        }

        if (empty($errors) && Tools::isSubmit('return_amount')) {
            $cause  = Tools::getValue('return_comment');
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
                    $refunds[]        = array(
                        'refund_id'  => $refund->getId(),
                        'status'     => $refund->getStatus(),
                        'amount'     => $refund->getAmount()->getValue(),
                        'comment'    => $cause,
                        'created_at' => $refund->getCreatedAt()->format('Y-m-d H:i:s'),
                    );
                }
            }
        }

        $customer = new Customer($params['order']->id_customer);

        $names                 = array(
            \YandexCheckout\Model\PaymentMethodType::BANK_CARD      => $this->l('Bank cards'),
            \YandexCheckout\Model\PaymentMethodType::YANDEX_MONEY   => $this->l('Yandex.Money'),
            \YandexCheckout\Model\PaymentMethodType::SBERBANK       => $this->l('Sberbank Online'),
            \YandexCheckout\Model\PaymentMethodType::QIWI           => $this->l('QIWI Wallet'),
            \YandexCheckout\Model\PaymentMethodType::WEBMONEY       => $this->l('Webmoney'),
            \YandexCheckout\Model\PaymentMethodType::CASH           => $this->l('Cash via payment kiosks'),
            \YandexCheckout\Model\PaymentMethodType::MOBILE_BALANCE => $this->l('Direct carrier billing'),
            \YandexCheckout\Model\PaymentMethodType::ALFABANK       => $this->l('Alfa-Click'),
        );
        $paymentType           = $this->l('Способ оплаты не определён');
        $additionalPaymentInfo = '';
        if ($payment->getPaymentMethod() !== null) {
            $method = $payment->getPaymentMethod();
            if (isset($names[$method->getType()])) {
                $paymentType = $names[$payment->getPaymentMethod()->getType()];
                if ($method instanceof \YandexCheckout\Model\PaymentMethod\PaymentMethodYandexWallet) {
                    $additionalPaymentInfo = $this->l('номер кошелька: ').$method->getAccountNumber();
                } elseif ($method instanceof \YandexCheckout\Model\PaymentMethod\PaymentMethodAlfaBank) {
                    $additionalPaymentInfo = $this->l('логин в Альфа-клике: ').$method->getLogin();
                } elseif ($method instanceof \YandexCheckout\Model\PaymentMethod\PaymentMethodSberbank) {
                    $additionalPaymentInfo = $this->l('телефон: ').$method->getPhone();
                }
            }
        }

        $carrier = new Carrier($params['order']->id_carrier);
        $this->context->smarty->assign(array(
            'email'                 => $customer->email,
            'orderId'               => $orderId,
            'returnTotal'           => Tools::displayPrice($totalRefunded),
            'refundableAmount'      => $refundableAmount,
            'payment'               => $payment,
            'paymentType'           => $paymentType,
            'additionalPaymentInfo' => $additionalPaymentInfo,
            'text_success'          => $this->l('The payment is successfully returned'),
            'refunds'               => $refunds,
            'return_errors'         => $errors,
            'dname'                 => $carrier->name,
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
     *
     * @param string $level Тип сообщения (debug|info|notice|warning|error|critical|alert|emergency)
     * @param string $message Текст сообщения
     */
    public function log($level, $message)
    {
        if (Configuration::get('YA_KASSA_LOGGING_ON') != 'on') {
            return;
        }
        $logDirName = 'log_files';
        $path       = _PS_MODULE_DIR_.'/yandexmodule/'.$logDirName;
        if (!is_dir($path)) {
            if (!mkdir($path, 0777)) {
                return;
            }
        } else {
            chmod($path, 0777);
        }
        $fileName = $path.'/module.log';
        $fd       = @fopen($fileName, 'a');
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
        $path       = _PS_MODULE_DIR_.'/yandexmodule/'.$logDirName.'/module.log';
        if (file_exists($path)) {
            $content = Tools::file_get_contents($path);
        } else {
            $content = '';
        }
        header('Pragma: public');
        header('Expires: 0');
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="yandex-money_'.date('Y-m-d_H-i-s').'.log"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: '.Tools::strlen($content));
        echo $content;
        exit();
    }

    /**
     * Hook what called to display payment method for customer on PrestaShop 1.7
     *
     * @param array $params Array with order information
     *
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
        $cart                  = $params['cart'];
        $model                 = $this->getPaymentModel();
        $totalAmount           = $cart->getOrderTotal();
        $isInstallmentsEnabled = $totalAmount > \YandexMoneyModule\Models\KassaModel::MIN_INSTALLMENTS_AMOUNT;
        if ($model === null) {
            // если отключен приём платежей - ничего не делаем
            $this->log('debug', 'Payment module disabled');

            return null;
        }
        $this->context->smarty->assign(array(
            'model'                 => $model,
            'shop_name'             => Configuration::get('PS_SHOP_NAME'),
            'image_dir'             => $this->getPathUri().'/views/img/',
            'amount'                => $totalAmount,
            'isInstallmentsEnabled' => $isInstallmentsEnabled,
            'action'                => $this->context->link->getModuleLink(
                $this->name,
                $model->getPaymentActionController(),
                array(),
                true
            ),
        ));

        if ($model instanceof \YandexMoneyModule\Models\KassaModel && $model->isEnabled()) {
            if ($model->getEPL()) {
                // если используется выбор на стороне кассы
                // отображаем шаблон пустым типом платежа
                $this->log('debug', 'Show EPL page');
                $template = 'module:yandexmodule/views/templates/hook/1.7/kassa_epl_form.tpl';
            } else {
                // если используется выбор на стороне магазина,
                // добавляем в шаблон список способов оплаты
                $this->log('debug', 'Show select payment method page');
                $methods  = $model->getEnabledPaymentMethods();
                $payments = Configuration::getMultiple(array_values($model->getPaymentMethods()));
                $key      = 'YA_KASSA_PAYMENT_INSTALLMENTS';
                if (isset($payments[$key]) && $payments[$key] == '1') {
                    $monthlyInstallment = \YandexMoneyModule\InstallmentsApi::creditPreSchedule(
                        $model->getShopId(),
                        $totalAmount
                    );
                    if (!isset($monthlyInstallment['amount'])) {
                        $errorMessage = \YandexMoneyModule\InstallmentsApi::getLastError() ?: 'Unknown error. Could not get installment amount';
                        $this->log('error', $errorMessage);
                    } else {
                        $installmentLabel = sprintf($this->l('Installments (%s ₽ per month)'),
                            $monthlyInstallment['amount']);
                        $this->log('info', 'Label: '.$installmentLabel);
                        foreach ($methods as $key => $method) {
                            if ($method['value'] == \YandexCheckout\Model\PaymentMethodType::INSTALLMENTS) {
                                if ($isInstallmentsEnabled) {
                                    $methods[$key]['name'] = $installmentLabel;
                                } else {
                                    unset($methods[$key]);
                                }
                            }
                        }
                    }
                }

                if (empty($methods)) {
                    // если мерчант не выбрал ни одного способа оплаты, не
                    // отображаем ничего, модуль настроен некорректно
                    $this->module->log('warning', 'Empty payment method list');

                    return null;
                }
                $template = 'module:yandexmodule/views/templates/hook/1.7/kassa_form.tpl';
                $this->context->smarty->assign('label', 'Please select payment method');
                $this->context->smarty->assign('payment_methods', $methods);
            }
        } else {
            $template = $model->assignVariables($this->context->smarty);
        }

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
                              'Yandex.Checkout (bank cards, e-money, etc.)',
                              array(),
                              'Modules.YandexModule.Shop'
                          )
                      )
                      ->setForm($display);

        $this->log('debug', 'Payment_options: '.json_encode($paymentOption));

        return array(
            $paymentOption,
        );
    }

    public function hookDisplayFooterProduct($params) {
        $product = (object)$params['product'];
        $price = round(Product::getPriceStatic($product->id), 2);
        $html = '
<script type="text/javascript">
    document.addEventListener("DOMContentLoaded", ecommerceDetailProducts);
    function ecommerceDetailProducts() {
        window.dataLayer = window.dataLayer || [];
        dataLayer.push({
            ecommerce: {
                detail: {
                    products: [
                        {
                            id: "'.$product->id.'",
                            name : "'.$product->name.'",
                            price: '.$price.',
                            category: "'.$params['category']->name.'"
                        }
                    ]
                }
            }
        });
    }
</script>';
        return $html;
    }

    public function hookDisplayPayment($params)
    {
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }
        $cart            = $this->context->cart;
        $total_to_pay    = $cart->getOrderTotal(true);
        $rub_currency_id = Currency::getIdByIsoCode('RUB');
        if ($cart->id_currency != $rub_currency_id) {
            $from_currency = new Currency($cart->id_curre1ncy);
            $to_currency   = new Currency($rub_currency_id);
            $total_to_pay  = Tools::convertPriceFull($total_to_pay, $from_currency, $to_currency);
        }
        $this->context->smarty->assign(array(
            'summ'          => number_format($total_to_pay, 2, '.', ''),
            'this_path'     => $this->_path,
            'this_path_ssl' => Tools::getHttpHost(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/',
        ));
        $display = '';
        if ($this->getKassaModel()->isEnabled()) {
            $kassa = $this->getKassaModel();
            $this->context->smarty->assign(array(
                'model'      => $kassa,
                'shop_name'  => Configuration::get('PS_SHOP_NAME'),
                'image_dir'  => $this->getPathUri().'/views/img/',
                'action'     => $this->context->link->getModuleLink($this->name, 'paymentkassa', array(), true),
                'buttontext' => $this->l('Yandex.Kassa (bank cards, e-money and other)'),
            ));
            if ($kassa->getEPL()) {
                // for epl show template without payment type options
                $this->log('debug', 'Show EPL page');
                $template = 'kassa_epl_form.tpl';
                $this->smarty->assign('amount', $total_to_pay);
            } else {
                // add payment type options to template variables
                $this->log('debug', 'Show select payment method page');
                $methods = $kassa->getEnabledPaymentMethods();
                if (empty($methods)) {
                    // without any payment method do not display module template
                    $this->log('warning', 'Empty payment method list');

                    return null;
                } else {
                    foreach ($methods as $key => $method) {
                        if ($method['value'] == \YandexCheckout\Model\PaymentMethodType::INSTALLMENTS) {
                            if ($total_to_pay > \YandexMoneyModule\Models\KassaModel::MIN_INSTALLMENTS_AMOUNT) {
                                $monthlyInstallment = \YandexMoneyModule\InstallmentsApi::creditPreSchedule(
                                    $kassa->getShopId(),
                                    $total_to_pay
                                );
                                if (!isset($monthlyInstallment['amount'])) {
                                    $errorMessage = \YandexMoneyModule\InstallmentsApi::getLastError() ?: 'Unknown error. Could not get installment amount';
                                    $this->log('error', $errorMessage);
                                } else {
                                    $installmentLabel      = sprintf($this->l('Installments (%s ₽ per month)'),
                                        $monthlyInstallment['amount']);
                                    $methods[$key]['name'] = $installmentLabel;
                                }
                            } else {
                                unset($methods[$key]);
                            }
                        }
                    }
                }
                $template = 'kassa_form.tpl';
                $this->smarty->assign('label', $this->l('Please select payment method'));
                $this->smarty->assign('payment_methods', $methods);
            }
            // получаем отображаемый контент
            $display = $this->display(__FILE__, '1.6/'.$template);
            if (empty($display)) {
                // if template is empty - go away
                $this->log('warning', 'Empty payment template');

                return null;
            }
        } elseif ($this->getWalletModel()->isEnabled()) {
            $this->context->smarty->assign(array(
                'wallet' => $this->getWalletModel(),
                'price'  => number_format($total_to_pay, 2, '.', ''),
                'cart'   => $this->context->cart,
            ));
            $display .= $this->display(__FILE__, '1.6/wallet_form.tpl');
        } elseif ($this->getBillingModel()->isEnabled()) {
            $this->context->smarty->assign(array(
                'billing' => $this->getBillingModel(),
                'price'   => number_format($total_to_pay, 2, '.', ''),
                'cart'    => $this->context->cart,
            ));
            $display .= $this->display(__FILE__, '1.6/billing_form.tpl');
        }

        return $display;
    }

    /**
     * Хук вызываемый при возврате пользователя из кассы
     *
     * @param array $params Массив с контекстом информации о платеже
     *
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
                (empty($params['order']) ? 'Empty' : 'Invalid').' order in return hook: '.json_encode($params)
            );

            return '';
        }

        /** @var Order $order Инстанс заказа, который оплачивал пользователь */
        $order   = $params[$key];
        $kassa   = $this->getKassaModel();
        $payment = $kassa->findOrderPayment($order->id);
        $success = $payment !== null && $payment->getPaid();
        if ($success) {
            $this->smarty->assign(array(
                'shop_name'   => $this->context->shop->name,
                'total'       => Tools::displayPrice(
                    $order->getOrdersTotalPaid(),
                    new Currency($order->id_currency),
                    false
                ),
                'reference'   => $order->reference,
                'contact_url' => $this->context->link->getPageLink('contact', true),
            ));
        }
        $template = 'kassa_payment_'.($success ? 'success' : 'failure');
        if (version_compare(_PS_VERSION_, '1.7.0') > 0) {
            return $this->fetch('module:yandexmodule/views/templates/hook/'.$template.'.tpl');
        } else {
            return $this->display(__FILE__, $template.'.tpl');
        }
    }

    /**
     * @param string $paymentId
     * @return bool
     */
    public function captureOrHoldPayment($paymentId)
    {
        $kassa   = $this->getKassaModel();
        $payment = $kassa->getPayment($paymentId);
        if ($payment === null) {
            $this->log('error', 'Failed to fetch payment object in notification');

            return false;
        }
        if ($payment->getStatus() !== \YandexCheckout\Model\PaymentStatus::WAITING_FOR_CAPTURE) {
            $this->log('error', 'Wrong status for capture: '.$payment->getStatus());

            return false;
        }
        $kassa->updatePaymentStatus($payment);
        $orderId = $kassa->getOrderIdByPayment($payment);

        if ($kassa->getEnableHoldMode()
            && $payment->getPaymentMethod()->getType() === \YandexCheckout\Model\PaymentMethodType::BANK_CARD
        ) {
            $this->log('debug', 'Hold payment for order #'.$orderId);
            $history           = new OrderHistory();
            $history->id_order = $orderId;
            $history->changeIdOrderState($kassa->getOnHoldStatusId(), $orderId);
            $history->addWithemail(true);
            $this->getKassaModel()->updateOrderPaymentId($orderId, $payment);

            return true;
        }

        $this->log('debug', 'Capture payment for order #'.$orderId);
        $response = $kassa->capturePayment($payment);
        if ($response !== null && $response->getStatus() === \YandexCheckout\Model\PaymentStatus::SUCCEEDED) {
            $history           = new OrderHistory();
            $history->id_order = $orderId;
            $history->changeIdOrderState($kassa->getSuccessStatusId(), $orderId);
            $history->addWithemail(true);
            $this->getKassaModel()->updateOrderPaymentId($orderId, $payment);
            return true;
        }

        $this->log('error', 'Failed to capture payment '.$payment->getId());
        return false;
    }

    public function hookDisplayOrderConfirmation($params)
    {
        if (!Configuration::get('YA_METRICS_ACTIVE')) {
            return false;
        }

        if (version_compare(_PS_VERSION_, '1.7.0') < 0) {
            $orderId      = (string)$params['objOrder']->id;
            $currencyCode = $params['currencyObj']->iso_code;
            $products     = array();
            foreach ($params['objOrder']->getCartProducts() as $k => $product) {
                $products[$k]['id']       = (string)$product['product_id'];
                $products[$k]['name']     = $product['product_name'];
                $products[$k]['quantity'] = (int)$product['product_quantity'];
                $products[$k]['price']    = round(Product::getPriceStatic($product['product_id']), 2);
            }
        } else {
            /** @var Order $order */
            $order        = $params['order'];
            $orderId      = (string)$order->id;
            $currency     = new Currency($order->id_currency);
            $currencyCode = $currency->iso_code;
            $products     = array();
            /** @var Cart $cart */
            $cart = Cart::getCartByOrderId($orderId);
            foreach ($cart->getProducts(true) as $k => $product) {
                $products[$k]['id']       = $product['id_product'];
                $products[$k]['name']     = $product['name'];
                $products[$k]['quantity'] = $product['quantity'];
                $products[$k]['price']    = round(Product::getPriceStatic((int)$product['id_product']), 2);
            }
        }

        $result = array(
            "ecommerce" => array(
                "currencyCode" => $currencyCode,
                "purchase"     => array(
                    "actionField" => array(
                        "id" => $orderId,
                    ),
                    "products"    => $products,
                ),
            ),
        );
        $data   = '<script>
                window.dataLayer = window.dataLayer || [];
                document.addEventListener("DOMContentLoaded", ecommercePurchase);
                function ecommercePurchase() {
                    dataLayer.push('.Tools::jsonEncode($result).');
                }
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
        $helper                        = new HelperForm();
        $helper->show_toolbar          = false;
        $helper->table                 = $this->table;
        $helper->module                = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->identifier            = $this->identifier;
        $helper->submit_action         = 'submit'.$mod.'Module';
        $helper->currentIndex          = $this->context->link->getAdminLink('AdminModules', false)
                                         .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token                 = Tools::getAdminTokenLite('AdminModules');
        foreach ($form['form']['input'] as $item) {
            if (isset($item['value'])) {
                $vars[$item['name']] = $item['value'];
            }
        }
        $helper->fields_value                             = $vars;
        $p2p_redirect                                     = $this->context->link->getModuleLink($this->name,
            'callbackwallet');
        $httpsBasePath                                    = str_replace('http://', 'https://',
            _PS_BASE_URL_.__PS_BASE_URI__);
        $redir                                            = $httpsBasePath.'modules/yandexmodule/callback.php';
        $helper->fields_value['YA_WALLET_REDIRECT']       = $p2p_redirect;
        $helper->fields_value['YA_METRICS_REDIRECT'] = $redir;

        return $helper->generateForm(array($form));
    }

    public function checkCurrency($cart)
    {
        $currency_order    = new Currency((int)$cart->id_currency);
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
            'invalid_request'            => $this->l(
                'Your request is missing required parameters or settings are incorrect or invalid values'
            ),
            'invalid_scope'              => $this->l(
                'The scope parameter is missing or has an invalid value or a logical contradiction'
            ),
            'unauthorized_client'        => $this->l(
                'Invalid parameter client_id, or the application does not have the'.
                ' right to request authorization (such as its client_id blocked Yandex.Money)'
            ),
            'access_denied'              => $this->l('Has declined a request authorization application'),
            'invalid_grant'              => $this->l(
                'The issue access_token denied. Issued a temporary token is not '.
                'Google search or expired, or on the temporary token is issued access_token (second '.
                'request authorization token with the same time token)'
            ),
            'illegal_params'             => $this->l('Required payment options are not available or have invalid values.'),
            'illegal_param_label'        => $this->l('Invalid parameter value label'),
            'phone_unknown'              => $this->l('A phone number is not associated with a user account or payee'),
            'payment_refused'            => $this->l(
                'The store refused to accept payment (for example, a user tried '.
                'to pay for a product that isn\'t in the store)'
            ),
            'limit_exceeded'             => $this->l(
                'Exceeded one of the limits on operations: on the amount of the '.
                'transaction for authorization token issued; transaction amount for the period of time'.
                ' for the token issued by the authorization; Yandeks.Deneg restrictions '.
                'for different types of operations.'
            ),
            'authorization_reject'       => $this->l(
                'In payment authorization is denied. Possible reasons are:'.
                ' transaction with the current parameters is not available to the user; person does not'.
                ' accept the Agreement on the use of the service "shops".'
            ),
            'contract_not_found'         => $this->l('None exhibited a contract with a given request_id'),
            'not_enough_funds'           => $this->l(
                'Insufficient funds in the account of the payer. '.
                'Need to recharge and carry out a new delivery'
            ),
            'not-enough-funds'           => $this->l(
                'Insufficient funds in the account of the payer.'.
                ' Need to recharge and carry out a new delivery'
            ),
            'money_source_not_available' => $this->l(
                'The requested method of payment (money_source) '.
                'is not available for this payment'
            ),
            'illegal_param_csc'          => $this->l('Empty or an invalid parameter value cs'),
            'payment_refused'            => $this->l('Shop for whatever reason, refused to accept payment.'),
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

    /**
     * @param array $params
     * @return string
     */
    public function hookDisplayAdminOrder($params)
    {
        if (empty($params['id_order'])) {
            return '';
        }
        $orderId = $params['id_order'];
        $order   = new Order($orderId);

        if ((int)$order->getCurrentState() !== $this->getKassaModel()->getOnHoldStatusId()) {
            return '';
        }

        $payment = $this->getKassaModel()->findOrderPayment($orderId);
        if (!$payment || $payment->getStatus() !== \YandexCheckout\Model\PaymentStatus::WAITING_FOR_CAPTURE) {
            return '';
        }
        $title        = $this->l('Отложенная оплата');
        $text = sprintf('Поступил новый платёж. Он ожидает подтверждения до %1$s, после чего автоматически отменится',
            $payment->getExpiresAt()->format('d.m.Y H:i'));
        $capture      = $this->l('Подтвердить');
        $cancel       = $this->l('Отменить');
        $token        = Tools::getAdminTokenLite(self::ADMIN_CONTROLLER);
        $controller   = self::ADMIN_CONTROLLER;
        $successCapture = $this->l('Вы подтвердили платёж в Яндекс.Кассе.');
        $errorCapture = $this->l('Платёж не подтвердился. Попробуйте ещё раз.');
        $successCancel = $this->l('Вы отменили платёж в Яндекс.Кассе. Деньги вернутся клиенту.');
        $errorCancel  = $this->l('Платёж не отменился. Попробуйте ещё раз.');

        $html = <<<HTML
<div class="panel">
    <div class="panel-heading">
        <i class="icon-shopping-cart"></i>
        $title
    </div>
    <div>
        $text
    </div>
    <div>
        <button type="button" class="btn btn-default" id="ya_kassa_capture_payment">
            <i class="icon-check"></i> 
            $capture
        </button>
        <button type="button" class="btn btn-default" id="ya_kassa_cancel_payment">
            <i class="icon-remove"></i> 
            $cancel
        </button>
    </div>
</div>
<script type="text/javascript">
$(document).ready(function() {
    function ya_kassa_disable_payment_action_buttons() {
        $('#ya_kassa_capture_payment').addClass('disabled');           
        $('#ya_kassa_cancel_payment').addClass('disabled');           
    }
    $('#ya_kassa_capture_payment').on('click', function() {
        ya_kassa_disable_payment_action_buttons();
        $.ajax({
            type: "POST",
            url: "ajax-tab.php",
            data : {
					ajax: "1",
					order_id: "$orderId",
					token: "$token",
					controller: "$controller",
					action: "capturePayment",
				},
            dataType: "json",
            complete: function(data) {
                if (data && data.responseJSON && data.responseJSON.result && data.responseJSON.result === 'success') {
                    alert('$successCapture');
                } else {
                    alert('$errorCapture');
                }
                location.reload();                    
            }
        });
    });
    $('#ya_kassa_cancel_payment').on('click', function() {
        ya_kassa_disable_payment_action_buttons();
        $.ajax({
            type: "POST",
            url: "ajax-tab.php",
            data : {
					ajax: "1",
					order_id: "$orderId",
					token: "$token",
					controller: "$controller",
					action: "cancelPayment",
				},
            dataType: "json",
            complete: function(data) {
                if (data && data.responseJSON && data.responseJSON.result && data.responseJSON.result === 'success') {
                    alert('$successCancel');
                } else {
                    alert('$errorCancel');
                }
                location.reload();
            }
        });
    });
});
</script>
HTML;

        return $html;
    }

}
