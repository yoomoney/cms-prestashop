<?php
/**
 * @author YooMoney <cms@yoomoney.ru>
 * @copyright © 2020 "YooMoney", NBСO LLC
 * @license  https://yoomoney.ru/doc.xml?id=527052
 */

namespace YooMoneyModule\Models;

abstract class AbstractModel
{
    /**
     * @var \yoomoneymodule
     */
    protected $module;

    public function __construct(\yoomoneymodule $module)
    {
        $this->module = $module;
    }

    abstract public function validateOptions();
    abstract public function initConfiguration();
}
