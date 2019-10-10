<?php

namespace YandexCheckout\Model;

use YandexCheckout\Common\AbstractObject;

/**
 * RefundError - Отказ в проведении возврата платежа
 * Код ошибки | Описание
 * --- | ---
 * authorization_rejected | Отказ в проведении возврата платежа по логике платежной системы (например лимиты или отказ антифрод-аналитики)
 * 
 * @property string $code Код ошибки
 * @property string $description Дополнительное текстовое пояснение ошибки
 */
class RefundError extends AbstractObject implements RefundErrorInterface
{
    /**
     * @var string Код ошибки
     */
    private $_code;

    /**
     * @var string Дополнительное текстовое пояснение ошибки
     */
    private $_description;

    /**
     * @return string Код ошибки
     */
    public function getCode()
    {
        return $this->_code;
    }

    /**
     * @param string $value Код ошибки
     */
    public function setCode($value)
    {
        if ($value === null || $value === '') {
            throw new \InvalidArgumentException('Invalid value');
        }
        $this->_code = (string)$value;
    }

    /**
     * @return string Дополнительное текстовое пояснение ошибки
     */
    public function getDescription()
    {
        return $this->_description;
    }

    /**
     * @param string $value Дополнительное текстовое пояснение ошибки
     */
    public function setDescription($value)
    {
        $this->_description = (string)$value;
    }
}
