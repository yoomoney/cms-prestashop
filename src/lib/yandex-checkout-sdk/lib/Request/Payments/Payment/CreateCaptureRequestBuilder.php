<?php

namespace YaMoney\Request\Payments\Payment;

use YaMoney\Common\AbstractRequestBuilder;
use YaMoney\Common\Exceptions\EmptyPropertyValueException;
use YaMoney\Common\Exceptions\InvalidPropertyException;
use YaMoney\Common\Exceptions\InvalidPropertyValueException;
use YaMoney\Common\Exceptions\InvalidPropertyValueTypeException;
use YaMoney\Common\Exceptions\InvalidRequestException;
use YaMoney\Model\AmountInterface;
use YaMoney\Model\MonetaryAmount;

class CreateCaptureRequestBuilder extends AbstractRequestBuilder
{
    /**
     * @var CreateCaptureRequest
     */
    protected $currentObject;

    /**
     * @var MonetaryAmount
     */
    private $amount;

    /**
     * @return CreateCaptureRequest
     */
    protected function initCurrentObject()
    {
        $this->amount = new MonetaryAmount();
        return new CreateCaptureRequest();
    }

    /**
     * Устанавливает сумму оплаты
     * @param AmountInterface|string $value Подтверждаемая сумма оплаты
     * @return CreateCaptureRequestBuilder Инстанс билдера запросов на подтверждение суммы оплаты
     *
     * @throws EmptyPropertyValueException Генерируется если было передано пустое значение
     * @throws InvalidPropertyValueException Выбрасывается если переданная сумма меньше или равна нулю
     * @throws InvalidPropertyValueTypeException Выбрасывается если переданная сумма не является числом или объектом
     * типа AmountInterface
     */
    public function setAmount($value)
    {
        if ($value === null || $value === '') {
            throw new EmptyPropertyValueException('Empty currency value', 0, 'amount.currency');
        } elseif (is_object($value) && $value instanceof AmountInterface) {
            $this->amount->setValue($value->getValue());
            $this->amount->setCurrency($value->getCurrency());
        } elseif (is_numeric($value)) {
            $this->amount->setValue($value);
        } else {
            throw new InvalidPropertyValueTypeException(
                'Invalid amount value type in CreateCaptureRequestBuilder',
                0,
                'CreateCaptureRequestBuilder.amount',
                $value
            );
        }
        return $this;
    }

    /**
     * Устанавливает валюту в которой будет происходить подтверждение оплаты заказа
     * @param string $value Валюта в которой подтверждается оплата
     * @return CreateCaptureRequestBuilder Инстанс билдера запросов на подтверждение суммы оплаты
     *
     * @throws EmptyPropertyValueException Генерируется если было передано пустое значение
     * @throws InvalidPropertyValueTypeException Генерируется если было передано значение невалидного типа
     * @throws InvalidPropertyValueException Генерируется если был передан неподдерживаемый код валюты
     */
    public function setCurrency($value)
    {
        $this->amount->setCurrency($value);
        return $this;
    }

    /**
     * Осуществляет сборку объекта запроса к API
     * @param array|null $options Массив дополнительных настроек объекта
     * @return CreateCaptureRequestInterface Иснатс объекта запроса к API
     *
     * @throws InvalidRequestException Выбрасывается если при валидации запроса произошла ошибка
     * @throws InvalidPropertyException Выбрасывается если не удалось установить один из параметров, переданных в
     * массиве настроек
     */
    public function build(array $options = null)
    {
        if (!empty($options)) {
            $this->setOptions($options);
        }
        $this->currentObject->setAmount($this->amount);
        return parent::build();
    }
}
