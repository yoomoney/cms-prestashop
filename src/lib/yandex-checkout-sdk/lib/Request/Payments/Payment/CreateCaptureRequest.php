<?php

namespace YaMoney\Request\Payments\Payment;

use YaMoney\Common\AbstractRequest;
use YaMoney\Model\AmountInterface;

/**
 * Класс объекта запроса к API на подтверждение оплаты
 *
 * @property AmountInterface $amount Подтверждаемая сумма оплаты
 */
class CreateCaptureRequest extends AbstractRequest implements CreateCaptureRequestInterface
{
    /**
     * @var AmountInterface Подтверждаемая сумма оплаты
     */
    private $_amount;

    /**
     * Возвращает подтвердаемую сумму оплаты
     * @return AmountInterface Подтверждаемая сумма оплаты
     */
    public function getAmount()
    {
        return $this->_amount;
    }

    /**
     * Проверяет была ли установлена сумма оплаты
     * @return bool True если сумма оплаты была установлена, false если нет
     */
    public function hasAmount()
    {
        return !empty($this->_amount);
    }

    /**
     * Устанавливает сумму оплаты
     * @param AmountInterface $value Подтверждаемая сумма оплаты
     */
    public function setAmount(AmountInterface $value)
    {
        $this->_amount = $value;
    }

    /**
     * Валидирует объект запроса
     * @return bool True если запрос валиден и его можно отправить в API, false если нет
     */
    public function validate()
    {
        if ($this->_amount === null) {
            $this->setValidationError('Amount not specified in CreateCaptureRequest');
            return false;
        }
        $value = $this->_amount->getValue();
        if (empty($value) || $value <= 0.0) {
            $this->setValidationError('Invalid amount value: ' . $value);
            return false;
        }
        return true;
    }

    /**
     * Возвращает билдер объектов запросов на подтверждение оплаты
     * @return CreateCaptureRequestBuilder Инстанс билдера
     */
    public static function builder()
    {
        return new CreateCaptureRequestBuilder();
    }
}
