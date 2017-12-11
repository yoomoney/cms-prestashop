<?php

namespace YaMoney\Request;

use YaMoney\Common\AbstractRequestBuilder;
use YaMoney\Model\AmountInterface;

/**
 * Класс билдера запросов для получения списка доступных способов оплаты
 *
 * @package YaMoney\Request
 */
class PaymentOptionsRequestBuilder extends AbstractRequestBuilder
{
    /**
     * @var PaymentOptionsRequest Инстанс собираемого запроса
     */
    protected $currentObject;

    /**
     * Инициализирует пустой запрос
     * @return PaymentOptionsRequest Инстанс запроса который будем собирать
     */
    protected function initCurrentObject()
    {
        return new PaymentOptionsRequest();
    }

    /**
     * Устанавливает идентификатор магазина
     * @param string|null $value Значение идентификатора магазина, null если требуется удалить значение
     * @return PaymentOptionsRequestBuilder Инстанс текущего билдера запросов
     */
    public function setAccountId($value)
    {
        $this->currentObject->setAccountId($value);
        return $this;
    }

    /**
     * Устанавливает идентификатор шлюза
     * @param string|null $value Значение идентификатора шлюза, null если требуется удалить значение
     * @return PaymentOptionsRequestBuilder Инстанс текущего билдера запросов
     */
    public function setGatewayId($value)
    {
        $this->currentObject->setGatewayId($value);
        return $this;
    }

    /**
     * Устанавливает сумму платежа
     * @param string|AmountInterface|null $value Сумма платежа, null если требуется удалить значение
     * @return PaymentOptionsRequestBuilder Инстанс текущего билдера запросов
     */
    public function setAmount($value)
    {
        if (empty($value)) {
            $this->currentObject->setAmount(null);
        } elseif ($value instanceof AmountInterface) {
            if ($value->getValue() > 0.0) {
                $this->currentObject->setAmount($value->getValue());
            }
            $this->currentObject->setCurrency($value->getCurrency());
        } else {
            $this->currentObject->setAmount($value);
        }
        return $this;
    }

    /**
     * Устанавливает код валюты в которой требуется провести платёж
     * @param string $value Код валюты, null если требуется удалить значение
     * @return PaymentOptionsRequestBuilder Инстанс текущего билдера запросов
     */
    public function setCurrency($value)
    {
        $this->currentObject->setCurrency($value);
        return $this;
    }

    /**
     * Устанавливает сценарий подтверждения платежа, для которого запрашивается список способов оплаты
     * @param string $value Сценарий подтверждения платежа
     * @return PaymentOptionsRequestBuilder Инстанс текущего билдера запросов
     */
    public function setConfirmationType($value)
    {
        $this->currentObject->setConfirmationType($value);
        return $this;
    }

    /**
     * Собирает и возвращает готовый объект запроса получения списка возможных способов оплаты
     * @param array|null $options Массив для устанавливаемых значений
     * @return PaymentOptionsRequestInterface Инстанс объекта запроса
     */
    public function build(array $options = null)
    {
        return parent::build($options);
    }
}
