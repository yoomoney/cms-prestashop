<?php

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

define('YOOMONEY_MODULE_ROOT_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);
define('YOOMONEY_MODULE_CLASS_PATH', YOOMONEY_MODULE_ROOT_PATH . 'classes');

function yooMoneyLoadClass($className)
{
    if (strncmp('YooMoneyModule', $className, 14) === 0) {
        $path = YOOMONEY_MODULE_CLASS_PATH;
        $length = 14;
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
        require_once $path;
    }
}

spl_autoload_register('yooMoneyLoadClass');