<?php
/**
 * Module is prohibited to sales! Violation of this condition leads to the deprivation of the license!
 *
 * @author    Yandex.Money <cms@yamoney.ru>
 * @copyright © 2015-2017 NBCO Yandex.Money LLC
 * @license   https://money.yandex.ru/doc.xml?id=527052
 *
 * @category  Front Office Features
 * @package   Yandex Payment Solution
 */

namespace YandexMoneyModule;

use Address;
use Carrier;
use Cart;
use CartRule;
use Configuration;
use Combination;
use Country;
use Context;
use Currency;
use Customer;
use Module;
use Product;
use StockAvailable;
use Tools;

class Partner
{
    private $app_id;
    private $url;
    private $number;
    private $login;
    private $app_pw;
    private $token;
    private $context;
    private $ya_token;

    /** @var \yandexmodule */
    private $module;

    public function __construct()
    {
        $this->app_id = Configuration::get('YA_MARKET_ORDERS_ID');
        $this->url = Configuration::get('YA_MARKET_ORDERS_APIURL');
        $this->number = Configuration::get('YA_MARKET_ORDERS_NC');
        $this->login = Configuration::get('YA_MARKET_ORDERS_LOGIN');
        $this->app_pw = Configuration::get('YA_MARKET_ORDERS_PW');
        $this->token = Configuration::get('YA_MARKET_ORDERS_TOKEN');
        $this->ya_token = Configuration::get('YA_MARKET_ORDERS_YATOKEN');
        $this->context = Context::getContext();
        $this->module = Module::getInstanceByName('yandexmodule');
    }

    public function getOrders()
    {
        return $this->sendResponse('/campaigns/'.$this->number.'/orders', array(), array(), 'GET');
    }

    public function getOutlets()
    {
        $data = $this->sendResponse('/campaigns/'.$this->number.'/outlets', array(), array(), 'GET');
        $array = array('outlets' => array());
        foreach ($data->outlets as $o) {
            $array['outlets'][] = array('id' => (int)$o->shopOutletId);
        }
        $return = array(
            'json' => $array,
            'array' => $data->outlets
        );

        return $return;
    }

    public function getOrder($id)
    {
        $data = $this->sendResponse('/campaigns/'.$this->number.'/orders/'.$id, array(), array(), 'GET');
        return $data;
    }

    public function sendOrder($state, $id)
    {
        $params = array(
            'order' => array(
                'status' => $state,
            )
        );

        if ($state == 'CANCELLED') {
            $params['order']['substatus'] = 'SHOP_FAILED';
        }

        return $this->sendResponse(
            '/campaigns/'.$this->number.'/orders/'.$id.'/status',
            array(),
            $params,
            'PUT'
        );
    }

    public function sendDelivery($order)
    {
        $order_ya_db = $this->module->getOrderModel()->getMarketOrderByOrderId($order->id);
        $ya_order = $this->getOrder((int) $order_ya_db['id_market_order']);
        $address = new Address($order->id_address_delivery);
        $carrier = new Carrier($order->id_carrier, $this->context->language->id);
        $country = new Country($address->id_country, $this->context->language->id);
        $date_time_string = explode(' ', $order->delivery_date);
        $types = unserialize(Configuration::get('YA_MARKET_ORDERS_CARRIER_SERIALIZE'));
        $type = $types[$carrier->id] ? $types[$carrier->id] : 'POST';
        $params = array(
            'delivery' => array(
                'id' => $carrier->id,
                'type' => $type,
                'serviceName' => $carrier->name.'('.$carrier->delay.')',
                'dates' => array(
                    'fromDate' => $date_time_string[0] > 0
                        ? date('d-m-Y', strtotime($date_time_string[0])) : date('d-m-Y'),
                )
            )
        );
        
        if ($ya_order->order->paymentType == 'POSTPAID') {
            $params['delivery']['price'] = $order->total_shipping;
        }

        if ($type == 'PICKUP') {
            $params['delivery']['outletId'] = !empty($order_ya_db['outlet'])
                ? $order_ya_db['outlet'] : Configuration::get('YA_MARKET_ORDERS_PUNKT');
            unset($params['delivery']['serviceName']);
        } else {
            $params['delivery']['address'] = array(
                    'country' => $country->name,
                    'postcode' => $address->postcode,
                    'city' => $address->city,
                    'house' => $order_ya_db['home'],
                    'street' => $address->address1.' '.($address->address2 ? $address->address2 : ''),
                    'recipient' => $address->firstname.' '.$address->lastname,
                    'phone' => $address->phone_mobile ? $address->phone_mobile : $address->phone,
                );
        }


        return $this->sendResponse(
            '/campaigns/'.$this->number.'/orders/'
            .$order_ya_db['id_market_order'].'/delivery',
            array(),
            $params,
            'PUT'
        );
    }

    public function addData($data, $add, $type)
    {
        $delivery = array();
        $cart = new Cart();
        if ($data->$type->currency == 'RUR') {
            $currency_id = Currency::getIdByIsoCode('RUB');
        } else {
            $currency_id = Currency::getIdByIsoCode($data->cart->currency);
        }
        $def_currency = Configuration::get('PS_CURRENCY_DEFAULT');
        $this->context->cookie->id_currency = ($def_currency != $currency_id) ? $currency_id : $def_currency;
        $this->context->cookie->write();
        $this->context->currency = new Currency($this->context->cookie->id_currency);
        $cart->id_lang = (int)$this->context->cookie->id_lang;
        $cart->id_currency = (int)$this->context->cookie->id_currency;
        $cart->id_guest = (int)$this->context->cookie->id_guest;
        $cart->add();
        $this->context->cookie->id_cart = (int)$cart->id;
        $this->context->cookie->write();
        $buyer = isset($data->$type->buyer) ? $data->$type->buyer : '';
        $b = array();
        if ($add) {
            $delivery = isset($data->$type->delivery->address) ? $data->$type->delivery->address : new stdClass();
            $street = isset($delivery->street) ? ' Улица: '.$delivery->street : 'Самовывоз';
            $subway = isset($delivery->subway) ? ' Метро: '.$delivery->subway : '';
            $block = isset($delivery->block) ? ' Корпус/Строение: '.$delivery->block : '';
            $floor = isset($delivery->floor) ? ' Этаж: '.$delivery->floor : '';
            $house = isset($delivery->house) ? ' Дом: '.$delivery->house : '';
            $address1 = $street.$subway.$block.$floor.$house;
            $customer = new Customer(Configuration::get('YA_MARKET_ORDERS_CUSTOMER'));
            $address = new Address();
            $address->firstname = $customer->firstname;
            $address->lastname = $customer->lastname;
            $address->phone_mobile = isset($buyer->phone) ? $buyer->phone : 999999;
            $address->postcode = isset($delivery->postcode) ? $delivery->postcode : 000000;
            $address->address1 = $address1;
            $address->city = isset($delivery->city) ? $delivery->city : 'Город';
            $address->alias = 'market_orders_' . Tools::substr(md5(time()._COOKIE_KEY_), 0, 7);
            $address->id_customer = $customer->id;
            $address->id_country = Configuration::get('PS_COUNTRY_DEFAULT');
            $address->save();
            $cart->id_address_invoice = (int)($address->id);
            $cart->id_address_delivery = (int)($address->id);
            $cart->update();
            $cart->id_customer = (int)$customer->id;
            $this->context->cookie->id_customer = (int)$customer->id;
            $this->context->cookie->write();
            $b = array(
                'address' => $address,
                'customer' => $customer
            );
        }
        CartRule::autoRemoveFromCart($this->context);
        CartRule::autoAddToCart($this->context);
        $a = array(
            'cart' => $cart,
        );
        $dd = array_merge($a, $b);
        return $dd;
    }

    public function requestItems($data)
    {
        $delivery = array();
        $items = $data->cart->items;
        if (isset($data->cart->delivery->address)) {
            $delivery = $data->cart->delivery->address;
        }
        if (count($items)) {
            if ($delivery) {
                $d = $this->addData($data, true, 'cart');
                $address = $d['address'];
            } else {
                $d = $this->addData($data, false, 'cart');
            }
            $cart = $d['cart'];
            $tovar = array();
            foreach ($items as $item) {
                $id_a = null;
                $id = explode('c', $item->offerId);
                $product = new Product($id[0], true, $this->context->cookie->id_lang);
                if (isset($id[1])) {
                    $id_a = (int)$id[1];
                }

                $count_shop = StockAvailable::getQuantityAvailableByProduct($product->id, $id_a);
                if (!$product->active || $count_shop < (int)$item->count) {
                    continue;
                }

                $count = min($count_shop, (int)$item->count);
                if ($id_a) {
                    $comb = new Combination($id_a);
                    if ($count < $comb->minimal_quantity) {
                        continue;
                    }
                } elseif ($count < $product->minimal_quantity) {
                    continue;
                }

                $price = Product::getPriceStatic($product->id, null, $id_a);
                $result = $cart->updateQty((int)$item->count, (int)$id[0], $id_a);
                $total = Tools::ps_round($price, 2);
                if ($result) {
                    $tovar[] = array(
                        'feedId' => $item->feedId,
                        'offerId' => $item->offerId,
                        'price' => $total,
                        'count' => (int)$count,
                        'delivery' => true,
                    );

                    $cart->update();
                }
            }
            $dost = array();
            $pm = array();
            $types = unserialize(Configuration::get('YA_MARKET_ORDERS_CARRIER_SERIALIZE'));
            foreach ($cart->simulateCarriersOutput() as $k => $d) {
                $id = str_replace(',', '', Cart::desintifier($d['id_carrier']));
                $type = isset($types[$id]) ? $types[$id] : 'POST';
                $dost[$k] = array(
                    'id' => $id,
                    'serviceName' => $d['name'],
                    'type' => $type,
                    'price' => $d['price'],
                    'dates' => array(
                        'fromDate' => date('d-m-Y'),
                        'toDate' => date('d-m-Y'),
                    ),
                );

                if ($type == 'PICKUP') {
                    $outlets = $this->getOutlets();
                    $dost[$k] = array_merge($dost[$k], $outlets['json']);
                }
            }

            if (Configuration::get('YA_MARKET_ORDERS_PREDOPLATA_YANDEX')) {
                $pm[] = 'YANDEX';
            }

            if (Configuration::get('YA_MARKET_ORDERS_PREDOPLATA_SHOP_PREPAID')) {
                $pm[] = 'SHOP_PREPAID';
            }

            if (Configuration::get('YA_MARKET_ORDERS_POSTOPLATA_CASH_ON_DELIVERY')) {
                $pm[] = 'CASH_ON_DELIVERY';
            }

            if (Configuration::get('YA_MARKET_ORDERS_POSTOPLATA_CARD_ON_DELIVERY')) {
                $pm[] = 'CARD_ON_DELIVERY';
            }


            $array = array(
                'cart' => array(
                    'items' => $tovar,
                    'deliveryOptions' => $dost,
                    'paymentMethods' => $pm
                )
            );

            $cart->delete();
            $this->context->cookie->logout();
            if ($delivery) {
                $address->delete();
            }

            die(Tools::jsonEncode($array));
        }
    }

    public function alertOrderStatus($data)
    {
        $order = $this->module->getOrderModel()->getOrderByMarketOrderId($data->order->id);
        if ($order->id_cart > 0) {
            $status = $data->order->status;
            if ($status == 'CANCELLED') {
                $sub = $data->order->substatus;
                if (isset($sub) && $sub == 'RESERVATION_EXPIRED') {
                    $order->setCurrentState((int)$this->module->status['RESERVATION_EXPIRED']);
                } else {
                    $order->setCurrentState((int)$this->module->status['CANCELLED']);
                }
            }

            if ($status == 'PROCESSING') {
                $buyer = isset($data->order->buyer) ? $data->order->buyer : '';
                if (isset($buyer) && !empty($buyer)) {
                    $customer = new Customer();
                    $c = $customer->getByEmail($buyer->email);
                    if (isset($c->id) && $c->id > 0) {
                        $customer = new Customer($c->id);
                    } else {
                        $customer->firstname = $buyer->firstName;
                        $customer->lastname = $buyer->lastName;
                        $customer->email = $buyer->email;
                        $customer->passwd = Tools::encrypt('OPC123456dmo');
                        $customer->newsletter = 1;
                        $customer->optin = 1;
                        $customer->active = 1;
                        $customer->save();
                    }
                    
                    $address = new Address($order->id_address_delivery);
                    $address->id_customer = $customer->id;
                    $order->id_customer = $customer->id;
                    $order->save();
                    $address->save();
                }

                $order->setCurrentState((int)$this->module->status['PROCESSING']);
            }

            if ($status == 'UNPAID') {
                $order->setCurrentState($this->module->status['UNPAID']);
            }

            die(1);
        }
    }

    public function orderAccept($data)
    {
        $array = array();
        $items = $data->order->items;

        if (count($items)) {
            $d = $this->addData($data, true, 'order');
            $cart = $d['cart'];
            $customer = $d['customer'];
            $address = $d['address'];

            foreach ($items as $item) {
                $id_a = null;
                $id = explode('c', $item->offerId);
                $product = new Product($id[0], true, $this->context->cookie->id_lang);
                if (isset($id[1])) {
                    $id_a = (int)$id[1];
                }

                $count_shop = StockAvailable::getQuantityAvailableByProduct($product->id, $id_a);
                if (!$product->active || $count_shop < (int)$item->count) {
                    continue;
                }

                $result = $cart->updateQty((int)$item->count, (int)$id[0], $id_a);
                if ($result) {
                    $cart->update();
                }
            }

            if (count($items) == count($cart->getProducts())
                    && isset($data->order->paymentMethod)
                    && isset($data->order->paymentType)
                ) {
                $resultat = false;
                if ($data->order->delivery->id > 0) {
                    $do = array($address->id => $data->order->delivery->id.',');
                    $cart->setDeliveryOption($do);
                }

                $message = '';
                if (isset($data->order->notes)) {
                    $message = $data->order->notes ? $data->order->notes : null;
                }

                $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
                $order = new OrderCreate();
                $order->name = $data->order->paymentType.'_'.$data->order->paymentMethod;
                $order->module = 'yandexmodule';
                $total = $this->context->cart->getOrderTotal(true, Cart::BOTH);
                $res = $order->validateOrder(
                    (int)$cart->id,
                    $this->module->status['RESERVATION'],
                    $total,
                    'Yandex.Market.Order',
                    $message,
                    array(),
                    null,
                    false,
                    ($cart->secure_key ? $cart->secure_key :
                        ($customer->secure_key ? $customer->secure_key : false)
                    )
                );

                if ($res) {
                    $values_to_insert = array(
                        'id_order' => (int)$order->currentOrder,
                        'id_market_order' => (int)$data->order->id,
                        'ptype' => ($data->order->paymentType == "POSTPAID")?"POSTPAID":"PREPAID",
                        'pmethod' => pSQL($data->order->paymentMethod),
                        'home' => (isset($data->order->delivery->address->house))?
                            (int) $data->order->delivery->address->house : 0,
                        'outlet' => isset($data->order->delivery->outlet->id) ?
                            (int) $data->order->delivery->outlet->id : 0,
                        'currency' => pSQL($data->order->currency)
                    );

                    \Db::getInstance()->autoExecute(_DB_PREFIX_.'yandex_market_orders', $values_to_insert, 'INSERT');
                    $resultat = true;
                } else {
                    $resultat = false;
                }
            } else {
                $resultat = false;
            }
        } else {
            $resultat = false;
        }

        if ($resultat) {
            $array = array(
                'order' => array(
                    'accepted' => true,
                    'id' => ''.$order->currentOrder.'',
                )
            );
        } else {
            $array = array(
                'order' => array(
                    'accepted' => false,
                    'reason' => 'OUT_OF_DATE'
                )
            );
        }

        die(Tools::jsonEncode($array));
    }

    public function sendResponse($to, $headers, $params, $type)
    {
        $response = $this->post(
            $this->url.$to.'.json?oauth_token='.$this->ya_token
            .'&oauth_client_id='.$this->app_id.'&oauth_login='.$this->login,
            $headers,
            $params,
            $type
        );

        $data = Tools::jsonDecode($response->body);
        if (isset($data->error)) {
            $this->module->log('info', 'Failed to send request, response is ' . $response->body);
        }
        if ($response->status_code == 200) {
            return $data;
        } else {
            Tools::d($response);
        }
    }

    public static function post($url, $headers, $params, $type)
    {
        $curlOpt = array(
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLINFO_HEADER_OUT => 1,
        );

        switch (Tools::strtoupper($type)) {
            case 'DELETE':
                $curlOpt[CURLOPT_CUSTOMREQUEST] = "DELETE";
                break;
            case 'GET':
                if (!empty($params)) {
                    $url .= (strpos($url, '?')===false ? '?' : '&') . http_build_query($params);
                }
                break;
            case 'PUT':
                $headers[] = 'Content-Type: application/json;';
                $body = Tools::jsonEncode($params);
                $fp = tmpfile();
                fwrite($fp, $body, Tools::strlen($body));
                fseek($fp, 0);
                $curlOpt[CURLOPT_PUT] = true;
                $curlOpt[CURLOPT_INFILE] = $fp;
                $curlOpt[CURLOPT_INFILESIZE] = Tools::strlen($body);
                break;
        }

        $curlOpt[CURLOPT_HTTPHEADER] = $headers;
        $curl = curl_init($url);
        curl_setopt_array($curl, $curlOpt);
        $rbody = curl_exec($curl);
        $error = curl_error($curl);
        $rcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        // Tools::d(curl_getinfo($curl, CURLINFO_HEADER_OUT));
        curl_close($curl);
        $result = new stdClass();
        $result->status_code = $rcode;
        $result->body = $rbody;
        $result->error = $error;
        return $result;
    }
}
