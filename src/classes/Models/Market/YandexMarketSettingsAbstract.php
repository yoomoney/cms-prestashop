<?php

namespace YandexMoneyModule\Models\Market;


use Module;

abstract class YandexMarketSettingsAbstract
{
    /**
     * @var Module
     */
    protected $module;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @param $settings
     */
    public function __construct($settings)
    {
        $this->settings = (array)$settings;
    }

    /**
     * @return string
     */
    abstract protected function getSettingsKeyPrefix();

    /**
     * @param $key
     * @param $index
     * @param null $default
     * @return mixed
     */
    protected function getConfig($key, $index = null, $default = null)
    {
        return ym_get_settings($this->settings, $this->getSettingsKeyPrefix().$key, $index, $default);
    }

    /**
     * @param string $s
     * @return string
     */
    protected function l($s)
    {
        if ($this->module === null) {
            $this->module = Module::getInstanceByName('yandexmodule');
        }
        return $this->module->l($s, 'FormHelper');
    }

}