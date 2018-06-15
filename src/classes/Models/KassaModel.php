<?php
/**
 * @author Yandex.Money <cms@yamoney.ru>
 * @copyright © 2015-2017 NBCO Yandex.Money LLC
 * @license  https://money.yandex.ru/doc.xml?id=527052
 */

namespace YandexMoneyModule\Models;

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

use YandexCheckout\Client;
use YandexCheckout\Common\Exceptions\NotFoundException;
use YandexCheckout\Model\ConfirmationType;
use YandexCheckout\Model\Payment;
use YandexCheckout\Model\PaymentMethodType;
use YandexCheckout\Model\RefundInterface;
use YandexCheckout\Model\RefundStatus;
use YandexCheckout\Request\Payments\CreatePaymentRequest;
use YandexCheckout\Request\Payments\CreatePaymentRequestBuilder;
use YandexCheckout\Request\Payments\CreatePaymentResponse;
use YandexCheckout\Request\Payments\Payment\CreateCaptureRequest;
use YandexCheckout\Request\Payments\Payment\CreateCaptureRequestSerializer;
use YandexCheckout\Request\Payments\Payment\CreateCaptureResponse;
use YandexCheckout\Request\Refunds\CreateRefundRequest;

class KassaModel extends AbstractPaymentModel
{
    /**
     * Минимальная сумма заказа для платежа с методом кредитования.
     */
    const MIN_INSTALLMENTS_AMOUNT = 3000;

    private $shopId;
    private $password;
    private $epl;
    private $showYandexButton;
    private $showInstallmentsButton;
    private $availablePaymentMethods;
    private $enabledPaymentMethods;
    private $sendReceipt;
    private $defaultTaxRate;
    private $minimumAmount;
    private $paymentDescription;
    private $debugLog;
    private $createStatusId;
    private $successStatusId;
    private $failureStatusId;
    private $apiClient;

    public function initConfiguration()
    {
        $this->enabled                = Configuration::get('YA_KASSA_ACTIVE') == '1';
        $this->shopId                 = Configuration::get('YA_KASSA_SHOP_ID');
        $this->password               = Configuration::get('YA_KASSA_PASSWORD');
        $this->epl                    = Configuration::get('YA_KASSA_PAYMENT_MODE') == 'kassa';
        $this->showYandexButton       = Configuration::get('YA_KASSA_PAY_LOGO_ON') == 'on';
        $this->showInstallmentsButton = Configuration::get('YA_KASSA_INSTALLMENTS_BUTTON_ON') == 'on';
        $this->sendReceipt            = Configuration::get('YA_KASSA_SEND_RECEIPT') == '1';
        $this->defaultTaxRate         = (int)Configuration::get('YA_KASSA_DEFAULT_TAX_RATE');
        $this->minimumAmount          = (float)Configuration::get('YA_KASSA_MIN');
        $this->debugLog               = Configuration::get('YA_KASSA_LOGGING_ON') == 'on';
        $this->createStatusId         = Configuration::get('PS_OS_PREPARATION');
        $this->successStatusId        = (int)Configuration::get('YA_KASSA_SUCCESS_STATUS_ID');
        $this->failureStatusId        = Configuration::get('PS_OS_ERROR');
        $this->paymentDescription     = Configuration::get('YA_KASSA_PAYMENT_DESCRIPTION');

        if (!$this->paymentDescription) {
            $this->paymentDescription = $this->module->l('Payment for order No. %cart_id%');
        }
        //$this->createStatusId = (int)Configuration::get('YA_KASSA_CREATE_STATUS_ID');
        //$this->failureStatusId = (int)Configuration::get('YA_KASSA_FAILURE_STATUS_ID');

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
     * @return bool
     */
    public function getShowYandexPaymentButton()
    {
        return $this->showYandexButton;
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
            foreach (PaymentMethodType::getEnabledValues() as $value) {
                $this->availablePaymentMethods[$value] = 'YA_KASSA_PAYMENT_'.Tools::strtoupper($value);
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
                PaymentMethodType::YANDEX_MONEY   => $this->module->l('Yandex.Money'),
                PaymentMethodType::BANK_CARD      => $this->module->l('Bank cards'),
                PaymentMethodType::MOBILE_BALANCE => $this->module->l('Direct carrier billing'),
                PaymentMethodType::WEBMONEY       => $this->module->l('Webmoney'),
                PaymentMethodType::CASH           => $this->module->l('Cash via payment kiosks'),
                PaymentMethodType::SBERBANK       => $this->module->l('Sberbank Online'),
                PaymentMethodType::ALFABANK       => $this->module->l('Alfa-Click'),
                PaymentMethodType::QIWI           => $this->module->l('QIWI Wallet'),
                PaymentMethodType::INSTALLMENTS   => $this->module->l('Installments (%s Р per month)'),
            );

            $payments = Configuration::getMultiple(array_values($this->getPaymentMethods()));

            foreach ($paymentMethodNames as $id => $loc) {
                $key = 'YA_KASSA_PAYMENT_'.Tools::strtoupper($id);
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
        return $this->createStatusId;
    }

    /**
     * @return int
     */
    public function getSuccessStatusId()
    {
        return $this->successStatusId;
    }

    /**
     * @return int
     */
    public function getFailureStatusId()
    {
        return $this->failureStatusId;
    }

    public function validateOptions()
    {
        $errors = '';

        $this->enabled = Tools::getValue('YA_KASSA_ACTIVE') == '1';
        Configuration::UpdateValue('YA_KASSA_ACTIVE', $this->enabled ? 1 : 0);

        if ($this->enabled) {
            Configuration::UpdateValue('YA_WALLET_ACTIVE', 0);
            Configuration::UpdateValue('YA_BILLING_ACTIVE', 0);
        }

        $this->defaultTaxRate = (int)Tools::getValue('YA_KASSA_DEFAULT_TAX_RATE');
        Configuration::UpdateValue('YA_KASSA_DEFAULT_TAX_RATE', $this->defaultTaxRate);

        Configuration::UpdateValue('YA_KASSA_SEND_RECEIPT', Tools::getValue('YA_KASSA_SEND_RECEIPT'));
        $this->sendReceipt = Tools::getValue('YA_KASSA_SEND_RECEIPT') == '1';

        $this->minimumAmount = (float)Tools::getValue('YA_KASSA_MIN');
        Configuration::UpdateValue('YA_KASSA_MIN', $this->minimumAmount);

        $count = 0;
        foreach ($this->getPaymentMethods() as $method) {
            Configuration::UpdateValue($method, Tools::getValue($method));
            if (Tools::getValue($method) == '1') {
                $count++;
            }
        }

        Configuration::UpdateValue('YA_KASSA_LOGGING_ON', Tools::getValue('YA_KASSA_LOGGING_ON'));

        Configuration::UpdateValue('YA_KASSA_PAY_LOGO_ON', Tools::getValue('YA_KASSA_PAY_LOGO_ON'));
        $this->showYandexButton = Tools::getValue('YA_KASSA_PAY_LOGO_ON') == 'on';

        Configuration::UpdateValue('YA_KASSA_INSTALLMENTS_BUTTON_ON',
            Tools::getValue('YA_KASSA_INSTALLMENTS_BUTTON_ON'));
        $this->showYandexButton = Tools::getValue('YA_KASSA_INSTALLMENTS_BUTTON_ON') == 'on';

        Configuration::UpdateValue('YA_KASSA_PAYMENT_DESCRIPTION',
            Tools::getValue('YA_KASSA_PAYMENT_DESCRIPTION'));
        $this->paymentDescription = Tools::getValue('YA_KASSA_PAYMENT_DESCRIPTION');

        Configuration::UpdateValue('YA_KASSA_PAYMENT_MODE', Tools::getValue('YA_KASSA_PAYMENT_MODE'));
        $this->epl = Tools::getValue('YA_KASSA_PAYMENT_MODE') == 'kassa';

        //$this->createStatusId = (int)Tools::getValue('YA_KASSA_CREATE_STATUS_ID');
        //Configuration::UpdateValue('YA_KASSA_CREATE_STATUS_ID', $this->createStatusId);

        $this->successStatusId = (int)Tools::getValue('YA_KASSA_SUCCESS_STATUS_ID');
        Configuration::UpdateValue('YA_KASSA_SUCCESS_STATUS_ID', $this->successStatusId);

        //$this->failureStatusId = (int)Tools::getValue('YA_KASSA_FAILURE_STATUS_ID');
        //Configuration::UpdateValue('YA_KASSA_FAILURE_STATUS_ID', $this->failureStatusId);

        foreach ($this->getTaxesArray() as $taxRow) {
            Configuration::UpdateValue($taxRow, Tools::getValue($taxRow));
        }

        $isShopIdValid = false;
        if (Tools::getValue('YA_KASSA_SHOP_ID') == '') {
            $errors .= $this->module->displayError($this->module->l('ShopId not specified!'));
        } else {
            $isShopIdValid = true;
            $this->shopId  = trim(Tools::getValue('YA_KASSA_SHOP_ID'));
            Configuration::UpdateValue('YA_KASSA_SHOP_ID', $this->shopId);
        }

        if (Tools::getValue('YA_KASSA_PASSWORD') == '') {
            $errors .= $this->module->displayError($this->module->l('The password is not specified!'));
        } else {
            $this->password = trim(Tools::getValue('YA_KASSA_PASSWORD'));
            Configuration::UpdateValue('YA_KASSA_PASSWORD', $this->password);

            if ($isShopIdValid) {
                if (!$this->testConnection()) {
                    $errors .= $this->module->displayError(
                        $this->module->l('Check shopId and Secret key—there is an error somewhere. Better yet, copy them directly from your ')
                        .'<a href="https://kassa.yandex.ru/my" target="_blank">'.$this->module->l('Yandex.Checkout\'s Merchant Profile').'</a>'
                    );
                    if ($this->enabled) {
                        $this->enabled = false;
                        Configuration::UpdateValue('YA_KASSA_ACTIVE', 0);
                    }
                } elseif (strncmp('test_', Tools::getValue('YA_KASSA_PASSWORD'), 5) === 0) {
                    $errors .= $this->module->displayWarning(
                        $this->module->l('You have enabled the test mode. Check the payment making process and contact Yandex.Checkout\'s manager. They will provide you with shopId the Secret key. ')
                        .'<a href="https://yandex.ru/support/checkout/payments/api.html#api__04" target="_blank">'.$this->module->l('Manual').'</a>'
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
     * @param \Smarty $smarty
     *
     * @return null|string
     */
    public function assignVariables($smarty)
    {
        if ($this->getEPL()) {
            $template = 'module:yandexmodule/views/templates/hook/1.7/kassa_epl_form.tpl';
        } else {
            $methods = $this->getEnabledPaymentMethods();
            if (empty($methods)) {
                // если мерчант не выбрал ни одного способа оплаты, не
                // отображаем ничего, модуль настроен некорректно
                $this->module->log('warning', 'Empty payment method list');

                return null;
            }
            $template = 'module:yandexmodule/views/templates/hook/1.7/kassa_form.tpl';
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
            $taxArray[] = 'YA_KASSA_TAX_RATE_'.$tax['id_tax'];
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
                    ->setCapture(true)
                    ->setDescription($description)
                    ->setClientIp($_SERVER['REMOTE_ADDR'])
                    ->setMetadata(array(
                        'cms_name'       => 'ya_api_ycms_prestashop',
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
                }
                $builder->setPaymentMethodData($paymentMethod);
            }
            $builder->setConfirmation($confirmation);
            if ($this->getSendReceipt()) {
                $this->addReceiptItems($context, $cart, $builder);
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

    public function findOrderPayment($orderId)
    {
        $paymentInfo = $this->findPaymentInfoByOrderId($orderId);
        if (empty($paymentInfo)) {
            $this->module->log('warning', 'Order#'.$orderId.' payment not found in database');

            return false;
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
                    ->setComment($comment);
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
        $query->from('ya_money_refunds');
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
        $query->from('ya_money_payments');
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
        Db::getInstance()->update('ya_money_payments', $update, '`payment_id` = \''.$payment->getId().'\'');
    }

    /**
     * @param Payment $payment
     *
     * @return CreateCaptureResponse|null
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

    public function updateOrderPaymentId($orderId, $payment)
    {
        $orderPaymentId = $this->findLocalOrderPaymentId($orderId);
        $this->updateOrderTransactionId($orderPaymentId, $payment->getId());
    }

    private function addReceiptItems(Context $context, Cart $cart, CreatePaymentRequestBuilder $builder)
    {
        $builder->setTaxSystemCode($this->getDefaultTaxRate());
        $builder->setReceiptEmail($context->customer->email);

        $products = $cart->getProducts(true);
        $taxValue = $this->getTaxesArray(true);
        $carrier  = new Carrier($cart->id_carrier, $context->language->id);

        foreach ($products as $product) {
            $taxIndex = 'YA_NALOG_STAVKA_'.Product::getIdTaxRulesGroupByIdProduct($product['id_product']);
            if (isset($taxValue[$taxIndex])) {
                $taxId = $taxValue[$taxIndex];
                $builder->addReceiptItem($product['name'], $product['price_wt'], $product['cart_quantity'], $taxId);
            } else {
                $builder->addReceiptItem($product['name'], $product['price_wt'], $product['cart_quantity']);
            }
        }

        if ($carrier->id && $cart->getPackageShippingCost()) {
            $taxIndex = 'YA_NALOG_STAVKA_'.Carrier::getIdTaxRulesGroupByIdCarrier($carrier->id, $context);
            if (isset($taxValue[$taxIndex])) {
                $taxId = $taxValue[$taxIndex];
                $builder->addReceiptShipping($carrier->name, $cart->getPackageShippingCost(), $taxId);
            } else {
                $builder->addReceiptShipping($carrier->name, $cart->getPackageShippingCost());
            }
        }
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

        return Db::getInstance()->insert('ya_money_payments', array($row));
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
        if ($refund->getAuthorizedAt() !== null) {
            $row['authorized_at'] = $refund->getAuthorizedAt()->format('Y-m-d H:i:s');
        }

        return Db::getInstance()->insert('ya_money_refunds', array($row));
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
        $query->from('ya_money_payments');
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
    private function getApiClient()
    {
        if ($this->apiClient === null) {
            $this->apiClient = new Client();
            $this->apiClient->setAuth($this->getShopId(), $this->getPassword());
            $this->apiClient->setLogger($this->module);
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
}
