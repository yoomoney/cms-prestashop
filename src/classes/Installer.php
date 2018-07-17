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
        $customer->firstname = $this->module->l('Service user for YCMS');
        $customer->lastname = $this->module->l('Service user for YCMS');
        if (property_exists($customer, 'middlename')) {
            $customer->middlename = $this->module->l('Service user for YCMS');
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

    public function installTab()
    {
        $tab = new \Tab();
        $tab->active = 1;
        $tab->class_name = \YandexModule::ADMIN_CONTROLLER;
        $tab->name = array();
        foreach (\Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = \YandexModule::ADMIN_CONTROLLER;
        }
        $tab->id_parent = -1;
        $tab->module = $this->module->name;

        return $tab->add();
    }

    public function uninstallTab() {
        $id_tab = (int)\Tab::getIdFromClassName(\YandexModule::ADMIN_CONTROLLER);
        if ($id_tab) {
            $tab = new \Tab($id_tab);
            return $tab->delete();
        } else {
            return false;
        }
    }

    public function issetTab() {
        return \Tab::getIdFromClassName(\YandexModule::ADMIN_CONTROLLER) !== false;
    }

}
