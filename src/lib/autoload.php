<?php

define('YANDEX_MONEY_SDK_ROOT_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'yandex-checkout-sdk' . DIRECTORY_SEPARATOR . 'lib');
define('YANDEX_MONEY_PSR_LOG_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'yandex-checkout-sdk'
    .DIRECTORY_SEPARATOR.'vendor'
    .DIRECTORY_SEPARATOR.'psr'
    .DIRECTORY_SEPARATOR.'log'
    .DIRECTORY_SEPARATOR.'Psr'
    .DIRECTORY_SEPARATOR.'Log');
define('YANDEX_MONEY_MODULE_CLASS_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'classes');

function yandexMoneyLoadClass($className)
{
    if (strncmp('YandexCheckout', $className, 14) === 0) {
        $path = YANDEX_MONEY_SDK_ROOT_PATH;
        $length = 14;
    } elseif (strncmp('Psr\Log', $className, 7) === 0) {
        $path = YANDEX_MONEY_PSR_LOG_PATH;
        $length = 7;
    } elseif (strncmp('YandexMoneyModule', $className, 17) === 0) {
        $path = YANDEX_MONEY_MODULE_CLASS_PATH;
        $length = 17;
    } else {
        return;
    }
    if (DIRECTORY_SEPARATOR === '/') {
        $path .= str_replace('\\', '/', substr($className, $length));
    } else {
        $path .= substr($className, $length);
    }
    $path .= '.php';
    if (file_exists($path)) {
        include $path;
    }
}

spl_autoload_register('yandexMoneyLoadClass');