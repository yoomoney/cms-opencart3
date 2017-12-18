<?php

/**
 * The MIT License
 *
 * Copyright (c) 2017 NBCO Yandex.Money LLC
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:

 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.

 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace YandexCheckout\Request\Payments\Payment;

use YandexCheckout\Common\AbstractRequestBuilder;
use YandexCheckout\Common\Exceptions\EmptyPropertyValueException;
use YandexCheckout\Common\Exceptions\InvalidPropertyException;
use YandexCheckout\Common\Exceptions\InvalidPropertyValueException;
use YandexCheckout\Common\Exceptions\InvalidPropertyValueTypeException;
use YandexCheckout\Common\Exceptions\InvalidRequestException;
use YandexCheckout\Model\AmountInterface;
use YandexCheckout\Model\MonetaryAmount;

class CreateCaptureRequestBuilder extends AbstractRequestBuilder
{
    /**
     * @var CreateCaptureRequest
     */
    protected $currentObject;

    /**
     * @var MonetaryAmount
     */
    private $amount;

    /**
     * @return CreateCaptureRequest
     */
    protected function initCurrentObject()
    {
        $this->amount = new MonetaryAmount();
        return new CreateCaptureRequest();
    }

    /**
     * Устанавливает сумму оплаты
     * @param AmountInterface|array|string $value Подтверждаемая сумма оплаты
     * @return CreateCaptureRequestBuilder Инстанс билдера запросов на подтверждение суммы оплаты
     *
     * @throws EmptyPropertyValueException Генерируется если было передано пустое значение
     * @throws InvalidPropertyValueException Выбрасывается если переданная сумма меньше или равна нулю
     * @throws InvalidPropertyValueTypeException Выбрасывается если переданная сумма не является числом или объектом
     * типа AmountInterface
     */
    public function setAmount($value)
    {
        if ($value === null || $value === '') {
            throw new EmptyPropertyValueException('Empty currency value', 0, 'amount.currency');
        } elseif (is_object($value) && $value instanceof AmountInterface) {
            $this->amount->setValue($value->getValue());
            $this->amount->setCurrency($value->getCurrency());
        } elseif (is_array($value)) {
            $this->amount->fromArray($value);
        } elseif (is_numeric($value)) {
            $this->amount->setValue($value);
        } else {
            throw new InvalidPropertyValueTypeException(
                'Invalid amount value type in CreateCaptureRequestBuilder',
                0,
                'CreateCaptureRequestBuilder.amount',
                $value
            );
        }
        return $this;
    }

    /**
     * Устанавливает валюту в которой будет происходить подтверждение оплаты заказа
     * @param string $value Валюта в которой подтверждается оплата
     * @return CreateCaptureRequestBuilder Инстанс билдера запросов на подтверждение суммы оплаты
     *
     * @throws EmptyPropertyValueException Генерируется если было передано пустое значение
     * @throws InvalidPropertyValueTypeException Генерируется если было передано значение невалидного типа
     * @throws InvalidPropertyValueException Генерируется если был передан неподдерживаемый код валюты
     */
    public function setCurrency($value)
    {
        $this->amount->setCurrency($value);
        return $this;
    }

    /**
     * Осуществляет сборку объекта запроса к API
     * @param array|null $options Массив дополнительных настроек объекта
     * @return CreateCaptureRequestInterface Иснатс объекта запроса к API
     *
     * @throws InvalidRequestException Выбрасывается если при валидации запроса произошла ошибка
     * @throws InvalidPropertyException Выбрасывается если не удалось установить один из параметров, переданных в
     * массиве настроек
     */
    public function build(array $options = null)
    {
        if (!empty($options)) {
            $this->setOptions($options);
        }
        $this->currentObject->setAmount($this->amount);
        return parent::build();
    }
}
