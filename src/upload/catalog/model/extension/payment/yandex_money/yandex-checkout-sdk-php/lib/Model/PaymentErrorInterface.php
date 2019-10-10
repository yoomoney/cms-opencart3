<?php

namespace YandexCheckout\Model;

/**
 * Interface PaymentErrorInterface
 * 
 * @package YandexCheckout\Model
 * 
 * @property-read string $code Код ошибки
 * @property-read string $description Дополнительное текстовое пояснение ошибки
 */
interface PaymentErrorInterface
{
    /**
     * @return string Код ошибки
     */
    function getCode();

    /**
     * @return string Дополнительное текстовое пояснение ошибки
     */
    function getDescription();
}
