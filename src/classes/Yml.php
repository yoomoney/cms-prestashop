<?php
/**
 * Module is prohibited to sales! Violation of this condition leads to the deprivation of the license!
 *
 * @author    Yandex.Money <cms@yamoney.ru>
 * @copyright © 2015 NBCO Yandex.Money LLC
 * @license   https://money.yandex.ru/doc.xml?id=527052
 *
 * @category  Front Office Features
 * @package   Yandex Payment Solution
 */

namespace YandexMoneyModule;

use Configuration;
use Tools;

class Yml
{
    public $from_charset = 'windows-1251';
    public $shop = array('name' => '', 'company' => '', 'url' => '', 'platform' => 'ya_prestashop');
    public $currencies = array();
    public $categories = array();
    public $offers = array();

    public function yml($from_charset = 'windows-1251')
    {
        $this->from_charset = trim(Tools::strtolower($from_charset));
    }
    
    public function convertArrayToTag($arr)
    {
        $s = '';
        foreach ($arr as $tag => $val) {
            if ($tag == 'weight' && (int)$val == 0) {
                continue;
            }

            if ($tag == 'picture') {
                foreach ($val as $v) {
                    $s .= '<'.$tag.'>'.$this->prepareField($v).'</'.$tag.'>';
                    $s .= "\r\n";
                }
            } elseif ($tag == 'param') {
                foreach ($val as $k => $v) {
                    $s .= '<param name="'.$this->prepareField($k).'">'.$this->prepareField($v).'</param>';
                    $s .= "\r\n";
                }
            } else {
                $s .= '<'.$tag.'>'.$val.'</'.$tag.'>';
                $s .= "\r\n";
            }
        }

        return $s;
    }

    public function convertArrayToAttr($arr, $tagname, $tagvalue = '')
    {
        $s = '<'.$tagname.' ';
        foreach ($arr as $attrname => $attrval) {
            $s .= $attrname . '="'.$attrval.'" ';
        }

        $s .= ($tagvalue!='') ? '>'.$tagvalue.'</'.$tagname.'>' : '/>';
        $s .= "\r\n";
        return $s;
    }

    public function prepareField($s)
    {
        $from = array('"', '&', '>', '<', '\'');
        $to = array('&quot;', '&amp;', '&gt;', '&lt;', '&apos;');
        $s = str_replace($from, $to, $s);
        if ($this->from_charset!='windows-1251') {
            $s = iconv($this->from_charset, 'windows-1251//IGNORE//TRANSLIT', $s);
        }
        $s = preg_replace('#[\x00-\x08\x0B-\x0C\x0E-\x1F]+#is', ' ', $s);
        return trim($s);
    }

    public function setShop($name, $company, $url)
    {
        $this->shop['name'] = $this->prepareField($name);
        $this->shop['name'] = Tools::substr($this->shop['name'], 0, 20);
        $this->shop['company'] = $this->prepareField($company);
        $this->shop['url'] = $this->prepareField($url);
    }

    public function addCurrency($id, $rate = 'CBRF', $plus = 0)
    {
        $rate = Tools::strtoupper($rate);
        $plus = str_replace(',', '.', $plus);
        if ($rate=='CBRF' && $plus>0) {
            $this->currencies[] = array(
                'id'=>$this->prepareField(Tools::strtoupper($id)),
                'rate'=>'CBRF',
                'plus'=>(float)$plus
            );
        } else {
            $rate = str_replace(',', '.', $rate);
            $this->currencies[] = array(
                'id'=>$this->prepareField(Tools::strtoupper($id)),
                'rate'=>(float)$rate
            );
        }
        return true;
    }

    public function addCategory($name, $id, $parent_id = -1)
    {
        if ((int)$id<1||trim($name)=='') {
            return false;
        }
        if ((int)$parent_id>0) {
            $this->categories[] = array(
                'id'=>(int)$id,
                'parentId'=>(int)$parent_id,
                'name'=>$this->prepareField($name)
            );
        } else {
            $this->categories[] = array(
                'id'=>(int)$id,
                'name'=>$this->prepareField($name)
            );
        }
        return true;
    }

    public function addOffer($id, $data, $available = true, $group_id = 0)
    {
        $allowed = array(
            'url',
            'price',
            'currencyId',
            'categoryId',
            'picture',
            'store',
            'pickup',
            'delivery',
            'name',
            'vendor',
            'vendorCode',
            'model',
            'description',
            'sales_notes',
            'downloadable',
            'weight',
            'dimensions',
            'param',
            'barcode',
            'sales_notes',
            'country_of_origin'
        );

        foreach ($data as $k => $v) {
            if (!in_array($k, $allowed)) {
                unset($data[$k]);
            }
            if ($k != 'picture' && $k != 'param') {
                $data[$k] = strip_tags($this->prepareField($v));
            }
        }
        $tmp = $data;
        $data = array();
        foreach ($allowed as $key) {
            if (isset($tmp[$key]) && !empty($tmp[$key])) {
                $data[$key] = $tmp[$key];
            }
        }
        // Порядок важен для Я.Маркета!!!

        $out = array('id' => $id, 'data' => $data, 'available' => ($available) ? 'true' : 'false');
        if ($group_id > 0) {
            $out['group_id'] = $group_id;
        }
        if (!Configuration::get('YA_MARKET_SHORT')) {
            $out['type'] = 'vendor.model';
        }
        $this->offers[] = $out;
    }

    public function getXmlHeader()
    {
        return '<?xml version="1.0" encoding="windows-1251"?>'
            .'<yml_catalog date="'.date('Y-m-d H:i').'">';
    }

    public function getXmlShop()
    {
        $s = '<shop>' . "\r\n";
        $s .= $this->convertArrayToTag($this->shop);
        $s .= '<currencies>' . "\r\n";
        foreach ($this->currencies as $currency) {
            $s .= $this->convertArrayToAttr($currency, 'currency');
        }

        $s .= '</currencies>' . "\r\n";
        $s .= '<categories>' . "\r\n";
        foreach ($this->categories as $category) {
            $category_name = $category['name'];
            unset($category['name']);
            $s .= $this->convertArrayToAttr($category, 'category', $category_name);
        }
        $s .= '</categories>' . "\r\n";
        if (Configuration::get('YA_MARKET_DELIVERY', 0) == 0) {
            $s .= '<local_delivery_cost>'.Configuration::get('YA_MARKET_DELIVERY').'</local_delivery_cost>' . "\r\n";
        }

        $s .= '<offers>' . "\r\n";
        foreach ($this->offers as $offer) {
            $data = $offer['data'];
            unset($offer['data']);
            $s .= $this->convertArrayToAttr($offer, 'offer', $this->convertArrayToTag($data));
        }
        $s .= '</offers>' . "\r\n";
        $s .= '</shop>';
        return $s;
    }

    public function getXmlFooter()
    {
        return '</yml_catalog>';
    }

    public function getXml()
    {
        $xml = $this->getXmlHeader();
        $xml .= $this->getXmlShop();
        $xml .= $this->getXmlFooter();
        return $xml;
    }
}
