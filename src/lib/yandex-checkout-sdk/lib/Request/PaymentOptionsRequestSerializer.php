<?php

namespace YaMoney\Request;

class PaymentOptionsRequestSerializer
{
    public function serialize(PaymentOptionsRequestInterface $request)
    {
        $result = array(
            'account_id' => $request->getAccountId(),
        );
        if ($request->hasGatewayId()) {
            $result['gateway_id'] = $request->getGatewayId();
        }
        if ($request->hasAmount()) {
            $result['amount'] = $request->getAmount();
        }
        if ($request->hasCurrency()) {
            $result['currency'] = $request->getCurrency();
        }
        if ($request->hasConfirmationType()) {
            $result['confirmation_types'] = $request->getConfirmationType();
        }
        return $result;
    }
}