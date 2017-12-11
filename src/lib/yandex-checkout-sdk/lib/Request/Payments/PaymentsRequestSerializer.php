<?php

namespace YaMoney\Request\Payments;

/**
 * Класс сериализатора объектов запросов к API для получения списка платежей
 *
 * @package YaMoney\Request\Payments
 */
class PaymentsRequestSerializer
{
    /**
     * @var array Карта маппинга свойств объекта запроса на поля отправляемого запроса
     */
    private static $propertyMap = array(
        'paymentId'      => 'payment_id',
        'gatewayId'      => 'gateway_id',
        'createdGte'     => 'created_gte',
        'createdGt'      => 'created_gt',
        'createdLte'     => 'created_lte',
        'createdLt'      => 'created_lt',
        'authorizedGte'  => 'authorized_gte',
        'authorizedGt'   => 'authorized_gt',
        'authorizedLte'  => 'authorized_lte',
        'authorizedLt'   => 'authorized_lt',
        'status'         => 'status',
        'nextPage'       => 'next_page',
    );

    /**
     * Сериализует объект запроса к API для дальнейшей его отправки
     * @param PaymentsRequestInterface $request Сериализуемый объект
     * @return array Массив с инфомрацией, отпарвляемый в дальнейшем в API
     */
    public function serialize(PaymentsRequestInterface $request)
    {
        $result = array(
            'account_id' => $request->getAccountId(),
        );
        foreach (self::$propertyMap as $property => $name) {
            $value = $request->{$property};
            if (!empty($value)) {
                if ($value instanceof \DateTime) {
                    if ($value->getTimestamp() > 1) {
                        $result[$name] = $value->format(DATE_ATOM);
                    }
                } else {
                    $result[$name] = $value;
                }
            }
        }
        return $result;
    }
}
