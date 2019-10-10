<?php

namespace YandexCheckout\Request;

use YandexCheckout\Common\AbstractObject;
use YandexCheckout\Common\Exceptions\EmptyPropertyValueException;
use YandexCheckout\Common\Exceptions\InvalidPropertyValueException;
use YandexCheckout\Common\Exceptions\InvalidPropertyValueTypeException;

/**
 * Запрос принят в обработку но результат его выполнения неизвестен.
 * Клиенту следует повторить запрос с теми же аргументами спустя рекомедуемое время повтора.
 * Тело ответа содержит пустой object.
 *
 * @package YandexCheckout\Request
 *
 * @property int $retryAfter Рекомендуемое время спустя которое следует повторить запрос
 */
class StateUnknownResponse extends AbstractObject
{
    /**
     * @var int Рекомендуемое время спустя которое следует повторить запрос
     */
    private $_retryAfter;

    /**
     * Конструктор, устанавливает значение рекомендуемого времени ответа равным дефолтному
     */
    public function __construct()
    {
        $this->_retryAfter = 5;
    }

    /**
     * Возвращает рекомендуемое время спустя которое следует повторить запрос
     * @return int Рекомендуемое время спустя которое следует повторить запрос
     */
    public function getRetryAfter()
    {
        return $this->_retryAfter;
    }

    /**
     * Устанавливает рекомендуемое время спустя которое следует повторить запрос
     * @param int $value Время через которое рекомендуется повторить запрос
     *
     * @throws EmptyPropertyValueException Выкидывается если было передано пустое значение
     * @throws InvalidPropertyValueException Выкидывается если значение невалидно
     * @throws InvalidPropertyValueTypeException Выкидывается если переданное значение не является числом
     */
    public function setRetryAfter($value)
    {
        if ($value === null || $value === '') {
            throw new EmptyPropertyValueException(
                'Empty retry after value in StateUnknownResponse', 0, 'StateUnknownResponse.retryAfter'
            );
        } elseif (is_numeric($value)) {
            $castedValue = (int)$value;
            if ($castedValue <= 0) {
                throw new InvalidPropertyValueException(
                    'Invalid retry after value in StateUnknownResponse', 0, 'StateUnknownResponse.retryAfter', $value
                );
            }
            $this->_retryAfter = (int)$castedValue;
        } else {
            throw new InvalidPropertyValueTypeException(
                'Invalid retry after value in StateUnknownResponse', 0, 'StateUnknownResponse.retryAfter', $value
            );
        }
    }
}