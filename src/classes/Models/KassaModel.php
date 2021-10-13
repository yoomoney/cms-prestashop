<?php
/**
 * @author YooMoney <cms@yoomoney.ru>
 * @copyright © 2020 "YooMoney", NBСO LLC
 * @license  https://yoomoney.ru/doc.xml?id=527052
 */

namespace YooMoneyModule\Models;

use Carrier;
use Cart;
use Configuration;
use Context;
use Currency;
use Db;
use DbQuery;
use Product;
use Tax;
use Tools;

use YooKassa\Client;
use YooKassa\Common\Exceptions\NotFoundException;
use YooKassa\Model\ConfirmationType;
use YooKassa\Model\Payment;
use YooKassa\Model\PaymentInterface;
use YooKassa\Model\PaymentMethodType;
use YooKassa\Model\RefundInterface;
use YooKassa\Model\RefundStatus;
use YooKassa\Request\Payments\CreatePaymentRequest;
use YooKassa\Request\Payments\CreatePaymentRequestBuilder;
use YooKassa\Request\Payments\CreatePaymentResponse;
use YooKassa\Request\Payments\Payment\CreateCaptureRequest;
use YooKassa\Request\Payments\Payment\CreateCaptureRequestBuilder;
use YooKassa\Request\Payments\Payment\CreateCaptureRequestSerializer;
use YooKassa\Request\Refunds\CreateRefundRequest;
use YooMoneyModule;

class KassaModel extends AbstractPaymentModel
{
    /**
     * Минимальная сумма заказа для платежа с методом кредитования.
     */
    const MIN_INSTALLMENTS_AMOUNT = 3000;

    /**
     * Статус заказа по умолчанию(если не сохранили настройки)
     */
    const DEFAULT_PAYMENT_STATUS = _PS_OS_PREPARATION_;

    private static $disabledPaymentMethods = array(
        PaymentMethodType::B2B_SBERBANK,
        PaymentMethodType::WECHAT,
    );

    const PAYMENT_METHOD_WIDGET = 'widget';

    private static $customPaymentMethods = array(
        self::PAYMENT_METHOD_WIDGET,
    );

    private $shopId;
    private $password;
    private $epl;
    private $showInstallmentsButton;
    private $availablePaymentMethods;
    private $enabledPaymentMethods;
    private $sendReceipt;
    private $defaultTaxRate;
    private $defaultTaxSystemCode;
    private $minimumAmount;
    private $paymentDescription;
    private $debugLog;
    private $createStatusId;
    private $successStatusId;
    private $enableHoldMode;
    private $onHoldStatusId;
    private $cancelStatusId;
    private $apiClient;
    private $defaultPaymentMode;
    private $defaultPaymentSubject;
    private $defaultDeliveryPaymentMode;
    private $defaultDeliveryPaymentSubject;


    public function initConfiguration()
    {
        $this->enabled                       = Configuration::get('YOOMONEY_KASSA_ACTIVE') == '1';
        $this->shopId                        = Configuration::get('YOOMONEY_KASSA_SHOP_ID');
        $this->password                      = Configuration::get('YOOMONEY_KASSA_PASSWORD');
        $this->epl                           = Configuration::get('YOOMONEY_KASSA_PAYMENT_MODE') == 'kassa';
        $this->showInstallmentsButton        = Configuration::get('YOOMONEY_KASSA_INSTALLMENTS_BUTTON_ON') == 'on';
        $this->sendReceipt                   = Configuration::get('YOOMONEY_KASSA_SEND_RECEIPT') == '1';
        $this->defaultTaxRate                = (int)Configuration::get('YOOMONEY_KASSA_DEFAULT_TAX_RATE');
        $this->defaultTaxSystemCode          = (int)Configuration::get('YOOMONEY_KASSA_DEFAULT_TAX_SYSTEM');
        $this->minimumAmount                 = (float)Configuration::get('YOOMONEY_KASSA_MIN');
        $this->debugLog                      = Configuration::get('YOOMONEY_KASSA_LOGGING_ON') == 'on';
        $this->createStatusId                = (int)Configuration::get('YOOMONEY_KASSA_DEFAULT_PAYMENT_INIT_STATUS');
        $this->successStatusId               = (int)Configuration::get('YOOMONEY_KASSA_SUCCESS_STATUS_ID');
        $this->enableHoldMode                = Configuration::get('YOOMONEY_KASSA_ENABLE_HOLD_MODE_ON') === 'on';
        $this->onHoldStatusId                = (int)Configuration::get('YOOMONEY_KASSA_ON_HOLD_STATUS_ID');
        $this->cancelStatusId                = (int)Configuration::get('YOOMONEY_KASSA_CANCEL_STATUS_ID');
        $this->paymentDescription            = Configuration::get('YOOMONEY_KASSA_PAYMENT_DESCRIPTION');
        $this->defaultPaymentMode            = Configuration::get('YOOMONEY_KASSA_DEFAULT_PAYMENT_MODE');
        $this->defaultPaymentSubject         = Configuration::get('YOOMONEY_KASSA_DEFAULT_PAYMENT_SUBJECT');
        $this->defaultDeliveryPaymentMode    = Configuration::get('YOOMONEY_KASSA_DEFAULT_DELIVERY_PAYMENT_MODE');
        $this->defaultDeliveryPaymentSubject = Configuration::get('YOOMONEY_KASSA_DEFAULT_DELIVERY_PAYMENT_SUBJECT');

        if (!$this->paymentDescription) {
            $this->paymentDescription = $this->module->l('Payment for order No. %cart_id%');
        }

        $this->paymentActionController = 'paymentkassa';
    }

    /**
     * Возвращает айди магазина
     * @return string ID магазина
     */
    public function getShopId()
    {
        return $this->shopId;
    }

    /**
     * Возвращает пароль магазина
     * @return string Пароль магазина
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Используется ли выбор способа оплаты на стороне кассы
     * @return bool
     */
    public function getEPL()
    {
        return $this->epl;
    }

    /**
     * Возвращает флаг отправки чека
     * @return bool
     */
    public function getSendReceipt()
    {
        return $this->sendReceipt;
    }

    /**
     * Возвращает ставку НДС, используемую по умолчанию
     * @return int Ставка НДС используемая по умолчанию
     */
    public function getDefaultTaxRate()
    {
        return $this->defaultTaxRate;
    }

    /**
     * Возвращает минимальную сумму заказа
     * @return float
     */
    public function getMinimumAmount()
    {
        return $this->minimumAmount;
    }

    /**
     * @return array Список возможных способов оплаты
     */
    public function getPaymentMethods()
    {
        if ($this->availablePaymentMethods === null) {
            $this->availablePaymentMethods = array();
            $enabledPaymentMethods = array_merge(PaymentMethodType::getEnabledValues(), self::getCustomPaymentMethods());
            foreach ($enabledPaymentMethods as $value) {
                if (!in_array($value, self::getDisabledPaymentMethods())) {
                    $this->availablePaymentMethods[$value] = 'YOOMONEY_KASSA_PAYMENT_'.Tools::strtoupper($value);
                }
            }
        }

        return $this->availablePaymentMethods;
    }

    /**
     * @return array Массив используемых способов оплаты
     */
    public function getEnabledPaymentMethods()
    {
        if ($this->enabledPaymentMethods === null) {
            $this->enabledPaymentMethods = array();
            $paymentMethodNames          = array(
                PaymentMethodType::YOO_MONEY      => $this->module->l('YooMoney'),
                PaymentMethodType::BANK_CARD      => $this->module->l('Bank cards'),
                PaymentMethodType::MOBILE_BALANCE => $this->module->l('Direct carrier billing'),
                PaymentMethodType::WEBMONEY       => $this->module->l('Webmoney'),
                PaymentMethodType::CASH           => $this->module->l('Cash via payment kiosks'),
                PaymentMethodType::SBERBANK       => $this->module->l('SberPay'),
                PaymentMethodType::ALFABANK       => $this->module->l('Alfa-Click'),
                PaymentMethodType::QIWI           => $this->module->l('QIWI Wallet'),
                PaymentMethodType::TINKOFF_BANK   => $this->module->l('Интернет-банк Тинькофф'),
                PaymentMethodType::INSTALLMENTS   => $this->module->l('Installments (%s Р per month)'),
                self::PAYMENT_METHOD_WIDGET       => $this->module->l('Bank cards, Apple Pay, Google Play'),
            );

            $payments = Configuration::getMultiple(array_values($this->getPaymentMethods()));

            foreach ($paymentMethodNames as $id => $loc) {
                $key = 'YOOMONEY_KASSA_PAYMENT_'.Tools::strtoupper($id);
                if (isset($payments[$key]) && $payments[$key] == '1') {
                    $this->enabledPaymentMethods[] = array(
                        'name'  => $loc,
                        'id'    => $key,
                        'value' => $id,
                    );
                }
            }
        }

        return $this->enabledPaymentMethods;
    }

    /**
     * Проеряет, доступен ли переданный способ оплаты
     *
     * @param string $methodId Код способа оплаты
     *
     * @return bool True если способ оплаты доступен, false если нет
     */
    public function isPaymentMethodEnabled($methodId)
    {
        foreach ($this->getEnabledPaymentMethods() as $method) {
            if ($method['value'] == $methodId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Возвращает информацию о способе оплаты
     *
     * @param string $methodId Код способа оплаты
     *
     * @return array|null Массив с информацией о способе оплаты, или null если
     * способ оплаты не найден или не активен
     */
    public function getPaymentMethodInfo($methodId)
    {
        foreach ($this->getEnabledPaymentMethods() as $method) {
            if ($method['value'] == $methodId) {
                return $method;
            }
        }

        return null;
    }

    /**
     * Айди статуса заказа, выставляемый при его создании
     * @return int Айди статуса заказа сразу после создания
     */
    public function getCreateStatusId()
    {
        return empty($this->createStatusId) ? self::DEFAULT_PAYMENT_STATUS : $this->createStatusId;
    }

    /**
     * @return int
     */
    public function getSuccessStatusId()
    {
        return $this->successStatusId;
    }

    /**
     * @return bool
     */
    public function getEnableHoldMode()
    {
        return $this->enableHoldMode;
    }

    /**
     * @return int
     */
    public function getOnHoldStatusId()
    {
        return $this->onHoldStatusId;
    }

    /**
     * @return int
     */
    public function getCancelStatusId()
    {
        return $this->cancelStatusId;
    }

    public function validateOptions()
    {
        $errors = '';

        $this->enabled = Tools::getValue('YOOMONEY_KASSA_ACTIVE') == '1';
        Configuration::UpdateValue('YOOMONEY_KASSA_ACTIVE', $this->enabled ? 1 : 0);

        if ($this->enabled) {
            Configuration::UpdateValue('YOOMONEY_WALLET_ACTIVE', 0);
        }

        $this->defaultTaxRate = (int)Tools::getValue('YOOMONEY_KASSA_DEFAULT_TAX_RATE');
        Configuration::UpdateValue('YOOMONEY_KASSA_DEFAULT_TAX_RATE', $this->defaultTaxRate);

        $this->defaultTaxSystemCode = (int)Tools::getValue('YOOMONEY_KASSA_DEFAULT_TAX_SYSTEM');
        Configuration::UpdateValue('YOOMONEY_KASSA_DEFAULT_TAX_SYSTEM', $this->defaultTaxSystemCode);

        Configuration::UpdateValue('YOOMONEY_KASSA_SEND_RECEIPT', Tools::getValue('YOOMONEY_KASSA_SEND_RECEIPT'));
        $this->sendReceipt = Tools::getValue('YOOMONEY_KASSA_SEND_RECEIPT') == '1';

        $this->minimumAmount = (float)Tools::getValue('YOOMONEY_KASSA_MIN');
        Configuration::UpdateValue('YOOMONEY_KASSA_MIN', $this->minimumAmount);

        $count = 0;
        foreach ($this->getPaymentMethods() as $method) {
            Configuration::UpdateValue($method, Tools::getValue($method));
            if (Tools::getValue($method) == '1') {
                $count++;
            }
        }

        Configuration::UpdateValue('YOOMONEY_KASSA_LOGGING_ON', Tools::getValue('YOOMONEY_KASSA_LOGGING_ON'));

        Configuration::UpdateValue('YOOMONEY_KASSA_PAY_LOGO_ON', Tools::getValue('YOOMONEY_KASSA_PAY_LOGO_ON'));

        Configuration::UpdateValue('YOOMONEY_KASSA_INSTALLMENTS_BUTTON_ON',
            Tools::getValue('YOOMONEY_KASSA_INSTALLMENTS_BUTTON_ON'));

        Configuration::UpdateValue('YOOMONEY_KASSA_PAYMENT_DESCRIPTION',
            Tools::getValue('YOOMONEY_KASSA_PAYMENT_DESCRIPTION'));
        $this->paymentDescription = Tools::getValue('YOOMONEY_KASSA_PAYMENT_DESCRIPTION');

        Configuration::UpdateValue('YOOMONEY_KASSA_PAYMENT_MODE', Tools::getValue('YOOMONEY_KASSA_PAYMENT_MODE'));
        $this->epl = Tools::getValue('YOOMONEY_KASSA_PAYMENT_MODE') == 'kassa';

        $this->createStatusId = (int)Tools::getValue('YOOMONEY_KASSA_DEFAULT_PAYMENT_INIT_STATUS');
        Configuration::UpdateValue('YOOMONEY_KASSA_DEFAULT_PAYMENT_INIT_STATUS', $this->createStatusId);

        $this->successStatusId = (int)Tools::getValue('YOOMONEY_KASSA_SUCCESS_STATUS_ID');
        Configuration::UpdateValue('YOOMONEY_KASSA_SUCCESS_STATUS_ID', $this->successStatusId);

        $this->enableHoldMode = Tools::getValue('YOOMONEY_KASSA_ENABLE_HOLD_MODE_ON') === 'on';
        Configuration::UpdateValue('YOOMONEY_KASSA_ENABLE_HOLD_MODE_ON', Tools::getValue('YOOMONEY_KASSA_ENABLE_HOLD_MODE_ON'));
        if ($this->enableHoldMode) {
            $module = new YooMoneyModule();
            $module->installTabIfNeeded();
        }

        if (Tools::getValue("YOOMONEY_KASSA_PAYMENT_WIDGET") == '1') {
            if (!$this->installVerifyAppleFile()) {
                $errors .= $this->module->displayWarning(
                    htmlspecialchars_decode($this->module->l('Чтобы покупатели могли заплатить вам через Apple Pay, <a href=\"https://yookassa.ru/docs/merchant.ru.yandex.kassa\">скачайте файл apple-developer-merchantid-domain-association</a> и добавьте его в папку ./well-known на вашем сайте. Если не знаете, как это сделать, обратитесь к администратору сайта или в поддержку хостинга. Не забудьте также подключить оплату через Apple Pay <a href=\"https://yookassa.ru/my/payment-methods/settings#applePay\">в личном кабинете Кассы</a>. <a href=\"https://yookassa.ru/developers/payment-forms/widget#apple-pay-configuration\">Почитать о подключении Apple Pay в документации Кассы</a>'))
                );
            }
        }

        $this->onHoldStatusId = (int)Tools::getValue('YOOMONEY_KASSA_ON_HOLD_STATUS_ID');
        Configuration::UpdateValue('YOOMONEY_KASSA_ON_HOLD_STATUS_ID', $this->onHoldStatusId);

        $this->cancelStatusId = (int)Tools::getValue('YOOMONEY_KASSA_CANCEL_STATUS_ID');
        Configuration::UpdateValue('YOOMONEY_KASSA_CANCEL_STATUS_ID', $this->cancelStatusId);

        $this->defaultPaymentMode = Tools::getValue('YOOMONEY_KASSA_DEFAULT_PAYMENT_MODE');
        Configuration::UpdateValue('YOOMONEY_KASSA_DEFAULT_PAYMENT_MODE', $this->defaultPaymentMode);

        $this->defaultPaymentSubject = Tools::getValue('YOOMONEY_KASSA_DEFAULT_PAYMENT_SUBJECT');
        Configuration::UpdateValue('YOOMONEY_KASSA_DEFAULT_PAYMENT_SUBJECT', $this->defaultPaymentSubject);

        $this->defaultDeliveryPaymentMode = Tools::getValue('YOOMONEY_KASSA_DEFAULT_DELIVERY_PAYMENT_MODE');
        Configuration::UpdateValue('YOOMONEY_KASSA_DEFAULT_DELIVERY_PAYMENT_MODE', $this->defaultDeliveryPaymentMode);

        $this->defaultDeliveryPaymentSubject = Tools::getValue('YOOMONEY_KASSA_DEFAULT_DELIVERY_PAYMENT_SUBJECT');
        Configuration::UpdateValue('YOOMONEY_KASSA_DEFAULT_DELIVERY_PAYMENT_SUBJECT', $this->defaultDeliveryPaymentSubject);

        foreach ($this->getTaxesArray() as $taxRow) {
            Configuration::UpdateValue($taxRow, Tools::getValue($taxRow));
        }

        $isShopIdValid = false;
        if (Tools::getValue('YOOMONEY_KASSA_SHOP_ID') == '') {
            $errors .= $this->module->displayError($this->module->l('ShopId not specified!'));
        } else {
            $isShopIdValid = true;
            $this->shopId  = trim(Tools::getValue('YOOMONEY_KASSA_SHOP_ID'));
            Configuration::UpdateValue('YOOMONEY_KASSA_SHOP_ID', $this->shopId);
        }

        if (Tools::getValue('YOOMONEY_KASSA_PASSWORD') == '') {
            $errors .= $this->module->displayError($this->module->l('The password is not specified!'));
        } else {
            $this->password = trim(Tools::getValue('YOOMONEY_KASSA_PASSWORD'));
            Configuration::UpdateValue('YOOMONEY_KASSA_PASSWORD', $this->password);

            if ($isShopIdValid) {
                if (!$this->testConnection()) {
                    $errors .= $this->module->displayError(
                        $this->module->l('Check shopId and Secret key—there is an error somewhere. Better yet, copy them directly from your ')
                        .'<a href="https://yookassa.ru/my" target="_blank">'.$this->module->l('YooKassa\'s Merchant Profile').'</a>'
                    );
                    if ($this->enabled) {
                        $this->enabled = false;
                        Configuration::UpdateValue('YOOMONEY_KASSA_ACTIVE', 0);
                    }
                } elseif (strncmp('test_', Tools::getValue('YOOMONEY_KASSA_PASSWORD'), 5) === 0) {
                    $errors .= $this->module->displayWarning(
                        $this->module->l('You have enabled the test mode. Check the payment making process and contact YooKassa\'s manager. They will provide you with shopId the Secret key.')
                        .' <a href="https://yookassa.ru/docs/support/payments/onboarding/integration" target="_blank">'.$this->module->l('Manual').'</a>'
                    );
                }
            }
        }

        if (!$this->epl && $count == 0) {
            $errors .= $this->module->displayError(
                $this->module->l('Please select at least one option from the list')
            );
        }

        if ($errors == '') {
            $errors = $this->module->displayConfirmation($this->module->l('Settings saved successfully!'));
        }

        return $errors;
    }

    /**
     * @param string $isoCode
     *
     * @return string
     */
    public function getNpsBlock($isoCode)
    {
        if ($isoCode !== 'ru') {
            return '';
        }

        if (substr(Configuration::get('YOOMONEY_KASSA_PASSWORD'), 0, 5) !== 'live_') {
            return '';
        }

        if (time() < Configuration::get('YOOMONEY_NPS_VOTE_TIME') + YooMoneyModule::NPS_RETRY_AFTER_DAYS * 86400) {
            return '';
        }

        $token = Tools::getAdminTokenLite(YooMoneyModule::ADMIN_CONTROLLER);

        return $this->module->displayConfirmation('Помогите нам улучшить модуль ЮKassa — ответьте на 
            <a href="#" onclick="return false;" class="yoomoney_nps_link"
            data-controller="'.YooMoneyModule::ADMIN_CONTROLLER.'"
            data-token="'.$token.'">один вопрос</a>');
    }

    /**
     * @param \Smarty $smarty
     *
     * @return null|string
     */
    public function assignVariables($smarty)
    {
        if ($this->getEPL()) {
            $template = 'module:yoomoneymodule/views/templates/hook/1.7/kassa_epl_form.tpl';
        } else {
            $methods = $this->getEnabledPaymentMethods();
            if (empty($methods)) {
                // если мерчант не выбрал ни одного способа оплаты, не
                // отображаем ничего, модуль настроен некорректно
                $this->module->log('warning', 'Empty payment method list');

                return null;
            }
            $template = 'module:yoomoneymodule/views/templates/hook/1.7/kassa_form.tpl';
            $smarty->assign('label', $this->module->l('Please select payment method'));
            $smarty->assign('payment_methods', $methods);
        }

        return $template;
    }

    public function getTaxesArray($config = false)
    {
        $taxes = Tax::getTaxes(Context::getContext()->language->id, true);

        $taxArray = array();
        foreach ($taxes as $tax) {
            $taxArray[] = 'YOOMONEY_KASSA_TAX_RATE_'.$tax['id_tax'];
        }

        if ($config) {
            return Configuration::getMultiple($taxArray);
        }

        return $taxArray;
    }

    /**
     * @param Context $context
     * @param Cart $cart
     * @param string $paymentMethod
     * @param string $returnUrl
     *
     * @return CreatePaymentResponse|null
     */
    public function createPayment(Context $context, Cart $cart, $paymentMethod, $returnUrl)
    {
        try {
            $builder = CreatePaymentRequest::builder();

            $totalAmount   = $cart->getOrderTotal(true);
            $rubCurrencyId = Currency::getIdByIsoCode('RUB');
            if ($cart->id_currency != $rubCurrencyId) {
                $from = new Currency($cart->id_currency);
                $to   = new Currency($rubCurrencyId);
                $this->module->log('debug', 'Convert amount from "'.$from->name.'" to "'.$to->name.'"');
                $totalAmount = Tools::convertPriceFull($totalAmount, $from, $to);
            }
            $description = $this->generateDescription($cart);
            $builder->setAmount($totalAmount)
                    ->setCurrency('RUB')
                    ->setCapture($this->getCaptureValue($paymentMethod))
                    ->setDescription($description)
                    ->setClientIp($_SERVER['REMOTE_ADDR'])
                    ->setMetadata(array(
                        'cms_name'       => 'yoomoney_api_ycms_prestashop',
                        'module_version' => $this->module->version,
                    ));

            $confirmation = array(
                'type'      => ConfirmationType::REDIRECT,
                'returnUrl' => $returnUrl,
            );
            if ($paymentMethod !== null) {
                if ($paymentMethod === PaymentMethodType::ALFABANK) {
                    $paymentMethod = array(
                        'type'  => $paymentMethod,
                        'login' => trim(Tools::getValue('alfaLogin')),
                    );
                    $confirmation  = ConfirmationType::EXTERNAL;
                } elseif ($paymentMethod === PaymentMethodType::QIWI) {
                    $paymentMethod = array(
                        'type'  => $paymentMethod,
                        'phone' => preg_replace('/[^\d]+/', '', Tools::getValue('qiwiPhone')),
                    );
                } elseif ($paymentMethod === self::PAYMENT_METHOD_WIDGET) {
                    $confirmation = ConfirmationType::EMBEDDED;
                    $paymentMethod = null;
                }
                $builder->setPaymentMethodData($paymentMethod);
            }
            $builder->setConfirmation($confirmation);
            if ($this->getSendReceipt()) {
                $this->addReceiptItems($context->customer, $cart, $builder);
            }
            $request = $builder->build();
            if ($this->getSendReceipt()) {
                $request->getReceipt()->normalize(
                    $request->getAmount()
                );
            }
        } catch (\Exception $e) {
            $this->module->log('error', 'Failed to create payment: '.$e->getMessage());

            return null;
        }

        try {
            $payment = $this->getApiClient()->createPayment($request);
            $this->module->log('info', 'Create payment response: '.($payment === null ? 'null' : $payment->getId()));
            if ($payment !== null) {
                if (!$this->insertPaymentInfo($payment, $this->module->currentOrder)) {
                    $this->module->log('error', 'Failed to insert payment info object');
                    $payment = null;
                }
            }
        } catch (\Exception $e) {
            $payment = null;
            $this->module->log('error', 'Failed to create payment object: '.$e->getMessage());
        }

        return $payment;
    }

    /**
     * @param $orderId
     *
     * @return null|PaymentInterface
     */
    public function findOrderPayment($orderId)
    {
        $paymentInfo = $this->findPaymentInfoByOrderId($orderId);
        if (empty($paymentInfo)) {
            $this->module->log('warning', 'Order#'.$orderId.' payment not found in database');

            return null;
        }

        try {
            $payment = $this->getApiClient()->getPaymentInfo($paymentInfo['payment_id']);
        } catch (\Exception $e) {
            $this->module->log(
                'warning',
                'API do not return payment '.$paymentInfo['payment_id'].' for order#'.$orderId
            );

            return null;
        }

        return $payment;
    }

    /**
     * @param PaymentInterface $payment
     * @param \Order $order
     * @param float $amount
     * @param string $comment
     *
     * @return RefundInterface|null
     */
    public function createRefund($payment, $order, $amount, $comment)
    {
        try {
            $builder = CreateRefundRequest::builder();
            $builder->setPaymentId($payment->getId())
                    ->setAmount($amount)
                    ->setDescription($comment);
            $request = $builder->build();
        } catch (\Exception $e) {
            $this->module->log('error', 'Failed to create refund request: '.$e->getMessage());

            return null;
        }

        /* @TODO Убрать цикл и протестить работоспобность. */
        try {
            $tries = 0;
            $key   = uniqid('', true);
            do {
                $refund = $this->getApiClient()->createRefund($request, $key);
                if ($refund === null) {
                    $tries++;
                    if ($tries > 3) {
                        break;
                    }
                    sleep(2);
                }
            } while ($refund === null);
        } catch (\Exception $e) {
            $this->module->log('error', 'Failed to create refund: '.$e->getMessage());

            return null;
        }
        if ($refund !== null) {
            $this->insertRefundInfo($refund, $order->id, $comment);
        }

        return $refund;
    }

    public function findRefunds($orderId)
    {
        $refunds = $this->fetchRefundsByOrderId($orderId);
        foreach ($refunds as $refund) {
            if ($refund['status'] === RefundStatus::PENDING) {
                // $response = $this->getApiClient()->getRefundInfo($refund['id']);
            }
        }

        return $refunds;
    }

    /**
     * @param int $orderId
     *
     * @return array
     */
    public function fetchRefundsByOrderId($orderId)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('yoomoney_refunds');
        $query->where('order_id = '.(int)$orderId);
        $recordSet = Db::getInstance()->query($query);
        if ($recordSet) {
            $result = array();
            while ($record = Db::getInstance()->nextRow($recordSet)) {
                $result[] = $record;
            }
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * @param string $paymentId
     *
     * @return Payment|null
     */
    public function getPayment($paymentId)
    {
        $payment = $this->getApiClient()->getPaymentInfo($paymentId);

        if ($payment === null) {
            $this->module->log('warning', 'API do not return payment '.$paymentId);

            return null;
        }

        /** @var Payment $payment */
        return $payment;
    }

    /**
     * @param Payment $payment
     *
     * @return int
     */
    public function getOrderIdByPayment($payment)
    {
        $query = new DbQuery();
        $query->select('order_id');
        $query->from('yoomoney_payments');
        $query->where('payment_id = \''.$payment->getId().'\'');
        $row = Db::getInstance()->getRow($query);
        if (!empty($row)) {
            return (int)$row['order_id'];
        }

        return 0;
    }

    /**
     * @param Payment $payment
     */
    public function updatePaymentStatus($payment)
    {
        $update = array(
            'status' => $payment->getStatus(),
            'paid'   => $payment->getPaid() ? 'Y' : 'N',
        );
        if ($payment->getCapturedAt() !== null) {
            $update['captured_at'] = $payment->getCapturedAt()->format('Y-m-d H:i:s');
        }
        Db::getInstance()->update('yoomoney_payments', $update, '`payment_id` = \''.$payment->getId().'\'');
    }

    /**
     * @param Payment $payment
     *
     * @return PaymentInterface|null
     */
    public function capturePayment($payment)
    {
        $response = null;
        try {
            $request = CreateCaptureRequest::builder()->setAmount($payment->getAmount())->build();
            if ($this->debugLog) {
                $serializer   = new CreateCaptureRequestSerializer();
                $requestArray = $serializer->serialize($request);
                $this->module->log(
                    'info',
                    'Capture payment request: paymentID='.$payment->getId().', body: '.json_encode($requestArray)
                );
            }
            $response = $this->getApiClient()->capturePayment($request, $payment->getId());
        } catch (\Exception $e) {
            $this->module->log('error', 'Capture error: '.$e->getMessage());
            $response = $payment;
        }

        return $response;
    }

    /**
     * @param Payment $payment
     *
     * @return PaymentInterface|null
     */
    public function cancelPayment($payment)
    {
        $response = null;
        try {
            $response = $this->getApiClient()->cancelPayment($payment->getId());
        } catch (\Exception $e) {
            $this->module->log('error', 'Cancel payment error: '.$e->getMessage());
            $response = $payment;
        }

        return $response;
    }

    public function updateOrderPaymentId($orderId, $payment)
    {
        $orderPaymentId = $this->findLocalOrderPaymentId($orderId);
        $this->updateOrderTransactionId($orderPaymentId, $payment->getId());
    }

    /**
     * @param Customer $customer
     * @param Cart $cart
     * @param CreateCaptureRequestBuilder|CreatePaymentRequestBuilder $builder
     */
    public function addReceiptItems($customer, $cart, $builder)
    {
        $builder->setReceiptEmail($customer->email);

        $products = $cart->getProducts(true);
        $taxValue = $this->getTaxesArray(true);
        $carrier  = new Carrier($cart->id_carrier);

        foreach ($products as $product) {
            $taxIndex = 'YOOMONEY_NALOG_STAVKA_'.Product::getIdTaxRulesGroupByIdProduct($product['id_product']);
            $taxId    = isset($taxValue[$taxIndex]) ? $taxValue[$taxIndex] : $this->getDefaultTaxRate();
            $builder->addReceiptItem($product['name'], $product['price_wt'], $product['cart_quantity'], $taxId,
                $this->defaultPaymentMode, $this->defaultPaymentSubject);
        }

        if ($carrier->id && $cart->getPackageShippingCost()) {
            $taxIndex = 'YOOMONEY_NALOG_STAVKA_'.Carrier::getIdTaxRulesGroupByIdCarrier($carrier->id);
            $taxId    = isset($taxValue[$taxIndex]) ? $taxValue[$taxIndex] : $this->getDefaultTaxRate();
            $builder->addReceiptShipping($carrier->name, $cart->getPackageShippingCost(), $taxId,
                $this->defaultDeliveryPaymentMode, $this->defaultDeliveryPaymentSubject);
        }

        if (!empty($this->defaultTaxSystemCode)) {
            $builder->setTaxSystemCode($this->defaultTaxSystemCode);
        }
    }

    private function installVerifyAppleFile()
    {
        clearstatcache();
        $pluginAssociationPath = YOOMONEY_MODULE_ROOT_PATH . "apple-developer-merchantid-domain-association";
        $rootDir = _PS_CORE_DIR_;
        $rootAssociationPath = $rootDir . DIRECTORY_SEPARATOR . ".well-known" . DIRECTORY_SEPARATOR . "apple-developer-merchantid-domain-association";

        if (file_exists($rootAssociationPath)) {
            return true;
        }

        if (!file_exists($rootDir. DIRECTORY_SEPARATOR . '.well-known')) {
            if (!@mkdir($rootDir. DIRECTORY_SEPARATOR . '.well-known', 0755)) {
                return false;
            }
        }

        if (!@copy($pluginAssociationPath, $rootAssociationPath)) {
            return false;
        }

        return true;
    }

    private static function getDisabledPaymentMethods()
    {
        return self::$disabledPaymentMethods;
    }

    /**
     * @return array custom payment methods
     */
    private static function getCustomPaymentMethods()
    {
        return self::$customPaymentMethods;
    }

    private function insertPaymentInfo(CreatePaymentResponse $payment, $orderId)
    {
        $paymentMethod = $payment->getPaymentMethod();
        $row           = array(
            'order_id'          => $orderId,
            'payment_id'        => $payment->getId(),
            'status'            => $payment->getStatus(),
            'amount'            => $payment->getAmount()->getValue(),
            'currency'          => $payment->getAmount()->getCurrency(),
            'payment_method_id' => empty($paymentMethod) ? '' : $payment->getPaymentMethod()->getType(),
            'paid'              => $payment->getPaid() ? 'Y' : 'N',
            'created_at'        => $payment->getCreatedAt()->format('Y-m-d H:i:s'),
        );

        return Db::getInstance()->insert('yoomoney_payments', array($row));
    }

    /**
     * @param RefundInterface $refund
     * @param int $orderId
     *
     * @return bool
     */
    private function insertRefundInfo(RefundInterface $refund, $orderId, $comment)
    {
        $row = array(
            'refund_id'  => $refund->getId(),
            'order_id'   => $orderId,
            'payment_id' => $refund->getPaymentId(),
            'status'     => $refund->getStatus(),
            'amount'     => $refund->getAmount()->getValue(),
            'currency'   => $refund->getAmount()->getCurrency(),
            'created_at' => $refund->getCreatedAt()->format('Y-m-d H:i:s'),
            'comment'    => $comment,
        );

        return Db::getInstance()->insert('yoomoney_refunds', array($row));
    }

    /**
     * @param int $orderId
     *
     * @return array
     */
    private function findPaymentInfoByOrderId($orderId)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('yoomoney_payments');
        $query->where('order_id = '.(int)$orderId);

        return Db::getInstance()->GetRow($query);
    }

    private function findLocalOrderPaymentId($orderId)
    {
        $query = new DbQuery();
        $query->select('id_order_payment');
        $query->from('order_invoice_payment');
        $query->where('id_order = '.(int)$orderId);
        $row = Db::getInstance()->getRow($query);
        if (!empty($row)) {
            return (int)$row['id_order_payment'];
        } else {
            return -1;
        }
    }

    private function updateOrderTransactionId($orderPaymentId, $paymentId)
    {
        $update = array(
            'transaction_id' => $paymentId,
        );

        return Db::getInstance()->update('order_payment', $update, 'id_order_payment = '.$orderPaymentId);
    }

    /**
     * @return Client
     */
    public function getApiClient()
    {
        if ($this->apiClient === null) {
            $this->apiClient = new Client();
            $this->apiClient->setAuth($this->getShopId(), $this->getPassword());
            $this->apiClient->setLogger($this->module);
            $userAgent = $this->apiClient->getApiClient()->getUserAgent();
            $userAgent->setCms("PrestaShop", _PS_VERSION_);
            $userAgent->setModule("yoomoney-ycms-v2-prestashop", $this->module->version);
        }

        return $this->apiClient;
    }

    /**
     * @return bool
     */
    private function testConnection()
    {
        try {
            $this->getApiClient()->getPaymentInfo('00000000-0000-0000-0000-000000000000');
        } catch (NotFoundException $e) {
            return true;
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function getShowInstallmentsButton()
    {
        return $this->showInstallmentsButton;
    }

    public function getPaymentDescription()
    {
        return $this->paymentDescription;
    }

    private function generateDescription($cart)
    {
        $descriptionTemplate = $this->getPaymentDescription();

        $description = str_replace('%cart_id%', $cart->id, $descriptionTemplate);

        $description = (string)mb_substr($description, 0, Payment::MAX_LENGTH_DESCRIPTION);

        return $description;
    }

    /**
     * @param string $paymentMethod
     *
     * @return bool
     */
    private function getCaptureValue($paymentMethod)
    {
        if (!$this->getEnableHoldMode()) {
            return true;
        }

        return !in_array($paymentMethod, array('', PaymentMethodType::BANK_CARD));
    }

    public function getDefaultTaxSystemCode()
    {
        return $this->defaultTaxSystemCode;
    }

    public function getDefaultPaymentMode()
    {
        return $this->defaultPaymentMode;
    }

    /**
     * @return mixed
     */
    public function getDefaultPaymentSubject()
    {
        return $this->defaultPaymentSubject;
    }

    /**
     * @return mixed
     */
    public function getDefaultDeliveryPaymentMode()
    {
        return $this->defaultDeliveryPaymentMode;
    }

    /**
     * @return mixed
     */
    public function getDefaultDeliveryPaymentSubject()
    {
        return $this->defaultDeliveryPaymentSubject;
    }
}
