<?php

namespace YandexMoneyModule\YandexMarket;

class Currency
{
    /**
     * @var string Идентификатор валюты
     */
    private $id;

    /**
     * @var int|float|string Курс валюты
     */
    private $rate;

    /**
     * @var int|float|null Надбавка
     */
    private $plus;

    /**
     * @param int $id
     * @param int|float|string $rate
     * @param int|float|null $plus
     */
    public function __construct($id, $rate, $plus = null)
    {
        $this->id   = $id;
        $this->rate = $rate;
        $this->plus = $plus;
    }

    /**
     * @return array
     */
    public static function getAvailableCurrencies()
    {
        return array('RUR', 'RUB', 'USD', 'EUR', 'UAH', 'BYN', 'KZT');
    }

    /**
     * @return array
     */
    public static function getAvailableRateCodes()
    {
        return array('CBRF', 'NBU', 'NBK', 'СВ');
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * @return float|null
     */
    public function getPlus()
    {
        return $this->plus;
    }

}