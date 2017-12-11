<?php

namespace YaMoney\Request\Refunds;

/**
 * Класс сериалайзера запросов к API на создание нового возврата средств
 *
 * @package YaMoney\Request\Refunds
 */
class CreateRefundRequestSerializer
{
    /**
     * Сериализует переданный объект запроса к API в массив
     * @param CreateRefundRequestInterface $request Сериализуемый объект запроса
     * @return array Ассоциативный массив для передачи в API
     */
    public function serialize(CreateRefundRequestInterface $request)
    {
        $result = array(
            'payment_id' => $request->getPaymentId(),
            'amount' => array(
                'value'    => $request->getAmount()->getValue(),
                'currency' => $request->getAmount()->getCurrency(),
            ),
        );
        if ($request->hasComment()) {
            $result['comment'] = $request->getComment();
        }
        if ($request->hasReceipt()) {
            $receipt = $request->getReceipt();
            $result['receipt'] = array();
            foreach ($receipt->getItems() as $item) {
                $vatId = $item->getVatCode();
                if ($vatId === null) {
                    $vatId = $receipt->getTaxSystemCode();
                }
                $result['receipt']['items'][] = array(
                    'description' => $item->getDescription(),
                    'amount'      => array(
                        'value'    => $item->getPrice()->getValue(),
                        'currency' => $item->getPrice()->getCurrency(),
                    ),
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
        return $result;
    }
}