<?php

namespace YandexCheckout\Model;

/**
 * Interface OrderInterface
 *
 * @package YandexCheckout\Model
 *
 * @property-read AmountInterface $amount
 * @property-read ReceiptInterface $receipt Данные фискального чека 54-ФЗ
 */
interface OrderInterface
{
    /**
     * @return AmountInterface
     */
    function getAmount();

    /**
     * @return ReceiptInterface Данные фискального чека 54-ФЗ
     */
    function getReceipt();

    /**
     * @return bool
     */
    function hasReceipt();
}