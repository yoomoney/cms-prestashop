<?php
/**
 * @author Yandex.Money <cms@yamoney.ru>
 * @copyright © 2015-2017 NBCO Yandex.Money LLC
 * @license  https://money.yandex.ru/doc.xml?id=527052
 */

namespace YandexMoneyModule\Models;

use Category;
use Configuration;
use Context;
use Currency;
use Image;
use Product;
use Tools;
use Link;
use YandexMoneyModule\YandexMarket\Offer;
use YandexMoneyModule\YandexMarket\ProductCategory;
use YandexMoneyModule\YandexMarket\ShopInfo;
use YandexMoneyModule\YandexMarket\YandexMarket;

class MarketModel extends AbstractModel
{
    private $settings;
    private $additionalConditionMap;

    public function validateOptions()
    {
        $errors = '';

        $simpleParams = array(
            'YA_MARKET_SHOP_NAME',
            'YA_MARKET_FULL_SHOP_NAME',
            'YA_MARKET_CATEGORY_ALL_ENABLED',
            'YA_MARKET_OFFER_TYPE_SIMPLE',
            'YA_MARKET_OFFER_TYPE_NAME_TEMPLATE',
            'YA_MARKET_VAT_ENABLED',
            'YA_MARKET_COMBINATION_EXPORT_ALL',
            'YA_MARKET_OFFER_OPTIONS_EXPORT_PARAMS',
            'YA_MARKET_OFFER_OPTIONS_EXPORT_DIMENSION',
        );
        foreach ($simpleParams as $param) {
            Configuration::UpdateValue($param, Tools::getValue($param));
        }

        $arrayParams = array(
            'YA_MARKET_CURRENCY_ENABLED',
            'YA_MARKET_CURRENCY_RATE',
            'YA_MARKET_CURRENCY_PLUS',
            'YA_MARKET_CATEGORY_LIST',
            'YA_MARKET_DELIVERY_ENABLED',
            'YA_MARKET_DELIVERY_COST',
            'YA_MARKET_DELIVERY_DAYS_FROM',
            'YA_MARKET_DELIVERY_DAYS_TO',
            'YA_MARKET_DELIVERY_ORDER_BEFORE',
            'YA_MARKET_AVAILABLE_ENABLED',
            'YA_MARKET_AVAILABLE_AVAILABLE',
            'YA_MARKET_AVAILABLE_DELIVERY',
            'YA_MARKET_AVAILABLE_PICKUP',
            'YA_MARKET_AVAILABLE_STORE',
            'YA_MARKET_VAT_LIST',
            'YA_MARKET_ADDITIONAL_CONDITION_ENABLED',
            'YA_MARKET_ADDITIONAL_CONDITION_NAME',
            'YA_MARKET_ADDITIONAL_CONDITION_TAG',
            'YA_MARKET_ADDITIONAL_CONDITION_TYPE_VALUE',
            'YA_MARKET_ADDITIONAL_CONDITION_STATIC_VALUE',
            'YA_MARKET_ADDITIONAL_CONDITION_DATA_VALUE',
            'YA_MARKET_ADDITIONAL_CONDITION_FOR_ALL_CAT',
            'YA_MARKET_ADDITIONAL_CONDITION_JOIN',
            'YA_MARKET_ADDITIONAL_CONDITION_CATEGORIES',
        );
        foreach ($arrayParams as $param) {
            Configuration::UpdateValue($param, json_encode(Tools::getValue($param)));
        }

        if ($errors == '') {
            $errors = $this->module->displayConfirmation($this->module->l('Settings saved successfully!'));
        }

        return $errors;
    }

    public function initConfiguration()
    {
        $this->settings = Configuration::getMultiple(array(
            'YA_MARKET_SHOP_NAME',
            'YA_MARKET_FULL_SHOP_NAME',
            'YA_MARKET_CURRENCY_ENABLED',
            'YA_MARKET_CURRENCY_RATE',
            'YA_MARKET_CURRENCY_PLUS',
            'YA_MARKET_CATEGORY_ALL_ENABLED',
            'YA_MARKET_CATEGORY_LIST',
            'YA_MARKET_DELIVERY_ENABLED',
            'YA_MARKET_DELIVERY_COST',
            'YA_MARKET_DELIVERY_DAYS_FROM',
            'YA_MARKET_DELIVERY_DAYS_TO',
            'YA_MARKET_DELIVERY_ORDER_BEFORE',
            'YA_MARKET_OFFER_TYPE_SIMPLE',
            'YA_MARKET_OFFER_TYPE_NAME_TEMPLATE',
            'YA_MARKET_AVAILABLE_ENABLED',
            'YA_MARKET_AVAILABLE_AVAILABLE',
            'YA_MARKET_AVAILABLE_DELIVERY',
            'YA_MARKET_AVAILABLE_PICKUP',
            'YA_MARKET_AVAILABLE_STORE',
            'YA_MARKET_VAT_ENABLED',
            'YA_MARKET_VAT_LIST',
            'YA_MARKET_COMBINATION_EXPORT_ALL',
            'YA_MARKET_OFFER_OPTIONS_EXPORT_PARAMS',
            'YA_MARKET_OFFER_OPTIONS_EXPORT_DIMENSION',
            'YA_MARKET_ADDITIONAL_CONDITION_ENABLED',
            'YA_MARKET_ADDITIONAL_CONDITION_NAME',
            'YA_MARKET_ADDITIONAL_CONDITION_TAG',
            'YA_MARKET_ADDITIONAL_CONDITION_TYPE_VALUE',
            'YA_MARKET_ADDITIONAL_CONDITION_STATIC_VALUE',
            'YA_MARKET_ADDITIONAL_CONDITION_DATA_VALUE',
            'YA_MARKET_ADDITIONAL_CONDITION_FOR_ALL_CAT',
            'YA_MARKET_ADDITIONAL_CONDITION_JOIN',
            'YA_MARKET_ADDITIONAL_CONDITION_CATEGORIES',
        ));
    }

    /**
     * @param bool $cron
     * @throws \Exception
     */
    public function generateXML($cron)
    {
        $market        = new YandexMarket();
        $market->setShop(
            $this->getConfig('YA_MARKET_SHOP_NAME'),
            $this->getConfig('YA_MARKET_FULL_SHOP_NAME'),
            'http://' . Tools::getHttpHost(false, true) . __PS_BASE_URI__
        );
        $shop = $market->getShop();

        $shop->setPlatform('prestashop');
        $shop->setVersion(_PS_VERSION_);

        $this->exportDelivery($shop);
        $this->exportCurrency($market);
        $this->exportCategories($market);

        foreach ($shop->getCategories() as $category) {
            $this->exportOffers($market, $category->getId());
        }

        $xml = $market->getXml($this->getConfig('YA_MARKET_OFFER_TYPE_SIMPLE'));

        if ($cron) {
            if ($fp = fopen(_PS_UPLOAD_DIR_.'yml.'.$this->context->shop->id.'.xml', 'w')) {
                fwrite($fp, $xml);
                fclose($fp);
                $this->log('info', 'market_generate: Cron '.$this->module->l('Generate price'));
            }
        } else {
            header('Content-type:application/xml; charset=utf-8');
            $this->module->log('info', 'market_generate: view '.$this->module->l('Generate price'));
            echo $xml;
            exit();
        }
    }

    /**
     * @param ShopInfo $shop
     */
    private function exportDelivery(ShopInfo $shop){
        for ($index = 1; $index <= 5; $index++) {
            $enabled = $this->getConfig('YA_MARKET_DELIVERY_ENABLED', $index);
            if (!$enabled) {
                continue;
            }
            $cost = $this->getConfig('YA_MARKET_DELIVERY_COST', $index);
            if ($cost === '') {
                continue;
            }
            $daysFrom = $this->getConfig('YA_MARKET_DELIVERY_DAYS_FROM', $index);
            $daysTo   = $this->getConfig('YA_MARKET_DELIVERY_DAYS_TO', $index);
            $days     = empty($daysTo) || $daysFrom === $daysTo ? $daysFrom : $daysFrom.'-'.$daysTo;
            if ($days === '') {
                continue;
            }
            $orderBefore = $this->getConfig('YA_MARKET_DELIVERY_ORDER_BEFORE', $index);
            $shop->addDeliveryOption($cost, $days, $orderBefore);
        }
    }

    /**
     * @param YandexMarket $market
     */
    private function exportCurrency(YandexMarket $market)
    {
        $currencies = Currency::getCurrencies();
        $defaultCurrency = Currency::getDefaultCurrency();

        foreach ($currencies as $currency) {
            if (!in_array($currency['iso_code'], \YandexMoneyModule\YandexMarket\Currency::getAvailableCurrencies())) {
                continue;
            }
            $enabled = $this->getConfig('YA_MARKET_CURRENCY_ENABLED', $currency['iso_code']);
            if (!$enabled) {
                continue;
            }
            if ($currency['iso_code'] === $defaultCurrency->iso_code) {
                $rate = '1';
                $plus = null;
            } else {
                $rate = $this->getConfig('YA_MARKET_CURRENCY_RATE', $currency['iso_code']);
                if ($rate === '1') {
                    continue;
                }
                $plus = (float)$this->getConfig('YA_MARKET_CURRENCY_PLUS', $currency['iso_code'], 0.0);
                if ($rate === '__cms') {
                    $rate = (float)$currency['conversion_rate'];
                }
            }
            $market->addCurrency($currency['iso_code'], $rate, $plus);
        }
    }

    /**
     * @param YandexMarket $market
     */
    private function exportCategories(YandexMarket $market)
    {
        $languageId   = (int)Configuration::get('PS_LANG_DEFAULT');
        $exportAll    = $this->getConfig('YA_MARKET_CATEGORY_ALL_ENABLED');
        $categoryList = (array)$this->getConfig('YA_MARKET_CATEGORY_LIST');

        foreach (Category::getCategories($languageId, false, false) as $category) {
            if ($category['id_category'] == 1) {
                continue;
            }
            if ($exportAll || in_array($category['id_category'], $categoryList)) {
                $market->addCategory($category['name'], $category['id_category'], $category['id_parent']);
            }
        }
    }

    /**
     * @param YandexMarket $market
     * @param $categoryId
     * @throws \Exception
     */
    private function exportOffers(YandexMarket $market, $categoryId)
    {
        $isSimpleType = $this->getConfig('YA_MARKET_OFFER_TYPE_SIMPLE');
        $nameTemplate = explode('%', $this->getConfig('YA_MARKET_OFFER_TYPE_NAME_TEMPLATE'));
        $isExportParams = $this->getConfig('YA_MARKET_OFFER_OPTIONS_EXPORT_PARAMS');

        $langId = (int)Configuration::get('PS_LANG_DEFAULT');
        $category = new Category($categoryId);
        $products = $category->getProducts($langId, 1, 10000);
        if (empty($products)) {
            return;
        }
        foreach ($products as $product) {
            if ($product['id_category_default'] != $categoryId) {
                continue;
            }
            $this->exportOffer($market, $product, $langId, $isSimpleType, $isExportParams, $nameTemplate);
        }
    }

    /**
     * @param YandexMarket $market
     * @param $product
     * @param $langId
     * @param $isSimpleType
     * @param $isExportParams
     * @param $nameTemplate
     * @throws \Exception
     */
    private function exportOffer($market, $product, $langId, $isSimpleType, $isExportParams, $nameTemplate)
    {
        $statusId  = $product['quantity'] > 0 ? 'non-zero-quantity' : 'zero-quantity';
        $useStatus = $this->getConfig('YA_MARKET_AVAILABLE_ENABLED', $statusId);
        $available = $this->getConfig('YA_MARKET_AVAILABLE_AVAILABLE', $statusId);
        if ($useStatus && $available === 'none') {
            return;
        }

        $offer = $market->createOffer($product['id_product'], $product['id_category_default']);
        if (!$offer) {
            return;
        }

        $offer
            ->setUrl($product['link'])
            ->setModel($product['name'])
            ->setVendor($product['manufacturer_name'])
            ->setDescription($product['description'] ?: $product['description_short'])
            ->setPrice(Tools::ps_round($product['price'], 2));

        $this->exportOfferAvailable($offer, $statusId, $useStatus, $available);
        $this->exportOfferVat($offer, $product);
        $this->exportOfferName($offer, $product, $isSimpleType, $nameTemplate);
        $this->exportOfferParams($offer, $product, $isExportParams);
        $this->exportOfferAdditionalCondition($offer, $product, $market->getShop()->getCategories());
        $this->exportOfferDimensions($offer, $product);

        if (!$this->makeOfferCombination($offer, $product, $market, $langId)) {
            $this->exportOfferPictures($offer, $product, $langId);
            $market->addOffer($offer);
        }
    }

    /**
     * @param Offer $offer
     * @param $statusId
     * @param $useStatus
     * @param $available
     */
    private function exportOfferAvailable($offer, $statusId, $useStatus, $available)
    {
        if (!$useStatus) {
            return;
        }
        $offer
            ->setAvailable($available === 'true')
            ->setDelivery((bool)$this->getConfig('YA_MARKET_AVAILABLE_DELIVERY', $statusId))
            ->setPickup((bool)$this->getConfig('YA_MARKET_AVAILABLE_PICKUP', $statusId))
            ->setStore((bool)$this->getConfig('YA_MARKET_AVAILABLE_STORE', $statusId));
    }

    /**
     * @param Offer $offer
     * @param $product
     * @param $langId
     */
    private function exportOfferPictures($offer, $product, $langId)
    {
        $link = Context::getContext()->link->getImageLink($product['id_image'], $product['id_image']);
        if ($link) {
            $offer->addPicture(Context::getContext()->link->getImageLink($product['link_rewrite'],
                $product['id_image']));
        }

        $images = Image::getImages($langId, $product['id_product']);
        if (!$images) {
            return;
        }
        foreach ($images as $image) {
            $offer->addPicture(Context::getContext()->link
                ->getImageLink($product['link_rewrite'], $image['id_image']));
        }
    }

    /**
     * @param Offer $offer
     * @param $product
     */
    private function exportOfferVat($offer, $product)
    {
        if (!$this->getConfig('YA_MARKET_VAT_ENABLED')) {
            return;
        }
        $vatRates = $this->getConfig('YA_MARKET_VAT_LIST', $product['id_tax_rules_group']);
        if ($vatRates) {
            $offer->setVat($vatRates);
        }
    }

    /**
     * @param Offer $offer
     * @param $product
     * @param $isSimpleType
     * @param $nameTemplate
     */
    private function exportOfferName($offer, $product, $isSimpleType, $nameTemplate)
    {
        if (!$isSimpleType) {
            return;
        }
        $name = '';
        foreach ($nameTemplate as $namePart) {
            $name .= isset($product[$namePart]) ? $product[$namePart] : $namePart;
        }
        $offer->setName($name);
    }

    /**
     * @param Offer $offer
     * @param $product
     * @param $isExportParams
     */
    private function exportOfferParams($offer, $product, $isExportParams)
    {
        if (!$isExportParams) {
            return;
        }
        $attributes = $product['features'];
        foreach ($attributes as $attribute) {
            $offer->addParameter($attribute['name'], $attribute['value']);
        }
    }

    /**
     * @param Offer $offer
     * @param $product
     * @param $categories
     */
    private function exportOfferAdditionalCondition(Offer $offer, $product, $categories)
    {
        $additionalConditionMap = $this->getAdditionalConditionCategoryMap($categories);
        $allCategories = Product::getProductCategories($product['id_product']);
        foreach ($allCategories as $category) {
            if (!isset($additionalConditionMap[$category])) {
                continue;
            }
            foreach ($additionalConditionMap[$category] as $conditionId) {
                $tag       = $this->getConfig('YA_MARKET_ADDITIONAL_CONDITION_TAG', $conditionId);
                $typeValue = $this->getConfig('YA_MARKET_ADDITIONAL_CONDITION_TYPE_VALUE', $conditionId);
                if ($typeValue === 'static') {
                    $value = $this->getConfig('YA_MARKET_ADDITIONAL_CONDITION_STATIC_VALUE', $conditionId);
                } else {
                    $dataValue = $this->getConfig('YA_MARKET_ADDITIONAL_CONDITION_DATA_VALUE', $conditionId);
                    $value     = isset($product[$dataValue]) ? $product[$dataValue] : '';
                }
                $join = $this->getConfig('YA_MARKET_ADDITIONAL_CONDITION_JOIN', $conditionId);

                if (!empty($tag) && $value !== '') {
                    $offer->addCustomTag($tag, $value, $join);
                }
            }
        }
    }

    /**
     * @param ProductCategory[] $categories
     * @return array
     */
    private function getAdditionalConditionCategoryMap($categories)
    {
        if (is_null($this->additionalConditionMap)) {
            $allCategoryIds = array_map(function (ProductCategory $category) {
                return $category->getId();
            }, $categories);

            $additionalConditionIds     = array();
            $additionalConditionMap     = array();
            $additionalConditionEnabled = (array)$this->getConfig('YA_MARKET_ADDITIONAL_CONDITION_ENABLED');
            foreach ($additionalConditionEnabled as $id => $enabled) {
                if ($enabled) {
                    $additionalConditionIds[] = $id;
                }
            }
            if (!empty($additionalConditionIds)) {
                foreach ($additionalConditionIds as $conditionId) {
                    $additionalConditionCategoryIds
                        = $this->getConfig('YA_MARKET_ADDITIONAL_CONDITION_FOR_ALL_CAT', $conditionId)
                        ? $allCategoryIds
                        : $this->getConfig('YA_MARKET_ADDITIONAL_CONDITION_CATEGORIES', $conditionId, array());
                    foreach ($additionalConditionCategoryIds as $category) {
                        $additionalConditionMap[$category][] = $conditionId;
                    }
                }
            }
            $this->additionalConditionMap = $additionalConditionMap;
        }

        return $this->additionalConditionMap;
    }

    /**
     * @param Offer $offer
     * @param $product
     */
    private function exportOfferDimensions($offer, $product)
    {
        if (!$this->getConfig('YA_MARKET_OFFER_OPTIONS_EXPORT_DIMENSION')) {
            return;
        }
        if ($product['height'] > 0 && $product['depth'] > 0 && $product['width'] > 0) {
            $offer->setDimensions(number_format($product['depth'], 3, '.', ''),
                number_format($product['width'], 3, '.', ''),
                number_format($product['height'], 3, '.', '')
            );
        }
    }

    /**
     * @param Offer $commonOffer
     * @param $product
     * @param YandexMarket $market
     * @param $langId
     * @return bool
     */
    private function makeOfferCombination($commonOffer, $product, $market, $langId)
    {
        if (!$this->getConfig('YA_MARKET_COMBINATION_EXPORT_ALL')) {
            return false;
        }

        $productInstance = new Product($product['id_product'], false, $langId);
        $combinations = $productInstance->getAttributeCombinations($langId);

        if (count($combinations) == 0) {
            return false;
        }

        $exports = array();
        foreach ($combinations as $combination) {
            $attrId = $combination['id_product_attribute'];

            $exports[$attrId]['id_product_attribute'] = $attrId;

            $exports[$attrId]['price'] = Product::getPriceStatic($product['id_product'], true, $attrId);

            $exports[$attrId]['attributes'][$combination['group_name']] = $combination['attribute_name'];

            if (!isset($exports[$attrId]['url'])) {
                $exports[$attrId]['url'] = '';
            }
            $exports[$attrId]['url'] .= '/'
                .Tools::str2url($combination['id_attribute'].'-'.$combination['group_name']).'-'
                .str_replace(Configuration::get('PS_ATTRIBUTE_ANCHOR_SEPARATOR'), '_',
                    Tools::str2url(str_replace(array(',', '.'), '-', $combination['attribute_name'])));
        }

        foreach ($exports as $combination) {
            $offer = clone $commonOffer;
            $offer->setId($product['id_product'].'c'.$combination['id_product_attribute']);
            $offer->setGroupId($product['id_product']);
            $offer->setPrice(Tools::ps_round($combination['price'], 2));
            $offer->setUrl($offer->getUrl().'#'.$combination['url']);
            foreach ($combination['attributes'] as $name => $value) {
                $offer->addParameter($name, $value);
            }

            $images = Image::getImages($langId, $product['id_product'], $combination['id_product_attribute']);
            if (empty($images)) {
                $images = Image::getImages($langId, $product['id_product']);
            }
            if (!empty($images)) {
                foreach ($images as $image) {
                    $url = Context::getContext()->link->getImageLink($product['link_rewrite'], $image['id_image']);
                    $offer->addPicture($url);
                }
            }

            $market->addOffer($offer);
        }

        return true;
    }

    /**
     * @param $key
     * @param $index
     * @param null $default
     * @return mixed
     */
    protected function getConfig($key, $index = null, $default = null)
    {
        return ym_get_settings($this->settings, $key, $index, $default);
    }
}
