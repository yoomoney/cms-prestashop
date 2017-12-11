<?php

namespace YaMoney\Model;

use YaMoney\Common\AbstractObject;

/**
 * Metadata - Метаданные платежа указанные мерчантом.
 * Мерчант может добавлять произвольные данные к платежам в виде набора пар ключ-значение.
 * Имена ключей уникальны.
 * 
 */
class Metadata extends AbstractObject implements \IteratorAggregate, \Countable
{
    public function toArray()
    {
        return $this->getUnknownProperties();
    }

    /**
     * @return \Iterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->getUnknownProperties());
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->getUnknownProperties());
    }
}
