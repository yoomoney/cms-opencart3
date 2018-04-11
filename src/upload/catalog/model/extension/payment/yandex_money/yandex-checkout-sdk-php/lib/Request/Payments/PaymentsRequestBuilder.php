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
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace YandexCheckout\Request\Payments;

use YandexCheckout\Common\AbstractRequestBuilder;
use YandexCheckout\Common\Exceptions\InvalidPropertyValueException;
use YandexCheckout\Common\Exceptions\InvalidPropertyValueTypeException;

/**
 * Билдер объектов запросов к API для пролучения списка платежей магазина
 *
 * @package YandexCheckout\Request\Payments
 */
class PaymentsRequestBuilder extends AbstractRequestBuilder
{
    /**
     * @var PaymentsRequest Собираемый объект запроса списка платежей магазина
     */
    protected $currentObject;

    /**
     * Возвращает новый объект запроса для получения списка платежей, который в дальнейшем будет собираться в билдере
     * @return PaymentsRequest Объект запроса списка платежей магазина
     */
    protected function initCurrentObject()
    {
        return new PaymentsRequest();
    }

    /**
     * Устанавливает идентификатор платежа
     * @param string|null $value Идентификатор платежа
     * @return PaymentsRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueException Выбрасывается если длина переданной строки не равна 36 символам
     * @throws InvalidPropertyValueTypeException Выбрасывается если в метод была передана не строка
     */
    public function setPaymentId($value)
    {
        $this->currentObject->setPaymentId($value);
        return $this;
    }

    /**
     * Устанавливает идентификатор магазина
     * @param string|null $value Идентификатор магазина или null чтобы удалить значение
     * @return PaymentsRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueTypeException Выбрасывается если в метод была передана не строка
     */
    public function setAccountId($value)
    {
        $this->currentObject->setAccountId($value);
        return $this;
    }

    /**
     * Устанавливает идентификатор шлюза
     * @param string|null $value Идентификатор шлюза или null чтобы удалить значение
     * @return PaymentsRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueTypeException Выбрасывается если в метод была передана не строка
     */
    public function setGatewayId($value)
    {
        $this->currentObject->setGatewayId($value);
        return $this;
    }

    /**
     * Устанавливает статус выбираемых платежей
     * @param string $value Статус выбираемых платежей или null чтобы удалить значение
     * @return PaymentsRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueException Выбрасывается если переданное значение не является валидным статусом
     * @throws InvalidPropertyValueTypeException Выбрасывается если в метод была передана не строка
     */
    public function setStatus($value)
    {
        $this->currentObject->setStatus($value);
        return $this;
    }

    /**
     * Устанавливает токен следующей страницы выборки
     * @param string $value Токен следующей страницы выборки или null чтобы удалить значение
     * @return PaymentsRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueTypeException Выбрасывается если в метод была передана не строка
     */
    public function setNextPage($value)
    {
        $this->currentObject->setNextPage($value);
        return $this;
    }

    /**
     * Устанавливает дату создания от которой выбираются платежи
     * @param \DateTime|string|int|null $value Время создания, от (не включая) или null чтобы удалить значение
     * @return PaymentsRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueException Генерируется если была передана дата в невалидном формате (была передана
     * строка или число, которые не удалось преобразовать в валидную дату)
     * @throws InvalidPropertyValueTypeException Генерируется если была передана дата с не тем типом (передана не
     * строка, не число и не значение типа \DateTime)
     */
    public function setCreatedGt($value)
    {
        $this->currentObject->setCreatedGt($value);
        return $this;
    }

    /**
     * Устанавливает дату создания от которой выбираются платежи
     * @param \DateTime|string|int|null $value Время создания, от (включительно) или null чтобы удалить значение
     * @return PaymentsRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueException Генерируется если была передана дата в невалидном формате (была передана
     * строка или число, которые не удалось преобразовать в валидную дату)
     * @throws InvalidPropertyValueTypeException Генерируется если была передана дата с не тем типом (передана не
     * строка, не число и не значение типа \DateTime)
     */
    public function setCreatedGte($value)
    {
        $this->currentObject->setCreatedGte($value);
        return $this;
    }

    /**
     * Устанавливает дату создания до которой выбираются платежи
     * @param \DateTime|string|int|null $value Время создания, до (не включая) или null чтобы удалить значение
     * @return PaymentsRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueException Генерируется если была передана дата в невалидном формате (была передана
     * строка или число, которые не удалось преобразовать в валидную дату)
     * @throws InvalidPropertyValueTypeException Генерируется если была передана дата с не тем типом (передана не
     * строка, не число и не значение типа \DateTime)
     */
    public function setCreatedLt($value)
    {
        $this->currentObject->setCreatedLt($value);
        return $this;
    }

    /**
     * Устанавливает дату создания до которой выбираются платежи
     * @param \DateTime|string|int|null $value Время создания, до (включительно) или null чтобы удалить значение
     * @return PaymentsRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueException Генерируется если была передана дата в невалидном формате (была передана
     * строка или число, которые не удалось преобразовать в валидную дату)
     * @throws InvalidPropertyValueTypeException Генерируется если была передана дата с не тем типом (передана не
     * строка, не число и не значение типа \DateTime)
     */
    public function setCreatedLte($value)
    {
        $this->currentObject->setCreatedLte($value);
        return $this;
    }

    /**
     * Устанавливает дату проведения от которой выбираются платежи
     * @param \DateTime|string|int|null $value Время проведения операции, от (не включая) или null чтобы удалить
     * значение
     * @return PaymentsRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueException Генерируется если была передана дата в невалидном формате (была передана
     * строка или число, которые не удалось преобразовать в валидную дату)
     * @throws InvalidPropertyValueTypeException Генерируется если была передана дата с не тем типом (передана не
     * строка, не число и не значение типа \DateTime)
     */
    public function setAuthorizedGt($value)
    {
        $this->currentObject->setAuthorizedGt($value);
        return $this;
    }

    /**
     * Устанавливает дату проведения от которой выбираются платежи
     * @param \DateTime|string|int|null $value Время проведения операции, от (не включая) или null чтобы удалить
     * значение
     * @return PaymentsRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueException Генерируется если была передана дата в невалидном формате (была передана
     * строка или число, которые не удалось преобразовать в валидную дату)
     * @throws InvalidPropertyValueTypeException Генерируется если была передана дата с не тем типом (передана не
     * строка, не число и не значение типа \DateTime)
     */
    public function setAuthorizedGte($value)
    {
        $this->currentObject->setAuthorizedGte($value);
        return $this;
    }

    /**
     * Устанавливает дату проведения до которой выбираются платежи
     * @param \DateTime|string|int|null $value Время проведения, до (не включая) или null чтобы удалить значение
     * @return PaymentsRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueException Генерируется если была передана дата в невалидном формате (была передана
     * строка или число, которые не удалось преобразовать в валидную дату)
     * @throws InvalidPropertyValueTypeException Генерируется если была передана дата с не тем типом (передана не
     * строка, не число и не значение типа \DateTime)
     */
    public function setAuthorizedLt($value)
    {
        $this->currentObject->setAuthorizedLt($value);
        return $this;
    }

    /**
     * Устанавливает дату проведения до которой выбираются платежи
     * @param \DateTime|string|int|null $value Время проведения, до (включительно) или null чтобы удалить значение
     * @return PaymentsRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueException Генерируется если была передана дата в невалидном формате (была передана
     * строка или число, которые не удалось преобразовать в валидную дату)
     * @throws InvalidPropertyValueTypeException Генерируется если была передана дата с не тем типом (передана не
     * строка, не число и не значение типа \DateTime)
     */
    public function setAuthorizedLte($value)
    {
        $this->currentObject->setAuthorizedLte($value);
        return $this;
    }

    /**
     * Собирает и возвращает объект запроса списка платежей магазина
     * @param array|null $options Массив с настройками запроса
     * @return PaymentsRequestInterface Инстанс объекта запроса к API для получения списка плаитежей магазина
     */
    public function build(array $options = null)
    {
        return parent::build($options);
    }
}
