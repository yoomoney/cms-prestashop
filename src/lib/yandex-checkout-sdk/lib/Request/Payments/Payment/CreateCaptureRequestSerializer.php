<?php

namespace YaMoney\Request\Payments\Payment;

/**
 * Класс объекта осуществляющего сериализацию запроса к API на подтверждение заказа
 *
 * @package YaMoney\Request\Payments\Payment
 */
class CreateCaptureRequestSerializer
{
    /**
     * Сериализует объект запроса к API на подтверждение заказа в ассоциативный массив
     * @param CreateCaptureRequestInterface $request Сериализуемый объект запроса
     * @return array Ассоциативный массив содержащий информацию для отправки в API
     */
    public function serialize(CreateCaptureRequestInterface $request)
    {
        $result = array(
            'amount' => array(
                'value'    => $request->getAmount()->getValue(),
                'currency' => $request->getAmount()->getCurrency(),
            ),
        );
        return $result;
    }
}