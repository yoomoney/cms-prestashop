<?php
/**
 * @author Yandex.Money <cms@yamoney.ru>
 * @copyright © 2015-2017 NBCO Yandex.Money LLC
 * @license  https://money.yandex.ru/doc.xml?id=527052
 */

namespace YandexMoneyModule\Models;

use Carrier;
use Configuration;
use Context;
use Tools;
use YandexMoneyModule\Partner;

class OrderModel extends AbstractModel
{
    public function validateOptions()
    {
        $array_c = array();
        $errors = '';
        foreach ($_POST as $k => $post) {
            if (strpos($k, 'YA_MARKET_ORDERS_DELIVERY_') !== false) {
                $id = str_replace('YA_MARKET_ORDERS_DELIVERY_', '', $k);
                $array_c[$id] = $post;
            }
        }

        Configuration::UpdateValue('YA_MARKET_ORDERS_CARRIER_SERIALIZE', serialize($array_c));
        Configuration::UpdateValue(
            'YA_MARKET_ORDERS_PREDOPLATA_YANDEX',
            Tools::getValue('YA_MARKET_ORDERS_PREDOPLATA_YANDEX')
        );
        Configuration::UpdateValue(
            'YA_MARKET_ORDERS_PREDOPLATA_SHOP_PREPAID',
            Tools::getValue('YA_MARKET_ORDERS_PREDOPLATA_SHOP_PREPAID')
        );
        Configuration::UpdateValue(
            'YA_MARKET_ORDERS_POSTOPLATA_CASH_ON_DELIVERY',
            Tools::getValue('YA_MARKET_ORDERS_POSTOPLATA_CASH_ON_DELIVERY')
        );
        Configuration::UpdateValue(
            'YA_MARKET_ORDERS_POSTOPLATA_CARD_ON_DELIVERY',
            Tools::getValue('YA_MARKET_ORDERS_POSTOPLATA_CARD_ON_DELIVERY')
        );
        Configuration::UpdateValue('YA_MARKET_ORDERS_SET_CHANGEC', Tools::getValue('YA_MARKET_ORDERS_SET_CHANGEC'));
        Configuration::UpdateValue('YA_MARKET_ORDERS_PUNKT', Tools::getValue('YA_MARKET_ORDERS_PUNKT'));

        if (Tools::getValue('YA_MARKET_ORDERS_TOKEN') == '') {
            $errors .= $this->module->displayError($this->l('Token to refer to the Yandex store, not filled!'));
        } else {
            Configuration::UpdateValue('YA_MARKET_ORDERS_TOKEN', Tools::getValue('YA_MARKET_ORDERS_TOKEN'));
        }

        Configuration::UpdateValue('YA_MARKET_ORDERS_APIURL', "https://api.partner.market.yandex.ru/v2/");

        if (Tools::getValue('YA_MARKET_ORDERS_LOGIN') == '') {
            $errors .= $this->module->displayError($this->l('Fill your username in Yandex!'));
        } else {
            Configuration::UpdateValue('YA_MARKET_ORDERS_LOGIN', Tools::getValue('YA_MARKET_ORDERS_LOGIN'));
        }

        if (Tools::getValue('YA_MARKET_ORDERS_NC') == '') {
            $errors .= $this->module->displayError($this->l('Fill your room campaign!'));
        } else {
            Configuration::UpdateValue('YA_MARKET_ORDERS_NC', Tools::getValue('YA_MARKET_ORDERS_NC'));
        }

        if (Tools::getValue('YA_MARKET_ORDERS_ID') == '') {
            $errors .= $this->module->displayError($this->l('Not filled in the application ID!'));
        } else {
            Configuration::UpdateValue('YA_MARKET_ORDERS_ID', Tools::getValue('YA_MARKET_ORDERS_ID'));
        }

        if (Tools::getValue('YA_MARKET_ORDERS_PW') == '') {
            $errors .= $this->module->displayError($this->l('Not filled with an application-specific Password!'));
        } else {
            Configuration::UpdateValue('YA_MARKET_ORDERS_PW', Tools::getValue('YA_MARKET_ORDERS_PW'));
        }

        if ($errors == '') {
            $carriers = Carrier::getCarriers(Context::getContext()->language->id, true, false, false, null, 5);
            foreach ($carriers as $a) {
                Configuration::UpdateValue(
                    'YA_MARKET_ORDERS_DELIVERY_'.$a['id_carrier'],
                    Tools::getValue('YA_MARKET_ORDERS_DELIVERY_'.$a['id_carrier'])
                );
            }

            $errors = $this->module->displayConfirmation($this->l('Settings saved successfully!'));
        }

        return $errors;
    }

    public function initConfiguration()
    {
    }

    public function getMarketOrderByOrderId($orderId)
    {
        $query = new \DbQuery();
        $query->select('*');
        $query->from('yandex_market_orders');
        $query->where('id_order = '.(int)$orderId);
        $row = \Db::getInstance()->getRow($query);

        return $row;
    }

    public function getOrderByMarketOrderId($marketOrderId)
    {
        $query = new \DbQuery();
        $query->select('id_order');
        $query->from('yandex_market_orders');
        $query->where('id_market_order = '.(int)$marketOrderId);
        $row = \Db::getInstance()->getRow($query);

        $order = null;
        if (!empty($row)) {
            $order = new \Order((int)$row['id_order']);
        }
        return $order;
    }

    public function sendCarrierToYandex($order, $statuses)
    {
        $marketOrder = $this->getMarketOrderByOrderId($order->id);
        if (empty($marketOrder)) {
            $this->module->log('warning', 'Market order for order#' . $order->id . ' not exists');
            return;
        }
        if ($marketOrder['home'] == '' || empty($marketOrder['id_market_order'])) {
            $this->module->log('warning', 'Market order home = "" or empty market order id for order#' . $order->id);
            return;
        }
        if (!in_array($order->current_state, $statuses)) {
            $this->module->log('warning', 'Invalid status "' . $order->current_state . '" on order#' . $order->id);
            return;
        }
        $partner = new Partner();
        $partner->sendDelivery($order);
    }
}
