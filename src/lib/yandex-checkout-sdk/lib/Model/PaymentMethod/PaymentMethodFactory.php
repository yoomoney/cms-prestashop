<?php

namespace YaMoney\Model\PaymentMethod;

use YaMoney\Model\PaymentMethodType;

class PaymentMethodFactory
{
    private $typeClassMap = array(
        PaymentMethodType::YANDEX_MONEY   => 'PaymentMethodYandexWallet',
        PaymentMethodType::BANK_CARD      => 'PaymentMethodBankCard',
        PaymentMethodType::SBERBANK       => 'PaymentMethodSberbank',
        PaymentMethodType::CASH           => 'PaymentMethodCash',
        PaymentMethodType::MOBILE_BALANCE => 'PaymentMethodMobileBalance',
        PaymentMethodType::APPLE_PAY      => 'PaymentMethodApplePay',
        PaymentMethodType::ANDROID_PAY    => 'PaymentMethodAndroidPay',
        PaymentMethodType::QIWI           => 'PaymentMethodQiwi',
        PaymentMethodType::WEBMONEY       => 'PaymentMethodWebmoney',
        PaymentMethodType::ALFABANK       => 'PaymentMethodAlfaBank',
    );

    private $optionsMap = array(
        'card_type'      => 'cardType',
        'expiry_month'   => 'expiryMonth',
        'expiry_year'    => 'expiryYear',
        'bind_id'        => 'bindId',
        'account_number' => 'accountNumber',
    );

    /**
     * @param string $type
     * @return AbstractPaymentMethod
     */
    public function factory($type)
    {
        if (!is_string($type)) {
            throw new \InvalidArgumentException('Invalid payment method type value in payment factory');
        }
        if (!array_key_exists($type, $this->typeClassMap)) {
            throw new \InvalidArgumentException('Invalid payment method data type "'.$type.'"');
        }
        $className = __NAMESPACE__ . '\\' . $this->typeClassMap[$type];
        return new $className();
    }

    /**
     * @param array $data
     * @param string|null $type
     * @return AbstractPaymentMethod
     */
    public function factoryFromArray(array $data, $type = null)
    {
        if ($type === null) {
            if (array_key_exists('type', $data)) {
                $type = $data['type'];
                unset($data['type']);
            } else {
                throw new \InvalidArgumentException(
                    'Parameter type not specified in PaymentDataFactory.factoryFromArray()'
                );
            }
        }
        $paymentData = $this->factory($type);
        foreach ($data as $key => $value) {
            if (array_key_exists($key, $this->optionsMap)) {
                $key = $this->optionsMap[$key];
            }
            if ($paymentData->offsetExists($key)) {
                $paymentData->offsetSet($key, $value);
            }
        }
        return $paymentData;
    }
}