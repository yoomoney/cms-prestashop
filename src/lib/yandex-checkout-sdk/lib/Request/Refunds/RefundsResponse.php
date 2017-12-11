<?php

namespace YaMoney\Request\Refunds;

use YaMoney\Model\RefundInterface;

/**
 * Класс объекта ответа от API со списком возвратов магазина
 *
 * @package YaMoney\Request\Refunds
 */
class RefundsResponse
{
    /**
     * @var RefundInterface[] Массив возвратов
     */
    private $items;

    /**
     * @var string|null Токен следующей страницы
     */
    private $nextPage;

    /**
     * Конструктор, устанавливает свойства объекта из пришедшего из API ассоциативного массива
     * @param array $options Массив настроек, пришедший от API
     */
    public function __construct(array $options)
    {
        $this->items = array();
        foreach ($options['items'] as $item) {
            $this->items[] = new RefundResponse($item);
        }
        if (!empty($options['next_page'])) {
            $this->nextPage = $options['next_page'];
        }
    }

    /**
     * Возвращает список возвратов
     * @return RefundInterface[] Список возвратов
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Возвращает токен следующей страницы, если он задан, или null
     * @return string|null Токен следующей страницы
     */
    public function getNextPage()
    {
        return $this->nextPage;
    }

    /**
     * Проверяет имееотся ли в ответе токен следующей страницы
     * @return bool True если токен следующей страницы есть, false если нет
     */
    public function hasNextPage()
    {
        return $this->nextPage !== null;
    }
}
