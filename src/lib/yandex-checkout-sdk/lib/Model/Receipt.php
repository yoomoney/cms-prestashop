<?php

namespace YaMoney\Model;

use YaMoney\Common\AbstractObject;
use YaMoney\Common\Exceptions\EmptyPropertyValueException;
use YaMoney\Common\Exceptions\InvalidPropertyValueException;
use YaMoney\Common\Exceptions\InvalidPropertyValueTypeException;
use YaMoney\Helpers\TypeCast;

/**
 * @property ReceiptItemInterface[] $items Список товаров в заказе
 * @property int $taxSystemCode Код системы налогообложения. Число 1-6.
 * @property string $phone Номер телефона плательщика в формате ITU-T E.164 на который будет выслан чек.
 * @property string $email E-mail адрес плательщика на который будет выслан чек.
 */
class Receipt extends AbstractObject implements ReceiptInterface
{
    /**
     * @var ReceiptItem[] Список товаров в заказе
     */
    private $_items = array();

    /**
     * @var ReceiptItem[] Список айтемов в заказе, являющихся доставкой
     */
    private $_shippingItems = array();

    /**
     * @var int Код системы налогообложения. Число 1-6.
     */
    private $_taxSystemCode;

    /**
     * @var string Номер телефона плательщика в формате ITU-T E.164 на который будет выслан чек.
     */
    private $_phone;

    /**
     * @var string E-mail адрес плательщика на который будет выслан чек.
     */
    private $_email;

    /**
     * @return ReceiptItemInterface[] Список товаров в заказе
     */
    public function getItems()
    {
        return $this->_items;
    }

    /**
     * @param array $value Список товаров в заказе
     */
    public function setItems($value)
    {
        if ($value === null || $value === '') {
            throw new EmptyPropertyValueException('Empty items value in receipt', 0, 'receipt.items');
        }
        if (!is_array($value) && !($value instanceof \Traversable)) {
            throw new InvalidPropertyValueTypeException(
                'Invalid items value type in receipt', 0, 'receipt.items', $value
            );
        }
        $this->_items = array();
        $this->_shippingItems = array();
        foreach ($value as $key => $val) {
            $this->_items[$key] = $val;
            if ($val->isShipping()) {
                $this->_shippingItems[] = $val;
            }
        }
    }

    public function addItem(ReceiptItemInterface $value)
    {
        $this->_items[] = $value;
        if ($value->isShipping()) {
            $this->_shippingItems[] = $value;
        }
    }

    /**
     * @return int Код системы налогообложения. Число 1-6.
     */
    public function getTaxSystemCode()
    {
        return $this->_taxSystemCode;
    }

    /**
     * Устанавливает код системы налогообложения
     * @param int $value Код системы налогообложения. Число 1-6
     *
     * @throws InvalidPropertyValueTypeException Выбрасывается если переданный аргумент - не число
     * @throws InvalidPropertyValueException Выбрасывается если переданный аргумент меньше одного или больше шести
     */
    public function setTaxSystemCode($value)
    {
        if ($value === null || $value === '') {
            $this->_taxSystemCode = null;
        } elseif (!is_numeric($value)) {
            throw new InvalidPropertyValueTypeException(
                'Invalid taxSystemCode value type', 0, 'receipt.taxSystemCode'
            );
        } else {
            $castedValue = (int)$value;
            if ($castedValue < 1 || $castedValue > 6) {
                throw new InvalidPropertyValueException(
                    'Invalid taxSystemCode value: ' . $value, 0, 'receipt.taxSystemCode'
                );
            }
            $this->_taxSystemCode = $castedValue;
        }
    }

    /**
     * @return string Номер телефона плательщика в формате ITU-T E.164 на который будет выслан чек.
     */
    public function getPhone()
    {
        return $this->_phone;
    }

    /**
     * @param string $value Номер телефона плательщика в формате ITU-T E.164 на который будет выслан чек.
     */
    public function setPhone($value)
    {
        if ($value === null || $value === '') {
            $this->_phone = null;
        } elseif (!TypeCast::canCastToString($value)) {
            throw new InvalidPropertyValueTypeException('Invalid phone value type', 0, 'receipt.phone');
        } elseif (!preg_match('/^[0-9]{4,15}$/', (string)$value)) {
            throw new InvalidPropertyValueException('Invalid phone value: "' . $value . '"', 0, 'receipt.phone');
        } else {
            $this->_phone = (string)$value;
        }
    }

    /**
     * @return string E-mail адрес плательщика на который будет выслан чек.
     */
    public function getEmail()
    {
        return $this->_email;
    }

    /**
     * @param string $value E-mail адрес плательщика на который будет выслан чек.
     */
    public function setEmail($value)
    {
        if ($value === null || $value === '') {
            $this->_email = null;
        } elseif (!TypeCast::canCastToString($value)) {
            throw new InvalidPropertyValueTypeException('Invalid email value type', 0, 'receipt.email');
        } else {
            $this->_email = (string)$value;
        }
    }

    /**
     * @return bool
     */
    public function notEmpty()
    {
        return !empty($this->_items);
    }

    /**
     * Возвращает стоимость заказа исходя из состава чека
     * @param bool $withShipping Добавить ли к стоимости заказа стоимость доставки
     * @return int Общая стоимость заказа в центах/копейках
     */
    public function getAmountValue($withShipping = true)
    {
        $result = 0;
        foreach ($this->_items as $item) {
            if ($withShipping || !$item->isShipping()) {
                $result += $item->getAmount();
            }
        }
        return $result;
    }

    /**
     * Возвращает стоимость доставки исходя из состава чека
     * @return int Стоимость доставки из состава чека в центах/копейках
     */
    public function getShippingAmountValue()
    {
        $result = 0;
        foreach ($this->_items as $item) {
            if ($item->isShipping()) {
                $result += $item->getAmount();
            }
        }
        return $result;
    }

    /**
     * Подгоняет стоимость товаров в чеке к общей цене заказа
     * @param AmountInterface $orderAmount Общая стоимость заказа
     * @param bool $withShipping Поменять ли заодно и цену доставки
     */
    public function normalize(AmountInterface $orderAmount, $withShipping = false)
    {
        $amount = $orderAmount->getIntegerValue();
        if (!$withShipping) {
            if ($this->_shippingItems !== null) {
                $amount -= $this->getShippingAmountValue();
            }
        }
        $realAmount = $this->getAmountValue($withShipping);
        if ($realAmount !== $amount) {
            $coefficient = (float)$amount / (float)$realAmount;
            $realAmount = 0;
            $aloneId = null;
            foreach ($this->_items as $index => $item) {
                if ($withShipping || !$item->isShipping()) {
                    $item->applyDiscountCoefficient($coefficient);
                    $realAmount += $item->getAmount();
                    if ($aloneId === null && $item->getQuantity() === 1.0 && !$item->isShipping()) {
                        $aloneId = $index;
                    }
                }
            }
            if ($aloneId === null) {
                foreach ($this->_items as $index => $item) {
                    if (!$item->isShipping()) {
                        $aloneId = $index;
                        break;
                    }
                }
            }
            if ($aloneId === null) {
                $aloneId = 0;
            }
            $diff = $amount - $realAmount;
            if (abs($diff) >= 0.1) {
                if ($this->_items[$aloneId]->getQuantity() === 1.0) {
                    $this->_items[$aloneId]->increasePrice($diff / 100.0);
                } elseif ($this->_items[$aloneId]->getQuantity() > 1.0) {
                    $item = $this->_items[$aloneId]->fetchItem(1);
                    $item->increasePrice($diff / 100.0);
                    array_splice($this->_items, $aloneId + 1, 0, array($item));
                } else {
                    $item = $this->_items[$aloneId]->fetchItem($this->_items[$aloneId]->getQuantity() / 2);
                    $item->increasePrice($diff / 100.0);
                    array_splice($this->_items, $aloneId + 1, 0, array($item));
                }
            }
        }
    }
}
