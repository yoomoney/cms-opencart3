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

namespace YandexCheckout\Request\Refunds;

use YandexCheckout\Common\AbstractRequestBuilder;
use YandexCheckout\Common\Exceptions\EmptyPropertyValueException;
use YandexCheckout\Common\Exceptions\InvalidPropertyValueException;
use YandexCheckout\Common\Exceptions\InvalidPropertyValueTypeException;
use YandexCheckout\Model\AmountInterface;
use YandexCheckout\Model\MonetaryAmount;
use YandexCheckout\Model\Receipt;
use YandexCheckout\Model\ReceiptInterface;
use YandexCheckout\Model\ReceiptItem;
use YandexCheckout\Model\ReceiptItemInterface;

/**
 * Класс билдера запросов к API на создание возврата средств
 *
 * @package YandexCheckout\Request\Refunds
 */
class CreateRefundRequestBuilder extends AbstractRequestBuilder
{
    /**
     * @var CreateRefundRequest Собираемый объет запроса к API
     */
    protected $currentObject;

    /**
     * @var MonetaryAmount Сумма возвращаемых средств
     */
    private $amount;

    /**
     * @var Receipt Инстанс чека
     */
    private $receipt;

    /**
     * Возвращает новый объект для сборки
     * @return CreateRefundRequest Собираемый объет запроса к API
     */
    protected function initCurrentObject()
    {
        $request = new CreateRefundRequest();
        $this->amount = new MonetaryAmount();
        $this->receipt = new Receipt();
        return $request;
    }

    /**
     * Устанавливает айди платежа для которого создаётся возврат
     * @param string $value Айди платежа
     * @return CreateRefundRequestBuilder Инстанс текущего билдера
     *
     * @throws EmptyPropertyValueException Выбрасывается если передано пустое значение айди платежа
     * @throws InvalidPropertyValueException Выбрасывается если переданное значение является строкой, но не является
     * валидным значением айди платежа
     * @throws InvalidPropertyValueTypeException Выбрасывается если передано значение не валидного типа
     */
    public function setPaymentId($value)
    {
        $this->currentObject->setPaymentId($value);
        return $this;
    }

    /**
     * Устанавливает сумму возвращаемых средств
     * @param AmountInterface|array $value Сумма возврата
     * @return CreateRefundRequestBuilder Инстанс текущего билдера
     *
     * @throws EmptyPropertyValueException Генерируется если было передано пустое значение
     * @throws InvalidPropertyValueTypeException Генерируется если было передано значение невалидного типа
     * @throws InvalidPropertyValueException Генерируется если было передано не валидное значение
     */
    public function setAmount($value)
    {
        if ($value instanceof AmountInterface) {
            $this->amount->setValue($value->getValue());
            $this->amount->setCurrency($value->getCurrency());
        } elseif (is_array($value)) {
            $this->amount->fromArray($value);
        } else {
            $this->amount->setValue($value);
        }
        return $this;
    }

    /**
     * Устанавливает валюту в которой средства возвращаются
     * @param string $value Код валюты
     * @return CreateRefundRequestBuilder Инстанс текущего билдера
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
     * Устанавливает комментарий к возврату
     * @param string $value Комментарий к возврату
     * @return CreateRefundRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueException Выбрасывается если переданная строка длинее 250 символов
     * @throws InvalidPropertyValueTypeException Выбрасывается если была передана не строка
     */
    public function setComment($value)
    {
        $this->currentObject->setComment($value);
        return $this;
    }

    /**
     * Устанавливает чек для проведения возврата
     * @param ReceiptInterface|array $value Инстанс чека и массив с его описанием
     */
    public function setReceipt($value)
    {
        if (is_array($value)) {
            $this->receipt->fromArray($value);
        } elseif ($value instanceof ReceiptInterface) {
            $this->receipt = clone $value;
        } else {
            throw new InvalidPropertyValueTypeException('Invalid receipt value type', 0, 'receipt', $value);
        }
    }

    /**
     * Устанавлвиает список товаров в заказе для создания чека
     * @param array $value Массив товаров в заказе
     * @return CreateRefundRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueException Генерируется если хотя бы один из товаров имеет неверную структуру
     */
    public function setReceiptItems($value)
    {
        $this->receipt->setItems(array());
        $index = 0;
        foreach ($value as $item) {
            if ($item instanceof ReceiptItemInterface) {
                $this->receipt->addItem($item);
            } else {
                if (empty($item['title']) && empty($item['description'])) {
                    throw new InvalidPropertyValueException(
                        'Item#' . $index . ' title or description not specified',
                        0,
                        'CreatePaymentRequest.items[' . $index . '].title',
                        json_encode($item)
                    );
                }
                if (empty($item['price'])) {
                    throw new InvalidPropertyValueException(
                        'Item#' . $index . ' price not specified',
                        0,
                        'CreatePaymentRequest.items[' . $index . '].price',
                        json_encode($item)
                    );
                }
                $this->addReceiptItem(
                    empty($item['title']) ? $item['description'] : $item['title'],
                    $item['price'],
                    empty($item['quantity']) ? 1.0 : $item['quantity'],
                    empty($item['vatCode']) ? null : $item['vatCode']
                );
            }
            $index++;
        }
        return $this;
    }

    /**
     * Добавляет в чек товар
     * @param string $title Название или описание товара
     * @param string $price Цена товара в валюте, заданной в заказе
     * @param float $quantity Количество покупаемого товара
     * @param int|null $vatCode Ставка НДС, или null если используется ставка НДС заказа
     * @return CreateRefundRequestBuilder Инстанс текущего билдера
     */
    public function addReceiptItem($title, $price, $quantity = 1.0, $vatCode = null)
    {
        $item = new ReceiptItem();
        $item->setDescription($title);
        $item->setQuantity($quantity);
        $item->setVatCode($vatCode);
        $item->setPrice(new MonetaryAmount($price, $this->amount->getCurrency()));
        $this->receipt->addItem($item);
        return $this;
    }

    /**
     * Добавляет в чек доставку товара
     * @param string $title Название доставки в чеке
     * @param string $price Стоимость доставки
     * @param int|null $vatCode Ставка НДС, или null если используется ставка НДС заказа
     * @return CreateRefundRequestBuilder Инстанс текущего билдера
     */
    public function addReceiptShipping($title, $price, $vatCode = null)
    {
        $item = new ReceiptItem();
        $item->setDescription($title);
        $item->setQuantity(1);
        $item->setVatCode($vatCode);
        $item->setIsShipping(true);
        $item->setPrice(new MonetaryAmount($price, $this->amount->getCurrency()));
        $this->receipt->addItem($item);
        return $this;
    }

    /**
     * Устанавливает адрес электронной почты получателя чека
     * @param string $value Email получателя чека
     * @return CreateRefundRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueTypeException Генерируется если было передано значение невалидного типа
     */
    public function setReceiptEmail($value)
    {
        $this->receipt->setEmail($value);
        return $this;
    }

    /**
     * Устанавливает телефон получателя чека
     * @param string $value Телефон получателя чека
     * @return CreateRefundRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueException Генерируется если был передан не телефон, а что-то другое
     * @throws InvalidPropertyValueTypeException Генерируется если было передано значение невалидного типа
     */
    public function setReceiptPhone($value)
    {
        $this->receipt->setPhone($value);
        return $this;
    }

    /**
     * Устанавливает код системы налогообложения.
     * @param int $value Код системы налогообложения. Число 1-6.
     * @return CreateRefundRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueTypeException Выбрасывается если переданный аргумент - не число
     * @throws InvalidPropertyValueException Выбрасывается если переданный аргумент меньше одного или больше шести
     */
    public function setTaxSystemCode($value)
    {
        $this->receipt->setTaxSystemCode($value);
        return $this;
    }

    /**
     * Строит объект запроса к API
     * @param array|null $options Устаналвиваемые параметры запроса
     * @return CreateRefundRequestInterface Инстанс сгенерированного объекта запроса к API
     */
    public function build(array $options = null)
    {
        if (!empty($options)) {
            $this->setOptions($options);
        }
        $this->currentObject->setAmount($this->amount);
        if ($this->receipt->notEmpty()) {
            $this->currentObject->setReceipt($this->receipt);
        }
        return parent::build();
    }
}