<?php

namespace YaMoney\Model\PaymentData;

use YaMoney\Model\PaymentMethodType;

class PaymentDataFactory
{
    private $typeClassMap = array(
        PaymentMethodType::YANDEX_MONEY   => 'PaymentDataYandexWallet',
        PaymentMethodType::BANK_CARD      => 'PaymentDataBankCard',
        PaymentMethodType::SBERBANK       => 'PaymentDataSberbank',
        PaymentMethodType::CASH           => 'PaymentDataCash',
        PaymentMethodType::MOBILE_BALANCE => 'PaymentDataMobileBalance',
        PaymentMethodType::APPLE_PAY      => 'PaymentDataApplePay',
        PaymentMethodType::ANDROID_PAY    => 'PaymentDataAndroidPay',
        PaymentMethodType::QIWI           => 'PaymentDataQiwi',
        PaymentMethodType::WEBMONEY       => 'PaymentDataWebmoney',
        PaymentMethodType::ALFABANK       => 'PaymentDataAlfabank',
    );

    /**
     * @param string $type
     * @return AbstractPaymentData
     */
    public function factory($type)
    {
        if (!is_string($type)) {
            throw new \InvalidArgumentException('Invalid payment type value in payment factory');
        }
        if (!array_key_exists($type, $this->typeClassMap)) {
            throw new \InvalidArgumentException('Invalid payment data type "'.$type.'"');
        }
        $className = __NAMESPACE__ . '\\' . $this->typeClassMap[$type];
        return new $className();
    }

    /**
     * @param array $data
     * @param string|null $type
     * @return AbstractPaymentData
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
            if ($paymentData->offsetExists($key)) {
                $paymentData->offsetSet($key, $value);
            }
        }
        return $paymentData;
    }
}
