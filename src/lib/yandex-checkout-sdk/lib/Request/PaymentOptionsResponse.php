<?php

namespace YaMoney\Request;

use YaMoney\Common\AbstractObject;

/**
 * Класс объекта ответа на запрос получения доступных способов оплаты
 *
 * @package YaMoney\Request
 *
 * @property-read PaymentOptionsResponseItem[] $items
 */
class PaymentOptionsResponse extends AbstractObject
{
    /**
     * Список способов оплаты подходящих для оплаты заказа
     * Если нет ни одного доступного способа оплаты, список будет пустым
     * @var PaymentOptionsResponseItem[] Список способов оплаты
     */
    private $_items;

    /**
     * Конструктор, устанавливает список полученныз от API способов оплаты
     * @param array $response Разобранный ответ от API в виде массива
     */
    public function __construct($response)
    {
        $this->_items = array();
        foreach ($response['items'] as $item) {
            $this->_items[] = new PaymentOptionsResponseItem($item);
        }
    }

    /**
     * Возаращает список способов оплаты подходящих для оплаты заказа
     * Если нет ни одного доступного способа оплаты, список будет пустым
     * @return PaymentOptionsResponseItem[] Список способов оплаты
     */
    public function getItems()
    {
        return $this->_items;
    }
}
