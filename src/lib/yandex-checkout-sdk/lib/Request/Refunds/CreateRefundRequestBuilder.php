<?php

namespace YaMoney\Request\Refunds;

use YaMoney\Common\AbstractRequestBuilder;
use YaMoney\Common\Exceptions\EmptyPropertyValueException;
use YaMoney\Common\Exceptions\InvalidPropertyValueException;
use YaMoney\Common\Exceptions\InvalidPropertyValueTypeException;
use YaMoney\Model\AmountInterface;
use YaMoney\Model\MonetaryAmount;
use YaMoney\Model\Receipt;
use YaMoney\Model\ReceiptItem;
use YaMoney\Model\ReceiptItemInterface;

/**
 * Класс билдера запросов к API на создание возврата средств
 *
 * @package YaMoney\Request\Refunds
 */
class CreateRefundRequestBuilder extends AbstractRequestBuilder
{
    /**
     * @var CreateRefundRequest Собираемый объет запроса к API
     */
    protected $currentObject;

    /**
     * @var MonetaryAmount Сумма возвращаемых средств
     */
    private $amount;

    /**
     * @var Receipt Инстанс чека
     */
    private $receipt;

    /**
     * Возвращает новый объект для сборки
     * @return CreateRefundRequest Собираемый объет запроса к API
     */
    protected function initCurrentObject()
    {
        $request = new CreateRefundRequest();
        $this->amount = new MonetaryAmount();
        $this->receipt = new Receipt();
        return $request;
    }

    /**
     * Устанавливает айди платежа для которого создаётся возврат
     * @param string $value Айди платежа
     * @return CreateRefundRequestBuilder Инстанс текущего билдера
     *
     * @throws EmptyPropertyValueException Выбрасывается если передано пустое значение айди платежа
     * @throws InvalidPropertyValueException Выбрасывается если переданное значение является строкой, но не является
     * валидным значением айди платежа
     * @throws InvalidPropertyValueTypeException Выбрасывается если передано значение не валидного типа
     */
    public function setPaymentId($value)
    {
        $this->currentObject->setPaymentId($value);
        return $this;
    }

    /**
     * Устанавливает сумму возвращаемых средств
     * @param AmountInterface $value Сумма возврата
     * @return CreateRefundRequestBuilder Инстанс текущего билдера
     *
     * @throws EmptyPropertyValueException Генерируется если было передано пустое значение
     * @throws InvalidPropertyValueTypeException Генерируется если было передано значение невалидного типа
     * @throws InvalidPropertyValueException Генерируется если было передано не валидное значение
     */
    public function setAmount($value)
    {
        if ($value instanceof AmountInterface) {
            $this->amount->setValue($value->getValue());
            $this->amount->setCurrency($value->getCurrency());
        } else {
            $this->amount->setValue($value);
        }
        return $this;
    }

    /**
     * Устанавливает валюту в которой средства возвращаются
     * @param string $value Код валюты
     * @return CreateRefundRequestBuilder Инстанс текущего билдера
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
     * Устанавливает комментарий к возврату
     * @param string $value Комментарий к возврату
     * @return CreateRefundRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueException Выбрасывается если переданная строка длинее 250 символов
     * @throws InvalidPropertyValueTypeException Выбрасывается если была передана не строка
     */
    public function setComment($value)
    {
        $this->currentObject->setComment($value);
        return $this;
    }

    /**
     * Устанавлвиает список товаров в заказе для создания чека
     * @param array $value Массив товаров в заказе
     * @return CreateRefundRequestBuilder Инстанс текущего билдера
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
     * @return CreateRefundRequestBuilder Инстанс текущего билдера
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
     * @return CreateRefundRequestBuilder Инстанс текущего билдера
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
     * @return CreateRefundRequestBuilder Инстанс текущего билдера
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
     * @return CreateRefundRequestBuilder Инстанс текущего билдера
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
     * @return CreateRefundRequestBuilder Инстанс текущего билдера
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
     * Строит объект запроса к API
     * @param array|null $options Устаналвиваемые параметры запроса
     * @return CreateRefundRequestInterface Инстанс сгенерированного объекта запроса к API
     */
    public function build(array $options = null)
    {
        if (!empty($options)) {
            $this->setOptions($options);
        }
        $this->currentObject->setAmount($this->amount);
        if ($this->receipt->notEmpty()) {
            $this->currentObject->setReceipt($this->receipt);
        }
        return parent::build();
    }
}