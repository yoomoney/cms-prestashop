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
use YandexMoneyModule\Yml;

class MarketModel extends AbstractModel
{
    public function validateOptions()
    {
        $errors = '';
        Configuration::UpdateValue('YA_MARKET_SHORT', Tools::getValue('YA_MARKET_SHORT'));
        Configuration::UpdateValue('YA_MARKET_SET_ALLCURRENCY', Tools::getValue('YA_MARKET_SET_ALLCURRENCY'));
        Configuration::UpdateValue('YA_MARKET_DESC_TYPE', Tools::getValue('YA_MARKET_DESC_TYPE'));
        Configuration::UpdateValue('YA_MARKET_DOSTUPNOST', Tools::getValue('YA_MARKET_DOSTUPNOST'));
        Configuration::UpdateValue('YA_MARKET_SET_GZIP', Tools::getValue('YA_MARKET_SET_GZIP'));
        Configuration::UpdateValue('YA_MARKET_SET_AVAILABLE', Tools::getValue('YA_MARKET_SET_AVAILABLE'));
        Configuration::UpdateValue('YA_MARKET_SET_NACTIVECAT', Tools::getValue('YA_MARKET_SET_NACTIVECAT'));
        //Configuration::UpdateValue('YA_MARKET_SET_HOMECARRIER', Tools::getValue('YA_MARKET_SET_HOMECARRIER'));
        Configuration::UpdateValue('YA_MARKET_SET_COMBINATIONS', Tools::getValue('YA_MARKET_SET_COMBINATIONS'));
        Configuration::UpdateValue('YA_MARKET_SET_DIMENSIONS', Tools::getValue('YA_MARKET_SET_DIMENSIONS'));
        Configuration::UpdateValue('YA_MARKET_SET_SAMOVIVOZ', Tools::getValue('YA_MARKET_SET_SAMOVIVOZ'));
        Configuration::UpdateValue('YA_MARKET_SET_DOST', Tools::getValue('YA_MARKET_SET_DOST'));
        Configuration::UpdateValue('YA_MARKET_SET_ROZNICA', Tools::getValue('YA_MARKET_SET_ROZNICA'));
        Configuration::UpdateValue('YA_MARKET_MK', Tools::getValue('YA_MARKET_MK'));
        Configuration::UpdateValue('YA_MARKET_HKP', Tools::getValue('YA_MARKET_HKP'));
        Configuration::UpdateValue('YA_MARKET_CATEGORIES', serialize(Tools::getValue('YA_MARKET_CATEGORIES')));

        if (Tools::getValue('YA_MARKET_NAME') == '') {
            $errors .= $this->module->displayError($this->module->l('The company name is not filled in!'));
        } else {
            Configuration::UpdateValue('YA_MARKET_NAME', Tools::getValue('YA_MARKET_NAME'));
        }

        if (Tools::getValue('YA_MARKET_DELIVERY') == '') {
            $errors .= $this->module->displayError(
                $this->module->l('The shipping cost to your home location is not filled in!')
            );
        } else {
            Configuration::UpdateValue('YA_MARKET_DELIVERY', Tools::getValue('YA_MARKET_DELIVERY'));
        }

        if ($errors == '') {
            $errors = $this->module->displayConfirmation($this->module->l('Settings saved successfully!'));
        }

        return $errors;
    }

    public function initConfiguration()
    {
    }

    public function generateXML($cron)
    {
        $this->yamarket_availability = Configuration::get('YA_MARKET_DOSTUPNOST');
        $this->gzip = Configuration::get('YA_MARKET_SET_GZIP');

        /*-----------------------------------------------------------------------------*/

        $yml = $this->initYml();
        $this->loadYmlCategories($yml);
        foreach ($yml->categories as $categoryInfo) {
            $this->loadYmlProducts($yml, $categoryInfo['id']);
        }

        $xml = $yml->getXml();
        if ($cron) {
            if ($fp = fopen(_PS_UPLOAD_DIR_.'yml.'.$this->context->shop->id.'.xml'.($this->gzip ? '.gz' : ''), 'w')) {
                fwrite($fp, $xml);
                fclose($fp);
                $this->log('info', 'market_generate: Cron '.$this->module->l('Generate price'));
            }
        } else {
            if ($this->gzip) {
                header('Content-type:application/x-gzip');
                header('Content-Disposition: attachment; filename=yml.'.$this->context->shop->id.'.xml.gz');
                $this->log('info', 'market_generate: gzip view '.$this->module->l('Generate price'));
            } else {
                header('Content-type:application/xml; charset=windows-1251');
            }
            $this->module->log('info', 'market_generate: view '.$this->module->l('Generate price'));
            echo $xml;
            exit;
        }
    }

    /**
     * @return \YandexMoneyModule\Yml
     */
    private function initYml()
    {
        $yml = new \YandexMoneyModule\Yml();
        $yml->yml('utf-8');
        $yml->setShop(
            Configuration::get('PS_SHOP_NAME'),
            Configuration::get('YA_MARKET_NAME'),
            'http://' . Tools::getHttpHost(false, true) . __PS_BASE_URI__
        );
        $defaultCurrency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
        if (Configuration::get('YA_MARKET_SET_ALLCURRENCY')) {
            foreach (Currency::getCurrencies() as $currency) {
                $yml->addCurrency(
                    $currency['iso_code'],
                    ((float)$defaultCurrency->conversion_rate / (float)$currency['conversion_rate'])
                );
            }
        } else {
            $yml->addCurrency($defaultCurrency->iso_code, (float)$defaultCurrency->conversion_rate);
        }
        return $yml;
    }

    private function loadYmlCategories(Yml $yml)
    {
        $languageId = (int)Configuration::get('PS_LANG_DEFAULT');
        $activeOnly = Configuration::get('YA_MARKET_SET_NACTIVECAT') ? true : false;
        $allCategories = Configuration::get('YA_MARKET_CATALL');

        $categoryIdList = array();
        if ($c = Configuration::get('YA_MARKET_CATEGORIES')) {
            $uc = unserialize($c);
            if (is_array($uc)) {
                foreach ($uc as $categoryId) {
                    $categoryIdList[$categoryId] = $categoryId;
                }
            }
        }

        foreach (Category::getCategories($languageId, false, false) as $category) {
            if ($category['id_category'] == 1) {
                continue;
            }
            if ($activeOnly && !$category['active']) {
                continue;
            }
            if ($allCategories || array_key_exists($category['id_category'], $categoryIdList)) {
                $yml->addCategory($category['name'], $category['id_category'], $category['id_parent']);
            }
        }
    }

    private function loadYmlProducts(Yml $yml, $categoryId)
    {
        $languageId = (int)Configuration::get('PS_LANG_DEFAULT');
        $yamarket_set_combinations = Configuration::get('YA_MARKET_SET_COMBINATIONS');
        $category = new Category($categoryId);
        $products = $category->getProducts($languageId, 1, 10000);
        if (!empty($products)) {
            foreach ($products as $productInfo) {
                if ($productInfo['id_category_default'] != $categoryId) {
                    continue;
                }

                $data = array();
                if ($yamarket_set_combinations && !Configuration::get('YA_MARKET_SHORT')) {
                    $product = new Product($productInfo['id_product'], false, $languageId);
                    $combinations = $product->getAttributeCombinations($languageId);
                } else {
                    $combinations = false;
                }

                if (is_array($combinations) && count($combinations) > 0) {
                    $comb_array = array();
                    foreach ($combinations as $combination) {
                        $comb_array[$combination['id_product_attribute']]['id_product_attribute']
                            = $combination['id_product_attribute'];
                        $comb_array[$combination['id_product_attribute']]['price'] = Product::getPriceStatic(
                            $productInfo['id_product'],
                            true,
                            $combination['id_product_attribute']
                        );

                        $comb_array[$combination['id_product_attribute']]['reference'] = $combination['reference'];
                        $comb_array[$combination['id_product_attribute']]['ean13'] = $combination['ean13'];
                        $comb_array[$combination['id_product_attribute']]['quantity'] = $combination['quantity'];
                        $comb_array[$combination['id_product_attribute']]['minimal_quantity']
                            = $combination['minimal_quantity'];
                        $comb_array[$combination['id_product_attribute']]['weight'] = $combination['weight'];
                        $comb_array[$combination['id_product_attribute']]['attributes'][$combination['group_name']]
                            = $combination['attribute_name'];
                        if (!isset($comb_array[$combination['id_product_attribute']]['comb_url'])) {
                            $comb_array[$combination['id_product_attribute']]['comb_url'] = '';
                        }
                        $comb_array[$combination['id_product_attribute']]['comb_url'] .= '/'.
                            Tools::str2url(
                                $combination['id_attribute']."-".$combination['group_name']
                            ).'-'.str_replace(
                                Configuration::get('PS_ATTRIBUTE_ANCHOR_SEPARATOR'),
                                '_',
                                Tools::str2url(
                                    str_replace(
                                        array(',', '.'),
                                        '-',
                                        $combination['attribute_name']
                                    )
                                )
                            );
                    }

                    foreach ($comb_array as $combination) {
                        $data = $this->makeData($product, $combination);
                        $available = $data['available'];
                        unset($data['available']);
                        if (!empty($data) && $data['price'] != 0) {
                            $yml->addOffer($data['id'], $data, $available, $data['group_id']);
                        }
                    }
                } else {
                    $data = $this->makeData($productInfo);
                    $available = $data['available'];
                    unset($data['available']);
                    if (!empty($data) && (int)$data['price'] != 0) {
                        $yml->addOffer($data['id'], $data, $available);
                    }
                }
                unset($data);
            }
        }
    }

    private function makeData($product, $combination = false)
    {
        $params = array();
        $data = array();
        $images = array();
        $id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        if ($combination) {
            $quantity = (int)$combination['quantity'];
            $url = $product['link'].'#'.$combination['comb_url'];
            $price =  Tools::ps_round($combination['price'], 2);
            $reference = $combination['reference'];
            $id_offer = $product['id_product'].'c'.$combination['id_product_attribute'];
            $barcode = $combination['ean13'];
            $images = Image::getImages($id_lang, $product['id_product'], $combination['id_product_attribute']);
            if (empty($images)) {
                $images = Image::getImages($id_lang, $product['id_product']);
            }

            if ((int)$combination['weight'] > 0) {
                $data['weight'] = $combination['weight'];
                $data['weight'] = number_format($data['weight'], 2);
            } else {
                $data['weight'] = $product['weight'];
                $data['weight'] = number_format($data['weight'], 2);
            }

            if ($combination['minimal_quantity'] > 1) {
                $data['sales_notes'] = $this->module->l('Minimum order').' '.$combination['minimal_quantity'].' '.
                    $this->module->l('of the product (s)');
            }
            $data['group_id'] = $product['id_product'];
        } else {
            $quantity = (int)$product['quantity'];
            $url = $product['link'];
            $price =  Tools::ps_round($product['price'], 2);
            $reference = $product['reference'];
            $id_offer = $product['id_product'];
            $barcode = $product['ean13'];
            $images = Image::getImages($id_lang, $product['id_product']);
            if ((int)$product['weight'] > 0) {
                $data['weight'] = $product['weight'];
                $data['weight'] = number_format($data['weight'], 2);
            }

            if ($product['minimal_quantity'] > 1) {
                $data['sales_notes'] = $this->module->l('Minimum order').' '.$product['minimal_quantity'].' '.
                    $this->module->l('of the product (s)');
            }
        }

        if (Configuration::get('YA_MARKET_SET_AVAILABLE')) {
            if ($quantity < 1) {
                return;
            }
        }

        $available = 'false';
        if ($this->yamarket_availability == 0) {
            $available = 'true';
        } elseif ($this->yamarket_availability == 1) {
            if ($quantity > 0) {
                $available = 'true';
            }
        } elseif ($this->yamarket_availability == 2) {
            $available = 'true';
            if ($quantity == 0) {
                return;
            }
        }


        if ($product['features']) {
            foreach ($product['features'] as $feature) {
                $params[$feature['name']] = $feature['value'];
            }
        }
        if ($combination) {
            $params = array_merge($params, $combination['attributes']);
        }

        $defaultCurrency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
        $data['available'] = $available;
        $data['url'] = str_replace('https://', 'http://', $url);
        $data['id'] = $id_offer;
        $data['currencyId'] = $defaultCurrency->iso_code;
        $data['price'] = $price;
        $data['categoryId'] = $product['id_category_default'];

        /*-------------------------------------------------------------------*/
        preg_match_all('/([а-яё]+)/iu', $data['url'], $urlarr, PREG_SET_ORDER);
        if (!empty($urlarr)) {
            foreach ($urlarr as $ua) {
                $data['url'] = str_replace($ua[0], rawurlencode($ua[0]), $data['url']);
            }
        }
        /*-------------------------------------------------------------------*/
        foreach ($images as $i) {
            $uri = Context::getContext()->link->getImageLink($product['link_rewrite'], $i['id_image']);
            preg_match_all('/([а-яё]+)/iu', $uri, $marr, PREG_SET_ORDER);
            if (!empty($marr)) {
                foreach ($marr as $m) {
                    $uri = str_replace($m[0], rawurlencode($m[0]), $uri);
                }
            }

            $data['picture'][] = $uri;
        }

        if (!Configuration::get('YA_MARKET_SHORT')) {
            $data['model'] = $product['name'];
            if (Configuration::get('YA_MARKET_SET_DIMENSIONS')
                && $product['height'] > 0
                && $product['depth'] > 0
                && $product['width']
            ) {
                $data['dimensions'] = number_format($product['depth'], 3, '.', '').
                    '/'.number_format($product['width'], 3, '.', '')
                    .'/'.number_format($product['height'], 3, '.', '');
            }
            if ($product['is_virtual']) {
                $data['downloadable'] = 'true';
            } else {
                $data['downloadable'] = 'false';
            }
            if (Configuration::get('YA_MARKET_DESC_TYPE')) {
                $data['description'] = $product['description_short'];
            } else {
                $data['description'] = $product['description'];
            }
            $data['param'] = $params;
        } else {
            $data['name'] = $product['name'];
        }

        $data['vendor'] = $product['manufacturer_name'];
        $data['barcode'] = $barcode;
        $data['delivery'] = 'false';
        $data['pickup'] = 'false';
        $data['store'] = 'false';
        $data['vendorCode'] = $reference;
        if (Configuration::get('YA_MARKET_SET_DOST')) {
            $data['delivery'] = 'true';
        }
        if (Configuration::get('YA_MARKET_SET_SAMOVIVOZ')) {
            $data['pickup'] = 'true';
        }
        if (Configuration::get('YA_MARKET_SET_ROZNICA')) {
            $data['store'] = 'true';
        }

        return $data;
    }
}
