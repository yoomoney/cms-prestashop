<?php
/**
 * Module is prohibited to sales! Violation of this condition leads to the deprivation of the license!
 *
 * @author   Yandex.Money <cms@yamoney.ru>
 * @copyright © 2015-2017 NBCO Yandex.Money LLC
 * @license   https://money.yandex.ru/doc.xml?id=527052
 *
 * @category Front Office Features
 * @package  Yandex Payment Solution
 */

namespace YandexMoneyModule;

use Category;
use Carrier;
use Configuration;
use Context;
use Currency;
use Module;
use OrderState;
use Tax;
use Tools;
use YandexCheckout\Model\PaymentMethodType;
use YandexCheckout\Model\Receipt\PaymentMode;
use YandexCheckout\Model\Receipt\PaymentSubject;
use yandexmodule;
use YandexMoneyModule\Models\Market\YandexMarketSettings;

class FormHelper
{
    private $module;

    public function l($s)
    {
        if ($this->module === null) {
            $this->module = Module::getInstanceByName('yandexmodule');
        }

        return $this->module->l($s, 'FormHelper');
    }

    public function getFormYamoneyMarket($settings)
    {
        $market = new YandexMarketSettings($settings);

        $inputs = array();

        $inputs[] = array(
            'col'         => 6,
            'type'        => 'text',
            'name'        => 'YA_MARKET_SHOP_NAME',
            'label'       => $this->l('Короткое название магазина'),
            'placeholder' => '123',
        );

        $inputs[] = array(
            'col'   => 6,
            'type'  => 'text',
            'name'  => 'YA_MARKET_FULL_SHOP_NAME',
            'label' => $this->l('Полное наименование организации'),
        );

        $inputs[] = array(
            'type'         => 'html',
            'name'         => '',
            'label'        => $this->l('Валюта'),
            'html_content' => $market->getCurrency()->htmlCurrencyList(
                Currency::getCurrencies(),
                Currency::getDefaultCurrency()->iso_code
            ),
        );

        $inputs[] = array(
            'type'         => 'html',
            'name'         => '',
            'label'        => $this->l('Выгружаем категории'),
            'html_content' => $market->getCategoryTree()->htmlCategoryList(Category::getCategories()),
        );

        $inputs[] = array(
            'type'         => 'html',
            'name'         => '',
            'label'        => $this->l('Курьерская доставка для домашнего региона'),
            'html_content' => $market->getDelivery()->htmlDeliveryList(Currency::getDefaultCurrency()->iso_code),
        );

        $inputs[] = array(
            'type'         => 'html',
            'name'         => '',
            'label'        => '',
            'html_content' => '<div class="yandex-money-market-sub-header">'.$this->l('Настройка предложений').'</div>',
        );

        $inputs[] = array(
            'type'         => 'html',
            'name'         => '',
            'label'        => $this->l('Формат предложений'),
            'html_content' => $market->getOfferType()->html(),
        );

        $inputs[] = array(
            'type'         => 'html',
            'name'         => '',
            'label'        => $this->l('Статус товара и способы получения'),
            'html_content' => $market->getAvailable()->htmlAvailableList(),
        );

        $taxes    = Tax::getTaxes(Context::getContext()->language->id, true);
        $inputs[] = array(
            'type'         => 'html',
            'name'         => '',
            'label'        => $this->l('Налоговые ставки'),
            'html_content' => $market->getVat()->htmlVatList($taxes),
        );

        $inputs[] = array(
            'type'   => 'checkbox',
            'label'  => $this->l('Выгрузка комбинаций товара'),
            'name'   => 'YA_MARKET_COMBINATION',
            'values' => array(
                'query' => array(
                    array(
                        'id'   => 'EXPORT_ALL',
                        'name' => $this->l('Выгружать комбинации товара'),
                        'val'  => 1,
                    ),
                ),
                'id'    => 'id',
                'name'  => 'name',
            ),
        );

        $inputs[] = array(
            'type'   => 'checkbox',
            'label'  => $this->l('Опции предложений'),
            'name'   => 'YA_MARKET_OFFER_OPTIONS',
            'values' => array(
                'query' => array(
                    array(
                        'id'   => 'EXPORT_PARAMS',
                        'name' => $this->l('Выгружать все атрибуты товаров'),
                        'val'  => 1,
                    ),
                    array(
                        'id'   => 'EXPORT_DIMENSION',
                        'val'  => 1,
                        'name' => $this->l('Выгружать размеры товаров'),
                    ),
                ),
                'id'    => 'id',
                'name'  => 'name',
            ),
        );

        $inputs[] = array(
            'type'         => 'html',
            'name'         => '',
            'label'        => $this->l('Дополнительные условия'),
            'html_content' => $market->getAdditionalCondition()->htmlAdditionalConditionList(Category::getCategories()),
        );

        $inputs[] = array(
            'type'         => 'html',
            'name'         => '',
            'label'        => '',
            'html_content' => '<div class="yandex-money-market-sub-header">'.$this->l('Параметры для Яндекс.Маркета').'</div>',
        );

        $inputs[] = array(
            'type'         => 'html',
            'name'         => '',
            'label'        => $this->l('Ссылка для выгрузки товаров на Маркет'),
            'html_content' => $market->getExportLink()->html(),
        );

        return array(
            'form' => array(
                'legend'  => array(
                    'title' => $this->l('The module settings Yandex.Market'),
                    'icon'  => 'icon-cogs',
                ),
                'input'   => $inputs,
                'submit'  => array(
                    'title' => $this->l('Save'),
                ),
                'buttons' => array(
                    'generatemanual' => array(
                        'title' => $this->l('To generate manually'),
                        'name'  => 'generatemanual',
                        'type'  => 'submit',
                        'class' => 'btn btn-default pull-right',
                        'icon'  => 'process-icon-refresh',
                    ),
                ),
            ),
        );
    }

    public function getFormYandexMetrics()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('The module settings Yandex.The metric'),
                    'icon'  => 'icon-cogs',
                ),
                'input'  => array(
                    array(
                        'type'     => 'radio',
                        'label'    => $this->l('Activity:'),
                        'name'     => 'YA_METRICS_ACTIVE',
                        'required' => false,
                        'class'    => 't',
                        'is_bool'  => true,
                        'values'   => array(
                            array(
                                'id'    => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id'    => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                    ),
                    array(
                        'col'   => 4,
                        'class' => 't',
                        'type'  => 'text',
                        'desc'  => $this->l('The number of Your counter'),
                        'name'  => 'YA_METRICS_NUMBER',
                        'label' => $this->l('The number of the counter'),
                    ),
                    array(
                        'col'   => 4,
                        'class' => 't',
                        'type'  => 'text',
                        'desc'  => $this->l('ID of the OAuth application'),
                        'name'  => 'YA_METRICS_ID_APPLICATION',
                        'label' => $this->l('Application ID'),
                    ),
                    array(
                        'col'   => 4,
                        'class' => 't',
                        'type'  => 'text',
                        'desc'  => $this->l('The password of the OAuth application'),
                        'name'  => 'YA_METRICS_PASSWORD_APPLICATION',
                        'label' => $this->l('An application-specific password'),
                    ),
                    array(
                        'type'   => 'checkbox',
                        'label'  => $this->l('Settings'),
                        'name'   => 'YA_METRICS_SET',
                        'values' => array(
                            'query' => array(
                                array(
                                    'id'   => 'WEBVIZOR',
                                    'name' => $this->l('Vebvizor'),
                                    'val'  => 1,
                                ),
                                array(
                                    'id'   => 'CLICKMAP',
                                    'name' => $this->l('Map clicks'),
                                    'val'  => 1,
                                ),
                                array(
                                    'id'   => 'HASH',
                                    'name' => $this->l('Hash tracking in the browser address bar'),
                                    'val'  => 1,
                                ),

                            ),
                            'id'    => 'id',
                            'name'  => 'name',
                        ),
                    ),
                    array(
                        'col'      => 6,
                        'class'    => 't',
                        'type'     => 'text',
                        'name'     => 'YA_METRICS_REDIRECT',
                        'desc'     => $this->l('Callback Url for OAuth applications'),
                        'label'    => $this->l('The link for the application'),
                        'disabled' => true,
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    public function getKassaForm(Models\KassaModel $model)
    {
        $paymentMethodOptions = array(
            'query' => array(),
            'id'    => 'id',
            'name'  => 'name',
        );
        $names                = array(
            PaymentMethodType::BANK_CARD      => $this->l('Bank cards'),
            PaymentMethodType::YANDEX_MONEY   => $this->l('Yandex.Money'),
            PaymentMethodType::SBERBANK       => $this->l('Sberbank Online'),
            PaymentMethodType::QIWI           => $this->l('QIWI Wallet'),
            PaymentMethodType::WEBMONEY       => $this->l('Webmoney'),
            PaymentMethodType::CASH           => $this->l('Cash via payment kiosks'),
            PaymentMethodType::MOBILE_BALANCE => $this->l('Direct carrier billing'),
            PaymentMethodType::ALFABANK       => $this->l('Alfa-Click'),
            PaymentMethodType::TINKOFF_BANK   => $this->l('Интернет-банк Тинькофф'),
            PaymentMethodType::INSTALLMENTS   => $this->l('Installments'),
        );

        $paymentModeEnum = array(
            PaymentMode::FULL_PREPAYMENT    => 'Полная предоплата ('.PaymentMode::FULL_PREPAYMENT.')',
            PaymentMode::PARTIAL_PREPAYMENT => 'Частичная предоплата ('.PaymentMode::PARTIAL_PREPAYMENT.')',
            PaymentMode::ADVANCE            => 'Аванс ('.PaymentMode::ADVANCE.')',
            PaymentMode::FULL_PAYMENT       => 'Полный расчет ('.PaymentMode::FULL_PAYMENT.')',
            PaymentMode::PARTIAL_PAYMENT    => 'Частичный расчет и кредит ('.PaymentMode::PARTIAL_PAYMENT.')',
            PaymentMode::CREDIT             => 'Кредит ('.PaymentMode::CREDIT.')',
            PaymentMode::CREDIT_PAYMENT     => 'Выплата по кредиту ('.PaymentMode::CREDIT_PAYMENT.')',
        );

        $paymentSubjectEnum = array(
            PaymentSubject::COMMODITY             => 'Товар ('.PaymentSubject::COMMODITY.')',
            PaymentSubject::EXCISE                => 'Подакцизный товар ('.PaymentSubject::EXCISE.')',
            PaymentSubject::JOB                   => 'Работа ('.PaymentSubject::JOB.')',
            PaymentSubject::SERVICE               => 'Услуга ('.PaymentSubject::SERVICE.')',
            PaymentSubject::GAMBLING_BET          => 'Ставка в азартной игре ('.PaymentSubject::GAMBLING_BET.')',
            PaymentSubject::GAMBLING_PRIZE        => 'Выигрыш в азартной игре ('.PaymentSubject::GAMBLING_PRIZE.')',
            PaymentSubject::LOTTERY               => 'Лотерейный билет ('.PaymentSubject::LOTTERY.')',
            PaymentSubject::LOTTERY_PRIZE         => 'Выигрыш в лотерею ('.PaymentSubject::LOTTERY_PRIZE.')',
            PaymentSubject::INTELLECTUAL_ACTIVITY => 'Результаты интеллектуальной деятельности ('.PaymentSubject::INTELLECTUAL_ACTIVITY.')',
            PaymentSubject::PAYMENT               => 'Платеж ('.PaymentSubject::PAYMENT.')',
            PaymentSubject::AGENT_COMMISSION      => 'Агентское вознаграждение ('.PaymentSubject::AGENT_COMMISSION.')',
            PaymentSubject::COMPOSITE             => 'Несколько вариантов ('.PaymentSubject::COMPOSITE.')',
            PaymentSubject::ANOTHER               => 'Другое ('.PaymentSubject::ANOTHER.')',
        );

        $paymentModeEnumOptions          = array(
            'id'   => 'id',
            'name' => 'name',
        );
        $paymentSubjectEnumOptions       = array(
            'id'   => 'id',
            'name' => 'name',
        );
        $paymentModeEnumOptions['query'] = array_map(function ($key, $value) {
            return array(
                'id'   => $key,
                'name' => $value,
            );
        }, array_keys($paymentModeEnum), $paymentModeEnum);

        $paymentSubjectEnumOptions['query'] = array_map(function ($key, $value) {
            return array(
                'id'   => $key,
                'name' => $value,
            );
        }, array_keys($paymentSubjectEnum), $paymentSubjectEnum);

        foreach (array_keys($model->getPaymentMethods()) as $key) {
            $paymentMethodOptions['query'][] = array(
                'id'   => Tools::strtoupper($key),
                'name' => $this->l($names[$key]),
                'val'  => 1,
            );
        }

        $statusList    = OrderState::getOrderStates(Context::getContext()->language->id);
        $statusOptions = array(
            'query' => $statusList,
            'id'    => 'id_order_state',
            'name'  => 'name',
        );

        $form = array(
            'form' => array(
                'input'   => array(
                    array(
                        'type'     => 'radio',
                        'label'    => $this->l(
                            'Activate payments via Yandex.Checkout'
                        ),
                        'name'     => 'YA_KASSA_ACTIVE',
                        'required' => false,
                        'class'    => 't',
                        'is_bool'  => true,
                        'value'    => ($model->isEnabled() ? 1 : 0),
                        'values'   => array(
                            array(
                                'id'    => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Yes'),
                            ),
                            array(
                                'id'    => 'active_off',
                                'value' => 0,
                                'label' => $this->l('No'),
                            ),
                        ),
                    ),
                    array(
                        'col'      => 4,
                        'class'    => 't',
                        'type'     => 'text',
                        'desc'     => $this->l(
                            'Скопируйте shopId из личного кабинета Яндекс.Кассы'
                        ),
                        'name'     => 'YA_KASSA_SHOP_ID',
                        'required' => true,
                        'label'    => $this->l('shopId'),
                        'value'    => $model->getShopId(),
                    ),
                    array(
                        'col'      => 4,
                        'class'    => 't',
                        'type'     => 'text',
                        'required' => true,
                        'desc'     => $this->l('Issue and activate your secret key in your ')
                                      .'<a href="https://kassa.yandex.ru/my" target="_blank">'
                                      .$this->l('Yandex.Checkout Merchant Profile').'</a>. '
                                      .$this->l('Then copy it here.')
                    ,
                        'name'     => 'YA_KASSA_PASSWORD',
                        'label'    => $this->l('Secret key'),
                        'value'    => $model->getPassword(),
                    ),
                    array(
                        'type'     => 'radio',
                        'label'    => $this->l('Select payment method'),
                        'desc'     => '',
                        'name'     => 'YA_KASSA_PAYMENT_MODE',
                        'required' => false,
                        'class'    => 't',
                        'value'    => $model->getEPL() ? 'kassa' : 'shop',
                        'values'   => array(
                            array(
                                'id'    => 'payment_mode_kassa',
                                'value' => 'kassa',
                                'label' => $this->l("On Yandex.Checkout's site"),
                            ),
                            array(
                                'id'    => 'payment_mode_shop',
                                'value' => 'shop',
                                'label' => $this->l("On the store's site"),
                            ),
                        ),
                    ),
                    array(
                        'type'   => 'checkbox',
                        'label'  => '',
                        'name'   => 'YA_KASSA_PAY_LOGO',
                        'class'  => 'text-inside payment-mode-kassa',
                        'desc'   => $this->l(''),
                        'values' => array(
                            'query' => array(
                                array(
                                    'id'   => 'ON',
                                    'name' => $this->l('Use "Pay with Yandex" button'),
                                ),
                            ),
                            'id'    => 'id',
                            'name'  => 'name',
                        ),
                    ),
                    array(
                        'type'   => 'checkbox',
                        'label'  => '',
                        'name'   => 'YA_KASSA_INSTALLMENTS_BUTTON',
                        'class'  => 'text-inside payment-mode-kassa',
                        'desc'   => $this->l(''),
                        'values' => array(
                            'query' => array(
                                array(
                                    'id'   => 'ON',
                                    'name' => $this->l('Add the Installments payment method to checkout page'),
                                ),
                            ),
                            'id'    => 'id',
                            'name'  => 'name',
                        ),
                    ),
                    array(
                        'type'   => 'checkbox',
                        'label'  => '',
                        'desc'   => '',
                        'name'   => 'YA_KASSA_PAYMENT',
                        'class'  => 'payment-mode-shop',
                        'values' => $paymentMethodOptions,
                    ),
                    array(
                        'col'      => 4,
                        'class'    => 't',
                        'type'     => 'text',
                        'desc'     => $this->l('Full description of the transaction that the user will see during the checkout process. You can find it in your Yandex.Checkout Merchant Profile. For example, "Payment for order No. 72 by user@yandex.ru". Limitations: no more than 128 symbols'),
                        'name'     => 'YA_KASSA_PAYMENT_DESCRIPTION',
                        'required' => true,
                        'label'    => $this->l('Transaction data'),
                        'value'    => $model->getPaymentDescription(),
                    ),
                ),
                'submit'  => array(
                    'title' => $this->l('Save'),
                ),
                'buttons' => array(
                    'downloadlog' => array(
                        'title' => $this->l('Download log file'),
                        'name'  => 'downloadlog',
                        'type'  => 'submit',
                        'class' => 'btn btn-default pull-right',
                        'icon'  => 'process-icon-refresh',
                    ),
                ),
            ),
        );

        $taxRateOptions = array(
            'query' => array(
                array(
                    'id'   => 1,
                    'name' => $this->l('Without VAT'),
                ),
                array(
                    'id'   => 2,
                    'name' => '0%',
                ),
                array(
                    'id'   => 3,
                    'name' => '10%',
                ),
                array(
                    'id'   => 4,
                    'name' => '20%',
                ),
                array(
                    'id'   => 5,
                    'name' => $this->l('Applicable rate 10/110'),
                ),
                array(
                    'id'   => 6,
                    'name' => $this->l('Applicable rate 20/120'),
                ),
            ),
            'id'    => 'id',
            'name'  => 'name',
        );

        $form['form']['input'][] = array(
            'type'   => 'checkbox',
            'label'  => $this->l('Включить отложенную оплату'),
            'name'   => 'YA_KASSA_ENABLE_HOLD_MODE',
            'values' => array(
                'query' => array(
                    array(
                        'id'   => 'ON',
                        'name' => $this->l('Если опция включена, платежи с карт проходят в 2 этапа: у клиента сумма замораживается, и вам вручную нужно подтвердить её списание – через панель администратора.'),
                    ),
                ),
                'id'    => 'id',
                'name'  => 'name',
            ),
        );

        $form['form']['input'][] = array(
            'type'   => 'checkbox',
            'label'  => $this->l('What status should be assigned to an order if it is:'),
            'name'   => 'YA_KASSA_HOLD_MODE_STATUSES_LABEL',
            'class'  => 'text-inside enable-hold-mode hidden',
            'values' => array(
                'query' => array(
                    array(
                        'id'   => 'ON',
                        'name' => $this->l(''),
                    ),
                ),
                'id'    => 'id',
                'name'  => 'name',
            ),
        );

        $form['form']['input'][] = array(
            'type'    => 'select',
            'label'   => $this->l('waiting for capture'),
            'name'    => 'YA_KASSA_ON_HOLD_STATUS_ID',
            'class'   => 'text-inside enable-hold-mode',
            'value'   => $model->getOnHoldStatusId(),
            'options' => $statusOptions,
            'desc'    => $this->l('the order status will be changed to this one after the funds are credited, until the store\'s operator either captures or cancels the payment'),
        );

        $form['form']['input'][] = array(
            'type'    => 'select',
            'label'   => $this->l('canceled'),
            'name'    => 'YA_KASSA_CANCEL_STATUS_ID',
            'class'   => 'text-inside enable-hold-mode',
            'value'   => $model->getCancelStatusId(),
            'options' => $statusOptions,
            'desc'    => $this->l('the order status will be changed to this one after the payment is canceled'),
        );

        $form['form']['input'][] = array(
            'type'   => 'radio',
            'label'  => $this->l('Send receipt to Yandex.Kassa (54 federal law)'),
            'name'   => 'YA_KASSA_SEND_RECEIPT',
            'desc'   => $this->l(''),
            'value'  => ($model->getSendReceipt() ? 1 : 0),
            'values' => array(
                array(
                    'id'    => 'kassa_send_receipt_enable',
                    'label' => $this->l('Enable'),
                    'value' => 1,
                ),
                array(
                    'id'    => 'kassa_send_receipt_disable',
                    'label' => $this->l('Disable'),
                    'value' => 0,
                ),
            ),
        );

        $form['form']['input'][] = array(
            'type'         => 'html',
            'label'        => $this->l('VAT'),
            'html_content' => '',
            'desc'         => '',
            'name'         => '',
        );

        $form['form']['input'][] = array(
            'type'         => 'select',
            'label'        => $this->l('Default rate'),
            'name'         => 'YA_KASSA_DEFAULT_TAX_RATE',
            'options'      => $taxRateOptions,
            'value'        => $model->getDefaultTaxRate(),
            'html_content' => '',
            'desc'         => $this->l('Default tax rate'),
        );

        $form['form']['input'][] = array(
            'type'         => 'html',
            'label'        => $this->l('Compare rates'),
            'html_content' => '',
            'desc'         => $this->l(''),
            'name'         => '',
            'class'        => 'kassa_tax_rate',
        );

        $form['form']['input'][] = array(
            'type'         => 'html',
            'label'        => $this->l('Rate at your store'),
            'html_content' => '',
            'desc'         => $this->l('Rate for the receipt to the tax service'),
            'name'         => '',
            'class'        => 'kassa_tax_rate',
        );

        foreach (Tax::getTaxes(Context::getContext()->language->id, true) as $tax) {
            $form['form']['input'][] = array(
                'type'    => 'select',
                'label'   => $tax['name'],
                'name'    => 'YA_KASSA_TAX_RATE_'.$tax['id_tax'],
                'class'   => 'kassa_tax_rate',
                'options' => $taxRateOptions,
            );
        }

        $form['form']['input'][] = array(
            'type'    => 'select',
            'label'   => $this->l('Признак способа расчета'),
            'name'    => 'YA_KASSA_DEFAULT_PAYMENT_MODE',
            'options' => $paymentModeEnumOptions,
            'value'   => $model->getDefaultPaymentMode(),
            'class'   => 'kassa_tax_rate',
        );

        $form['form']['input'][] = array(
            'type'    => 'select',
            'label'   => $this->l('Признак предмета расчета'),
            'name'    => 'YA_KASSA_DEFAULT_PAYMENT_SUBJECT',
            'options' => $paymentSubjectEnumOptions,
            'value'   => $model->getDefaultPaymentSubject(),
            'class'   => 'kassa_tax_rate',
        );

        $form['form']['input'][] = array(
            'type'    => 'select',
            'label'   => $this->l('Признак способа расчета для доставки'),
            'name'    => 'YA_KASSA_DEFAULT_DELIVERY_PAYMENT_MODE',
            'options' => $paymentModeEnumOptions,
            'value'   => $model->getDefaultDeliveryPaymentMode(),
            'class'   => 'kassa_tax_rate',
        );

        $form['form']['input'][] = array(
            'type'    => 'select',
            'label'   => $this->l('Признак предмета расчета для доставки'),
            'name'    => 'YA_KASSA_DEFAULT_DELIVERY_PAYMENT_SUBJECT',
            'options' => $paymentSubjectEnumOptions,
            'value'   => $model->getDefaultDeliveryPaymentSubject(),
            'class'   => 'kassa_tax_rate',
        );

        $form['form']['input'][] = array(
            'col'      => 6,
            'class'    => 't',
            'type'     => 'text',
            'desc'     => $this->l(
                'Этот адрес понадобится, если его '
                .'попросят специалисты Яндекс.Кассы'
            ),
            'name'     => 'YA_KASSA_NOTIFICATION_URL',
            'label'    => $this->l('Address for notifications'),
            'disabled' => true,
        );

        $form['form']['input'][] = array(
            'type'    => 'select',
            'label'   => $this->l('Order status after the payment'),
            'name'    => 'YA_KASSA_SUCCESS_STATUS_ID',
            'value'   => $model->getSuccessStatusId(),
            'options' => $statusOptions,
        );

        $form['form']['input'][] = array(
            'col'   => 4,
            'class' => 't',
            'type'  => 'text',
            'desc'  => $this->l(''),
            'name'  => 'YA_KASSA_MIN',
            'value' => $model->getMinimumAmount(),
            'label' => $this->l('Минимальная сумма заказа'),
        );
        $form['form']['input'][] = array(
            'type'   => 'checkbox',
            'label'  => $this->l('Debug log'),
            'name'   => 'YA_KASSA_LOGGING',
            'desc'   => $this->l(
                'Настройку нужно будет поменять, если попросят специалисты Яндекс.Кассы'
            ),
            'values' => array(
                'query' => array(
                    array(
                        'id'   => 'ON',
                        'name' => '',
                    ),
                ),
                'id'    => 'id',
                'name'  => 'name',
            ),
        );

        return $form;
    }

    public function getWalletForm(Models\WalletModel $model)
    {
        $state = new OrderState();

        return array(
            'form' => array(
                'input'  => array(
                    array(
                        'type'     => 'radio',
                        'label'    => $this->l(
                            'Activate payments via Yandex Wallet'
                        ),
                        'name'     => 'YA_WALLET_ACTIVE',
                        'required' => false,
                        'class'    => 't',
                        'is_bool'  => true,
                        'values'   => array(
                            array(
                                'id'    => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Yes'),
                            ),
                            array(
                                'id'    => 'active_off',
                                'value' => 0,
                                'label' => $this->l('No'),
                            ),
                        ),
                        'value'    => $model->isEnabled() ? '1' : '0',
                    ),
                    array(
                        'col'   => 6,
                        'class' => 't',
                        'desc'  => $this->l('Copy this link to the Redirect URL field at').
                                   '<a href="https://sp-money.yandex.ru/myservices/online.xml" target="_blank"> '.
                                   $this->l('the notification page').' </a>',
                        'type'  => 'text',
                        'name'  => 'YA_WALLET_REDIRECT',
                        'label' => $this->l('RedirectURL'),
                    ),
                    array(
                        'col'   => 4,
                        'class' => 't',
                        'type'  => 'text',
                        'desc'  => $this->l(''),
                        'name'  => 'YA_WALLET_ACCOUNT_ID',
                        'label' => $this->l('Номер кошелька'),
                        'value' => $model->getAccountId(),
                    ),
                    array(
                        'type'  => 'textarea',
                        'label' => $this->l('Codeword'),
                        'name'  => 'YA_WALLET_PASSWORD',
                        'rows'  => 5,
                        'cols'  => 30,
                        'desc'  => $this->l('Секретное слово нужно скопировать').'<a href="https://money.yandex.ru/myservices/online.xml" target="_blank">'.$this->l(' со страницы настройки уведомлений на сайте Яндекс.Денег').'</a>',
                        'class' => 't',
                        'value' => $model->getPassword(),
                    ),
                    array(
                        'col'   => 4,
                        'class' => 't',
                        'type'  => 'text',
                        'desc'  => $this->l(''),
                        'name'  => 'YA_WALLET_MIN_AMOUNT',
                        'label' => $this->l('Minimum order amount'),
                        'value' => $model->getMinimumAmount(),
                    ),
                    array(
                        'col'     => 4,
                        'class'   => 't',
                        'type'    => 'select',
                        'desc'    => '',
                        'name'    => 'YA_WALLET_END_STATUS',
                        'label'   => $this->l('Order status'),
                        'options' => array(
                            'query' => $state->getOrderStates(1),
                            'id'    => 'id_order_state',
                            'name'  => 'name',
                        ),
                        'default' => Configuration::get('PS_OS_PAYMENT'),
                        'value'   => $model->getOrderStatus(),
                    ),
                    array(
                        'type'   => 'checkbox',
                        'label'  => $this->l('Debug log'),
                        'desc'   => $this->l('Настройку нужно будет поменять, только если попросят специалисты Яндекс.Денег'),
                        'name'   => 'YA_WALLET_LOGGING',
                        'values' => array(
                            'query' => array(
                                array(
                                    'id'   => 'ON',
                                    'name' => '',
                                    'val'  => 1,
                                ),
                            ),
                            'id'    => 'id',
                            'name'  => 'name',
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    public function getBillingForm(Models\BillingModel $model)
    {
        $state = new OrderState();

        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('The module settings Yandex.Billing'),
                    'icon'  => 'icon-cogs',
                ),
                'input'  => array(
                    array(
                        'type'     => 'radio',
                        'label'    => $this->l('Activate payments via Yandex.Billing'),
                        'name'     => 'YA_BILLING_ACTIVE',
                        'required' => false,
                        'class'    => 't',
                        'is_bool'  => true,
                        'values'   => array(
                            array(
                                'id'    => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Yes'),
                            ),
                            array(
                                'id'    => 'active_off',
                                'value' => 0,
                                'label' => $this->l('No'),
                            ),
                        ),
                        'value'    => ($model->isEnabled() ? '1' : '0'),
                    ),
                    array(
                        'col'   => 4,
                        'class' => 't',
                        'type'  => 'text',
                        'label' => $this->l('Yandex.Billing\'s identifier'),
                        'name'  => 'YA_BILLING_ID',
                        'value' => $model->getFormId(),
                    ),
                    array(
                        'col'     => 4,
                        'class'   => 't',
                        'type'    => 'text',
                        'desc'    => $this->l(
                            'Payment purpose is added to the payment order: specify whatever will help identify the'
                            .' order paid via Yandex.Billing'
                        ),
                        'name'    => 'YA_BILLING_PURPOSE',
                        'label'   => $this->l('Payment purpose'),
                        'default' => $this->l('Order No. #order_id# Payment via Yandex.Billing'),
                        'value'   => $model->getPurpose(),
                    ),
                    array(
                        'col'     => 4,
                        'class'   => 't',
                        'type'    => 'select',
                        'desc'    => $this->l(
                            'Order status shows the payment result is unknown: you can only learn whether the client'
                            .' made payment or not from an email notification or in your bank'
                        ),
                        'name'    => 'YA_BILLING_END_STATUS',
                        'label'   => $this->l('Order status'),
                        'options' => array(
                            'query' => $state->getOrderStates(1),
                            'id'    => 'id_order_state',
                            'name'  => 'name',
                        ),
                        'default' => Configuration::get('PS_OS_PAYMENT'),
                        'value'   => $model->getOrderStatus(),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }
}
