<?php

function yandexMoneyClassLoader($className)
{
    if (strncmp('YandexMoneyModule', $className, 17) === 0) {
        $length = 17;
        $path = __DIR__;
    } elseif (strncmp('YandexCheckout', $className, 14) === 0) {
        $length = 14;
        $path = __DIR__ . '/yandex-checkout-sdk-php/lib/';
    } elseif (strncmp('Psr\Log', $className, 7) === 0) {
        $length = 7;
        $path = __DIR__ . '/yandex-checkout-sdk-php/vendor/psr/log/Psr/Log';
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

spl_autoload_register('yandexMoneyClassLoader');
