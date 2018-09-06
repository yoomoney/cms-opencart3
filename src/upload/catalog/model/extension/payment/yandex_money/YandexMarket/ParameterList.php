<?php

namespace YandexMoneyModule\YandexMarket;

class ParameterList
{
    private $name;
    private $unit;
    private $value;

    /**
     * ParameterList constructor.
     * @param string $name
     * @param string $value
     * @param string|null $unit
     */
    public function __construct($name, $value, $unit = null)
    {
        $this->name  = $name;
        $this->unit  = $unit;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @return bool
     */
    public function hasUnit()
    {
        return !empty($this->unit);
    }

    /**
     * @param string|integer $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return string|integer
     */
    public function getValue()
    {
        return $this->value;
    }

}
