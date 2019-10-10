<?php

namespace YandexCheckout\Model;

use YandexCheckout\Common\AbstractObject;

/**
 * Order - Параметры заказа
 * * amount - Стоимость заказа
 *
 * @property AmountInterface $amount
 * @property ReceiptInterface $receipt Данные фискального чека 54-ФЗ
 */
class Order extends AbstractObject implements OrderInterface
{
    /**
     * @var AmountInterface
     */
    private $_amount;

    /**
     * @var Receipt Данные фискального чека 54-ФЗ
     */
    private $_receipt;

    /**
     * Order constructor.
     */
    public function __construct()
    {
        $this->_amount = new MonetaryAmount();
    }

    /**
     * @return AmountInterface
     */
    public function getAmount()
    {
        return $this->_amount;
    }

    /**
     * @param AmountInterface $value
     */
    public function setAmount(AmountInterface $value)
    {
        $this->_amount = $value;
    }

    /**
     * @return ReceiptInterface Данные фискального чека 54-ФЗ
     */
    public function getReceipt()
    {
        return $this->_receipt;
    }

    /**
     * @param ReceiptInterface $value Данные фискального чека 54-ФЗ
     */
    public function setReceipt(ReceiptInterface $value)
    {
        $this->_receipt = $value;
    }

    /**
     * @return bool
     */
    public function hasReceipt()
    {
        return $this->_receipt !== null;
    }

    /**
     *
     */
    public function removeReceipt()
    {
        $this->_receipt = null;
    }
}
