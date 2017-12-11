<?php

namespace YaMoney\Request\Payments;

use YaMoney\Model\AmountInterface;
use YaMoney\Model\ConfirmationType;
use YaMoney\Model\PaymentData\AbstractPaymentData;
use YaMoney\Model\PaymentData\PaymentDataAlfabank;
use YaMoney\Model\PaymentData\PaymentDataBankCard;
use YaMoney\Model\PaymentData\PaymentDataSberbank;
use YaMoney\Model\PaymentData\PaymentDataYandexWallet;
use YaMoney\Model\PaymentMethodType;

/**
 * Класс сериалайзера объекта запроса к API на проведение платежа
 *
 * @package YaMoney\Request\Payments
 */
class CreatePaymentRequestSerializer
{
    private static $propertyMap = array(
        'reference_id'        => 'referenceId',
        'payment_token'       => 'paymentToken',
        'payment_method_id'   => 'paymentMethodId',
        'save_payment_method' => 'savePaymentMethod',
        'capture'             => 'capture',
        'client_ip'           => 'clientIp',
    );

    private static $paymentDataSerializerMap = array(
        PaymentMethodType::BANK_CARD      => 'serializePaymentDataBankCard',
        PaymentMethodType::YANDEX_MONEY   => 'serializePaymentDataYandexWallet',
        PaymentMethodType::APPLE_PAY      => 'serializePaymentDataMobile',
        PaymentMethodType::ANDROID_PAY    => 'serializePaymentDataMobile',
        PaymentMethodType::SBERBANK       => 'serializePaymentDataSberbank',
        PaymentMethodType::ALFABANK       => 'serializePaymentDataAlfabank',
        PaymentMethodType::WEBMONEY       => 'serializePaymentData',
        PaymentMethodType::QIWI           => 'serializePaymentDataMobilePhone',
        PaymentMethodType::CASH           => 'serializePaymentDataMobilePhone',
        PaymentMethodType::MOBILE_BALANCE => 'serializePaymentDataMobilePhone',
    );

    public function serialize(CreatePaymentRequestInterface $request)
    {
        $result = array(
            'amount' => $this->serializeAmount($request->getAmount()),
        );
        if ($request->hasReceipt()) {
            $receipt = $request->getReceipt();
            if ($receipt->notEmpty()) {
                $result['receipt'] = array();
                foreach ($receipt->getItems() as $item) {
                    $vatId = $item->getVatCode();
                    if ($vatId === null) {
                        $vatId = $receipt->getTaxSystemCode();
                    }
                    $result['receipt']['items'][] = array(
                        'description' => $item->getDescription(),
                        'amount'      => $this->serializeAmount($item->getPrice()),
                        'quantity'    => $item->getQuantity(),
                        'vat_code'    => $vatId,
                    );
                }
                $value = $receipt->getEmail();
                if (!empty($value)) {
                    $result['receipt']['email'] = $value;
                }
                $value = $receipt->getPhone();
                if (!empty($value)) {
                    $result['receipt']['phone'] = $value;
                }
                $value = $receipt->getTaxSystemCode();
                if (!empty($value)) {
                    $result['receipt']['tax_system_code'] = $value;
                }
            }
        }
        if ($request->hasRecipient()) {
            $result['recipient']['account_id'] = $request->getRecipient()->getAccountId();
            $result['recipient']['gateway_id'] = $request->getRecipient()->getGatewayId();
        }
        if ($request->hasPaymentMethodData()) {
            $method = self::$paymentDataSerializerMap[$request->getPaymentMethodData()->getType()];
            $result['payment_method_data'] = $this->{$method}($request->getPaymentMethodData());
        }
        if ($request->hasConfirmation()) {
            $result['confirmation'] = array(
                'type' => $request->getConfirmation()->getType(),
            );
            $confirmation = $request->getConfirmation();
            if ($confirmation->getType() === ConfirmationType::REDIRECT) {
                if ($confirmation->getEnforce()) {
                    $result['confirmation']['enforce'] = $confirmation->getEnforce();
                }
                $result['confirmation']['return_url'] = $confirmation->getReturnUrl();
            }
        }
        if ($request->hasMetadata()) {
            $result['metadata'] = $request->getMetadata()->toArray();
        }

        foreach (self::$propertyMap as $name => $property) {
            $value = $request->{$property};
            if (!empty($value)) {
                $result[$name] = $value;
            }
        }
        return $result;
    }
    
    private function serializeAmount(AmountInterface $amount)
    {
        return array(
            'value'    => $amount->getValue(),
            'currency' => $amount->getCurrency(),
        );
    }

    private function serializePaymentDataBankCard(PaymentDataBankCard $paymentData)
    {
        $result = array(
            'type' => $paymentData->getType(),
        );
        if ($paymentData->getBankCard() !== null) {
            $result['bank_card'] = array(
                'cardholder' => $paymentData->getBankCard()->getCardholder(),
                'expiry_year' => $paymentData->getBankCard()->getExpiryYear(),
                'expiry_month' => $paymentData->getBankCard()->getExpiryMonth(),
                'number' => $paymentData->getBankCard()->getNumber(),
                'csc' => $paymentData->getBankCard()->getCsc(),
            );
        }
        return $result;
    }

    private function serializePaymentDataYandexWallet(PaymentDataYandexWallet $paymentData)
    {
        $result = array(
            'type' => $paymentData->getType(),
        );
        if ($paymentData->getAccountNumber() !== null) {
            $result['account_number'] = $paymentData->getAccountNumber();
        }
        if ($paymentData->getPhone() !== null) {
            $result['phone'] = $paymentData->getPhone();
        }
        return $result;
    }

    private function serializePaymentDataMobile(AbstractPaymentData $paymentData)
    {
        $result = array(
            'type' => $paymentData->getType(),
        );
        if ($paymentData->getPaymentData() !== null) {
            $result['payment_data'] = $paymentData->getPaymentData();
        }
        return $result;
    }

    private function serializePaymentDataSberbank(PaymentDataSberbank $paymentData)
    {
        $result = array(
            'type' => $paymentData->getType(),
        );
        if ($paymentData->getBindId() !== null) {
            $result['bind_id'] = $paymentData->getBindId();
        }
        if ($paymentData->getPhone() !== null) {
            $result['phone'] = $paymentData->getPhone();
        }
        return $result;
    }

    private function serializePaymentDataAlfabank(PaymentDataAlfabank $paymentData)
    {
        $result = array(
            'type' => $paymentData->getType(),
        );
        if ($paymentData->getLogin() !== null) {
            $result['login'] = $paymentData->getLogin();
        }
        return $result;
    }

    private function serializePaymentData(AbstractPaymentData $paymentData)
    {
        return array(
            'type'  => $paymentData->getType(),
        );
    }

    private function serializePaymentDataMobilePhone(AbstractPaymentData $paymentData)
    {
        $result = array(
            'type' => $paymentData->getType(),
        );
        if ($paymentData->getPhone() !== null) {
            $result['phone'] = $paymentData->getPhone();
        }
        return $result;
    }
}