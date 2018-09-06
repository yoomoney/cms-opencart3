<?php

namespace YandexMoneyModule\YandexMarket;

/**
 * Класс для хранения информации о доставке
 *
 * @package YandexMoneyModule\YandexMarket
 */
class DeliveryOption
{
    /**
     * @var int Стоимость доставки
     */
    private $cost;

    /**
     * @var int|string Время доставки в днях
     */
    private $days;

    /**
     * @var int Доставка применяется до осуществления заказа до ... часов
     */
    private $orderBefore;

    /**
     * Конструктор, устанавливает стоимость доставки и время доставки в днях
     * @param int $cost Стоимость доставки
     * @param int $days Время доставки в днях
     */
    public function __construct($cost = 0, $days = 1)
    {
        $this->cost = $cost;
        $this->days = $days;
    }

    /**
     * Устанавливает стоимость доставки
     * @param int $value Стоимость доставки
     * @return DeliveryOption Инстанс текущего объекта
     */
    public function setCost($value)
    {
        if ($value <= 0) {
            $this->cost = 0;
        } else {
            $this->cost = (int)$value;
        }
        return $this;
    }

    /**
     * Возвращает стоимость доставки
     * @return int Стоимость доставки
     */
    public function getCost()
    {
        return $this->cost;
    }

    /**
     * Устагавливает время доставки в днях
     * @param int|string $value Время доставки в днях
     * @return DeliveryOption Инстанс текущего объекта
     */
    public function setDays($value)
    {
        $this->days = $value;
        return $this;
    }

    /**
     * Возвращает время доставки в днях
     * @return int|string Время доставки в дных
     */
    public function getDays()
    {
        return $this->days;
    }

    /**
     * Устанавливает время в часах до которого будет применяться доставка
     * @param int $value Время в часах
     * @return DeliveryOption Инстанс текущего объекта
     */
    public function setOrderBefore($value)
    {
        $this->orderBefore = $value;
        return $this;
    }

    /**
     * Возвращает время в часах, до которого применяется текущая доставка
     * @return int Время заказа в часах, до которого будет применена доставка
     */
    public function getOrderBefore()
    {
        return $this->orderBefore;
    }

    /**
     * Проверяет, было ли установлено время применения доставки
     * @return bool True если время было установлено, false если нет
     */
    public function hasOrderBefore()
    {
        return !empty($this->orderBefore);
    }
}