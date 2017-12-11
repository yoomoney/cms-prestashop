<?php
/**
 * @author Yandex.Money <cms@yamoney.ru>
 * @copyright © 2015-2017 NBCO Yandex.Money LLC
 * @license  https://money.yandex.ru/doc.xml?id=527052
 */

namespace YandexMoneyModule;

/**
 * Класс хэлпер, используемый при установку и удалении модуля
 *
 * @package YandexMoneyModule
 */
class Installer
{
    /**
     * @var \yandexmodule
     */
    private $module;

    public function __construct(\yandexmodule $module)
    {
        $this->module = $module;
    }

    /**
     *
     */
    public function addOrderStatuses()
    {
        $status = array(
            'DELIVERY' => array(
                'name' => 'YA Ждёт отправки',
                'color' => '#8A2BE2',
                'id' => 900,
                'paid' => true,
                'shipped' => false,
                'logable' => true,
                'delivery' => true
            ),
            'CANCELLED' => array(
                'name' => 'YA Отменен',
                'color' => '#b70038',
                'id' => 901,
                'paid' => false,
                'shipped' => false,
                'logable' => true,
                'delivery' => false
            ),
            'PICKUP' => array(
                'name' => 'YA В пункте самовывоза',
                'color' => '#cd98ff',
                'id' => 902,
                'paid' => true,
                'shipped' => true,
                'logable' => true,
                'delivery' => true
            ),
            'PROCESSING' => array(
                'name' => 'YA В процессе подготовки',
                'color' => '#FF8C00',
                'id' => 903,
                'paid' => true,
                'shipped' => false,
                'logable' => false,
                'delivery' => true
            ),
            'DELIVERED' => array(
                'name' => 'YA Доставлен',
                'color' => '#108510',
                'id' => 904,
                'paid' => true,
                'shipped' => true,
                'logable' => true,
                'delivery' => true
            ),
            'UNPAID' => array(
                'name' => 'YA Не оплачен',
                'color' => '#ff1c30',
                'id' => 905,
                'paid' => false,
                'shipped' => false,
                'logable' => false,
                'delivery' => false
            ),
            'RESERVATION_EXPIRED' => array(
                'name' => 'YA Резерв отменён',
                'color' => '#ff2110',
                'id' => 906,
                'paid' => false,
                'shipped' => false,
                'logable' => false,
                'delivery' => false
            ),
            'RESERVATION' => array(
                'name' => 'YA Резерв',
                'color' => '#0f00d3',
                'id' => 907,
                'paid' => false,
                'shipped' => false,
                'logable' => false,
                'delivery' => false
            ),
        );
        $existsStatuses = \OrderState::getOrderStates(1);
        foreach ($status as $s) {
            if (array_key_exists($s['id'], $existsStatuses)) {
                continue;
            }
            $os = new \OrderState((int)$s['id']);
            $os->id = $s['id'];
            $os->force_id = true;
            $os->name = $this->module->multiLangField($s['name']);
            $os->color = $s['color'];
            $os->module_name = $this->module->name;
            $os->paid = $s['paid'];
            $os->logable = $s['logable'];
            $os->shipped = $s['shipped'];
            $os->delivery = $s['delivery'];
            $os->add();
            $this->module->log('debug', 'Order state "' . $s['name'] . '" added');
        }
    }

    /**
     * Delete Yandex.Market order statuses when module uninstalled
     * @param int[] $statuses Yandex.Market order statuses
     */
    public function removeOrderStatuses($statuses)
    {
        foreach ($statuses as $statusId) {
            $os = new \OrderState((int)$statusId);
            $os->id = $statusId;
            $os->delete();
        }
    }

    /**
     * Добавляет в базу данных магазина таблицы модуля
     */
    public function addDatabaseTables()
    {
        $sql = array();
        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'yandex_market_orders`
            (
                `id_order` int(10) NOT NULL,
                `id_market_order` varchar(100) NOT NULL,
                `currency` varchar(100) NOT NULL,
                `ptype` varchar(100) NOT NULL,
                `home` varchar(100) NOT NULL,
                `pmethod` varchar(100) NOT NULL,
                `outlet` varchar(100) NOT NULL,
                PRIMARY KEY  (`id_order`,`id_market_order`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci';

        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ya_money_payments` (
            `order_id`          INTEGER  NOT NULL,
            `payment_id`        CHAR(36) NOT NULL,
            `status`            ENUM(\'pending\', \'waiting_for_capture\', \'succeeded\', \'canceled\') NOT NULL,
            `amount`            DECIMAL(11, 2) NOT NULL,
            `currency`          CHAR(3) NOT NULL,
            `payment_method_id` CHAR(36) NOT NULL,
            `paid`              ENUM(\'Y\', \'N\') NOT NULL,
            `created_at`        DATETIME NOT NULL,
            `captured_at`       DATETIME NOT NULL DEFAULT \'0000-00-00 00:00:00\',

            CONSTRAINT `' . _DB_PREFIX_ . 'ya_money_payment_pk` PRIMARY KEY (`order_id`),
            CONSTRAINT `' . _DB_PREFIX_ . 'ya_money_payment_unq_payment_id` UNIQUE (`payment_id`) 
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci';

        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ya_money_refunds` (
            `refund_id`         CHAR(36) NOT NULL,
            `order_id`          INTEGER  NOT NULL,
            `payment_id`        CHAR(36) NOT NULL,
            `status`            ENUM(\'pending\', \'succeeded\', \'canceled\') NOT NULL,
            `amount`            DECIMAL(11, 2) NOT NULL,
            `currency`          CHAR(3) NOT NULL,
            `created_at`        DATETIME NOT NULL,
            `authorized_at`     DATETIME NOT NULL DEFAULT \'0000-00-00 00:00:00\',
            `comment`           VARCHAR(254) NOT NULL,

            CONSTRAINT `' . _DB_PREFIX_ . 'ya_money_refunds_pk` PRIMARY KEY (`refund_id`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=UTF8 COLLATE=utf8_general_ci';

        foreach ($sql as $query) {
            $this->module->log('debug', 'Execute query: ' . $query);
            \Db::getInstance()->execute($query);
        }
    }

    /**
     * Дропает таблицы модуля при его удалении
     */
    public function removeDatabaseTables()
    {
        $sql = array(
            'DROP TABLE IF EXISTS `'._DB_PREFIX_.'yandex_market_orders`',
            'DROP TABLE IF EXISTS `'._DB_PREFIX_.'ya_money_payments`',
            'DROP TABLE IF EXISTS `'._DB_PREFIX_.'ya_money_refunds`',
        );
        foreach ($sql as $query) {
            \Db::getInstance()->execute($query);
        }
    }

    /**
     *
     */
    public function addServiceCustomer()
    {
        $customer = new \Customer();
        $customer->firstname = 'Service user for YCMS';
        $customer->lastname = 'Service user for YCMS';
        if (property_exists($customer, 'middlename')) {
            $customer->middlename = 'Service user for YCMS';
        }
        $customer->email = 'service@example.com';
        if (version_compare(_PS_VERSION_, '1.7.0') < 0) {
            $customer->passwd = \Tools::encrypt('OPC123456dmo');
        } else {
            $customer->passwd = \Tools::hash('OPC123456dmo');
        }
        $customer->newsletter = 1;
        $customer->optin = 1;
        $customer->active = 0;
        $customer->add();
        \Configuration::updateValue('YA_MARKET_ORDERS_CUSTOMER', $customer->id);
        \Configuration::updateValue('YA_KASSA_PAYMENT_MODE', 'off');
    }

    /**
     * При удалении модуля удаляет сервисного пользователя
     */
    public function removeServiceCustomer()
    {
        $id = (int) \Configuration::get('YA_MARKET_ORDERS_CUSTOMER');
        if ($id > 0) {
            $customer = new \Customer($id);
            $customer->id = $id;
            $customer->delete();
        }
    }
}
