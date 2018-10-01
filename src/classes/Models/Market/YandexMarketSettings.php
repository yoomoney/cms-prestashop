<?php

namespace YandexMoneyModule\Models\Market;


class YandexMarketSettings
{
    private $settings;

    private $currency;
    private $categoryTree;
    private $delivery;
    private $offerType;
    private $available;
    private $vat;
    private $additionalCondition;
    private $exportLink;

    /**
     * @param array $settings
     */
    public function __construct($settings)
    {
        $this->settings = $settings;
    }

    /**
     * @return YandexMarketSettingsCurrency
     */
    public function getCurrency()
    {
        if (!$this->currency) {
            $this->currency = new YandexMarketSettingsCurrency($this->settings);
        }

        return $this->currency;
    }

    /**
     * @return YandexMarketSettingsCategoryTree
     */
    public function getCategoryTree()
    {
        if (!$this->categoryTree) {
            $this->categoryTree = new YandexMarketSettingsCategoryTree($this->settings);
        }

        return $this->categoryTree;
    }

    /**
     * @return YandexMarketSettingsDelivery
     */
    public function getDelivery()
    {
        if (!$this->delivery) {
            $this->delivery = new YandexMarketSettingsDelivery($this->settings);
        }

        return $this->delivery;
    }

    /**
     * @return YandexMarketSettingsOfferType
     */
    public function getOfferType()
    {
        if (!$this->offerType) {
            $this->offerType = new YandexMarketSettingsOfferType($this->settings);
        }

        return $this->offerType;
    }


    /**
     * @return YandexMarketSettingsAvailable
     */
    public function getAvailable()
    {
        if (!$this->available) {
            $this->available = new YandexMarketSettingsAvailable($this->settings);
        }

        return $this->available;
    }


    /**
     * @return YandexMarketSettingsVat
     */
    public function getVat()
    {
        if (!$this->vat) {
            $this->vat = new YandexMarketSettingsVat($this->settings);
        }

        return $this->vat;
    }

    /**
     * @return YandexMarketSettingsAdditionalCondition
     */
    public function getAdditionalCondition()
    {
        if (!$this->additionalCondition) {
            $this->additionalCondition = new YandexMarketSettingsAdditionalCondition($this->settings);
        }

        return $this->additionalCondition;
    }

    /**
     * @return YandexMarketSettingsExportLink
     */
    public function getExportLink()
    {
        if (!$this->exportLink) {
            $this->exportLink = new YandexMarketSettingsExportLink($this->settings);
        }

        return $this->exportLink;
    }

}