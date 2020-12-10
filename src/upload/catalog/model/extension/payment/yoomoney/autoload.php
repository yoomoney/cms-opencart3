<?php

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

define('YOOMONEY_MODULE_PATH', dirname(__FILE__));

function yooMoneyLoadClass($className)
{
    if (strncmp('YooMoneyModule', $className, 14) === 0) {
        $path = YOOMONEY_MODULE_PATH;
        $length = 14;
    } else {
        return;
    }
    if (DIRECTORY_SEPARATOR === '/') {
        $path .= str_replace('\\', '/', substr($className, $length)) . '.php';
    } else {
        $path .= substr($className, $length) . '.php';
    }
    if (file_exists($path)) {
        require_once $path;
    }
}

spl_autoload_register('yooMoneyLoadClass');
