<?php
/**
 * @author Yandex.Money <cms@yamoney.ru>
 * @copyright © 2015-2017 NBCO Yandex.Money LLC
 * @license  https://money.yandex.ru/doc.xml?id=527052
 */

namespace YandexMoneyModule\Models;

abstract class AbstractModel
{
    /**
     * @var \yandexmodule
     */
    protected $module;

    public function __construct(\yandexmodule $module)
    {
        $this->module = $module;
    }

    abstract public function validateOptions();
    abstract public function initConfiguration();
}
