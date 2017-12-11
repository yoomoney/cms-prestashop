<?php

namespace YaMoney\Request\Payments;

use YaMoney\Common\AbstractRequestBuilder;
use YaMoney\Common\Exceptions\EmptyPropertyValueException;
use YaMoney\Common\Exceptions\InvalidPropertyValueException;
use YaMoney\Common\Exceptions\InvalidPropertyValueTypeException;
use YaMoney\Common\Exceptions\InvalidRequestException;
use YaMoney\Model\AmountInterface;
use YaMoney\Model\ConfirmationAttributes\AbstractConfirmationAttributes;
use YaMoney\Model\ConfirmationAttributes\ConfirmationAttributesFactory;
use YaMoney\Model\Metadata;
use YaMoney\Model\MonetaryAmount;
use YaMoney\Model\PaymentData\AbstractPaymentData;
use YaMoney\Model\PaymentData\PaymentDataFactory;
use YaMoney\Model\Receipt;
use YaMoney\Model\ReceiptItem;
use YaMoney\Model\ReceiptItemInterface;
use YaMoney\Model\Recipient;

/**
 * Класс билдера объектов запрсов к API на создание платежа
 *
 * @package YaMoney\Request\Payments
 */
class CreatePaymentRequestBuilder extends AbstractRequestBuilder
{
    /**
     * @var CreatePaymentRequest Собираемый объект запроса
     */
    protected $currentObject;

    /**
     * @var Recipient Получатель платежа
     */
    private $recipient;

    /**
     * @var Receipt Объект с информацией о чеке
     */
    private $receipt;

    /**
     * @var MonetaryAmount Сумма заказа
     */
    private $amount;

    /**
     * @var PaymentDataFactory Фабрика методов проведения платежей
     */
    private $paymentDataFactory;

    /**
     * @var ConfirmationAttributesFactory Фабрика объектов методов подтверждения платежей
     */
    private $confirmationFactory;

    /**
     * Инициализирует объект запроса, который в дальнейшем будет собираться билдером
     * @return CreatePaymentRequest Инстанс собираемого объекта запроса к API
     */
    protected function initCurrentObject()
    {
        $request = new CreatePaymentRequest();

        $this->recipient = new Recipient();
        $this->receipt = new Receipt();
        $this->amount = new MonetaryAmount();

        return $request;
    }

    /**
     * Устанавливает идентификатор магазина получателя платежа
     * @param string $value Идентификатор магазина
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
     *
     * @throws EmptyPropertyValueException Выбрасывается если было передано пустое значение
     * @throws InvalidPropertyValueTypeException Выбрасывается если было передано не строковое значение
     */
    public function setAccountId($value)
    {
        $this->recipient->setAccountId($value);
        return $this;
    }

    /**
     * Устанавливает идентификатор шлюза
     * @param string $value Идентификатор шлюза
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
     *
     * @throws EmptyPropertyValueException Выбрасывается если было передано пустое значение
     * @throws InvalidPropertyValueTypeException Выбрасывается если было передано не строковое значение
     */
    public function setGatewayId($value)
    {
        $this->recipient->setGatewayId($value);
        return $this;
    }

    /**
     * Устанавливает сумму заказа
     * @param AmountInterface|string $value Сумма заказа
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
     *
     * @throws EmptyPropertyValueException Выбрасывается если было передано пустое значение
     * @throws InvalidPropertyValueException Выбрасывается если был передан ноль или отрицательное значение
     * @throws InvalidPropertyValueTypeException Выбрасывается если было передано не строковое значение
     */
    public function setAmount($value)
    {
        if ($value instanceof AmountInterface) {
            $this->amount->setValue($value->getValue());
            $this->amount->setCurrency($value->getCurrency());
        } elseif ($value === null || $value === '') {
            throw new EmptyPropertyValueException('Empty payment amount value', 0, 'CreatePaymentRequest.amount');
        } elseif (!is_numeric($value)) {
            throw new InvalidPropertyValueTypeException(
                'Invalid payment amount value type', 0, 'CreatePaymentRequest.amount', $value
            );
        } elseif ($value > 0) {
            $this->amount->setValue($value);
        } else {
            throw new InvalidPropertyValueException(
                'Invalid payment amount value', 0, 'CreatePaymentRequest.amount', $value
            );
        }
        return $this;
    }

    /**
     * Устанавливает валюту в которой заказ оплачивается
     * @param string $value Код валюты заказа
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
     *
     * @throws EmptyPropertyValueException Генерируется если было передано пустое значение
     * @throws InvalidPropertyValueTypeException Генерируется если было передано значение невалидного типа
     * @throws InvalidPropertyValueException Генерируется если был передан неподдерживаемый код валюты
     */
    public function setCurrency($value)
    {
        $this->amount->setCurrency($value);
        foreach ($this->receipt->getItems() as $item) {
            $item->getPrice()->setCurrency($value);
        }
        return $this;
    }

    /**
     * Устанавлвиает список товаров в заказе для создания чека
     * @param array $value Массив товаров в заказе
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
     * 
     * @throws InvalidPropertyValueException Генерируется если хотя бы один из товаров имеет неверную структуру
     */
    public function setReceiptItems($value)
    {
        $this->receipt->setItems(array());
        $index = 0;
        foreach ($value as $item) {
            if ($item instanceof ReceiptItemInterface) {
                $this->receipt->addItem($item);
            } else {
                if (empty($item['title']) && empty($item['description'])) {
                    throw new InvalidPropertyValueException(
                        'Item#' . $index . ' title or description not specified',
                        0,
                        'CreatePaymentRequest.items[' . $index . '].title',
                        json_encode($item)
                    );
                }
                if (empty($item['price'])) {
                    throw new InvalidPropertyValueException(
                        'Item#' . $index . ' price not specified',
                        0,
                        'CreatePaymentRequest.items[' . $index . '].price',
                        json_encode($item)
                    );
                }
                $this->addReceiptItem(
                    empty($item['title']) ? $item['description'] : $item['title'],
                    $item['price'],
                    empty($item['quantity']) ? 1.0 : $item['quantity'],
                    empty($item['vatCode']) ? null : $item['vatCode']
                );
            }
            $index++;
        }
        return $this;
    }

    /**
     * Добавляет в чек товар
     * @param string $title Название или описание товара
     * @param string $price Цена товара в валюте, заданной в заказе
     * @param float $quantity Количество покупаемого товара
     * @param int|null $vatCode Ставка НДС, или null если используется ставка НДС заказа
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
     */
    public function addReceiptItem($title, $price, $quantity = 1.0, $vatCode = null)
    {
        $item = new ReceiptItem();
        $item->setDescription($title);
        $item->setQuantity($quantity);
        $item->setVatCode($vatCode);
        $item->setPrice(new MonetaryAmount($price, $this->amount->getCurrency()));
        $this->receipt->addItem($item);
        return $this;
    }

    /**
     * Добавляет в чек доставку товара
     * @param string $title Название доставки в чеке
     * @param string $price Стоимость доставки
     * @param int|null $vatCode Ставка НДС, или null если используется ставка НДС заказа
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
     */
    public function addReceiptShipping($title, $price, $vatCode = null)
    {
        $item = new ReceiptItem();
        $item->setDescription($title);
        $item->setQuantity(1);
        $item->setVatCode($vatCode);
        $item->setIsShipping(true);
        $item->setPrice(new MonetaryAmount($price, $this->amount->getCurrency()));
        $this->receipt->addItem($item);
        return $this;
    }

    /**
     * Устанавливает адрес электронной почты получателя чека
     * @param string $value Email получателя чека
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueTypeException Генерируется если было передано значение невалидного типа
     */
    public function setReceiptEmail($value)
    {
        $this->receipt->setEmail($value);
        return $this;
    }

    /**
     * Устанавливает телефон получателя чека
     * @param string $value Телефон получателя чека
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueException Генерируется если был передан не телефон, а что-то другое
     * @throws InvalidPropertyValueTypeException Генерируется если было передано значение невалидного типа
     */
    public function setReceiptPhone($value)
    {
        $this->receipt->setPhone($value);
        return $this;
    }

    /**
     * Устанавливает код системы налогообложения.
     * @param int $value Код системы налогообложения. Число 1-6.
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueTypeException Выбрасывается если переданный аргумент - не число
     * @throws InvalidPropertyValueException Выбрасывается если переданный аргумент меньше одного или больше шести
     */
    public function setTaxSystemCode($value)
    {
        $this->receipt->setTaxSystemCode($value);
        return $this;
    }

    /**
     * Устанавливает одноразовый токен для проведения оплаты
     * @param string $value Одноразовый токен для проведения оплаты
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueException Выбрасывается если переданное значение длинее 200 символов
     * @throws InvalidPropertyValueTypeException Выбрасывается если переданное значение не является строкой
     */
    public function setPaymentToken($value)
    {
        $this->currentObject->setPaymentToken($value);
        return $this;
    }

    /**
     * Устанавливает идентификатор записи о сохранённых данных покупателя
     * @param string $value Идентификатор записи о сохраненных платежных данных покупателя
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueTypeException Генерируется если переданные значение не является строкой или null
     */
    public function setPaymentMethodId($value)
    {
        $this->currentObject->setPaymentMethodId($value);
        return $this;
    }

    /**
     * Устанавливает объект с информацией для создания метода оплаты
     * @param AbstractPaymentData|string|array|null $value Объект с создания метода оплаты или null
     * @param array $options Настройки способа оплаты в виде ассоциативного массива
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueTypeException Выбрасывается если был передан объект невалидного типа
     */
    public function setPaymentMethodData($value, array $options = null)
    {
        if (is_string($value) && $value !== '') {
            if (empty($options)) {
                $value = $this->getPaymentDataFactory()->factory($value);
            } else {
                $value = $this->getPaymentDataFactory()->factoryFromArray($options, $value);
            }
        } elseif (is_array($value)) {
            $value = $this->getPaymentDataFactory()->factoryFromArray($value);
        }
        $this->currentObject->setPaymentMethodData($value);
        return $this;
    }

    /**
     * Устанавливает способ подтверждения платежа
     * @param AbstractConfirmationAttributes|string|array|null $value Способ подтверждения платежа
     * @param array|null $options Настройки способа подтверждения платежа в виде массива
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueTypeException Выбрасывается если переданное значение не является объектом типа
     * AbstractConfirmationAttributes или null
     */
    public function setConfirmation($value, array $options = null)
    {
        if (is_string($value) && $value !== '') {
            if (empty($options)) {
                $value = $this->getConfirmationFactory()->factory($value);
            } else {
                $value = $this->getConfirmationFactory()->factoryFromArray($options, $value);
            }
        } elseif (is_array($value)) {
            $value = $this->getConfirmationFactory()->factoryFromArray($value);
        }
        $this->currentObject->setConfirmation($value);
        return $this;
    }

    /**
     * Устанавливает флаг сохранения платёжных данных. Значение true инициирует создание многоразового payment_method.
     * @param bool $value Сохранить платежные данные для последующего использования
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueTypeException Генерируется если переданный аргумент не кастится в bool
     */
    public function setSavePaymentMethod($value)
    {
        $this->currentObject->setSavePaymentMethod($value);
        return $this;
    }

    /**
     * Устанавливает флаг автоматического принятия поступившей оплаты
     * @param bool $value Автоматически принять поступившую оплату
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueTypeException Генерируется если переданный аргумент не кастится в bool

     */
    public function setCapture($value)
    {
        $this->currentObject->setCapture($value);
        return $this;
    }

    /**
     * Устанавливает IP адрес покупателя
     * @param string $value IPv4 или IPv6-адрес покупателя
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueTypeException Выбрасывается если переданный аргумент не является строкой
     */
    public function setClientIp($value)
    {
        $this->currentObject->setClientIp($value);
        return $this;
    }

    /**
     * Устанавливает метаданные, привязанные к платежу
     * @param Metadata|null $value Метаданные платежа, устанавливаемые мерчантом
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueTypeException Выбрасывается если переданные данные не удалось интерпретировать как
     * метаданные платежа
     */
    public function setMetadata($value)
    {
        $this->currentObject->setMetadata($value);
        return $this;
    }

    /**
     * Строит и возвращает объект запроса для отправки в API яндекс денег
     * @param array|null $options Массив параметров для установки в объект запроса
     * @return CreatePaymentRequestInterface Инстанс объекта запроса
     *
     * @throws InvalidRequestException Выбрасывается если собрать объект запроса не удалось
     */
    public function build(array $options = null)
    {
        if (!empty($options)) {
            $this->setOptions($options);
        }
        $accountId = $this->recipient->getAccountId();
        $gatewayId = $this->recipient->getGatewayId();
        if (!empty($accountId) && !empty($gatewayId)) {
            $this->currentObject->setRecipient($this->recipient);
        }
        if ($this->receipt->notEmpty()) {
            $this->currentObject->setReceipt($this->receipt);
        }
        $this->currentObject->setAmount($this->amount);
        return parent::build();
    }

    /**
     * Возвращает фабрику методов проведения платежей
     * @return PaymentDataFactory Фабрика методов проведения платежей
     */
    protected function getPaymentDataFactory()
    {
        if ($this->paymentDataFactory === null) {
            $this->paymentDataFactory = new PaymentDataFactory();
        }
        return $this->paymentDataFactory;
    }

    /**
     * Возвращает фабрику для создания методов подтверждения платежей
     * @return ConfirmationAttributesFactory Фабрика объектов методов подтверждения платежей
     */
    protected function getConfirmationFactory()
    {
        if ($this->confirmationFactory === null) {
            $this->confirmationFactory = new ConfirmationAttributesFactory();
        }
        return $this->confirmationFactory;
    }
}