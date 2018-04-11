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

namespace YandexCheckout\Request\Payments\Payment;

use YandexCheckout\Common\AbstractRequestBuilder;
use YandexCheckout\Common\Exceptions\EmptyPropertyValueException;
use YandexCheckout\Common\Exceptions\InvalidPropertyException;
use YandexCheckout\Common\Exceptions\InvalidPropertyValueException;
use YandexCheckout\Common\Exceptions\InvalidPropertyValueTypeException;
use YandexCheckout\Common\Exceptions\InvalidRequestException;
use YandexCheckout\Model\AmountInterface;
use YandexCheckout\Model\MonetaryAmount;
use YandexCheckout\Model\Receipt;
use YandexCheckout\Model\ReceiptInterface;
use YandexCheckout\Model\ReceiptItem;
use YandexCheckout\Model\ReceiptItemInterface;

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
     * @var Receipt Объект с информацией о чеке
     * @since 1.0.2
     */
    private $receipt;

    /**
     * @return CreateCaptureRequest
     */
    protected function initCurrentObject()
    {
        $this->amount = new MonetaryAmount();
        $this->receipt = new Receipt();
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
            if ($this->amount->getValue() > 0) {
                $this->amount = new MonetaryAmount();
            }
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
     * Устанавливает чек
     * @param ReceiptInterface|array $value Инстанс чека или ассоциативный массив с данными чека
     * @since 1.0.2
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
     * @return CreateCaptureRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueException Генерируется если хотя бы один из товаров имеет неверную структуру
     *
     * @since 1.0.2
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
     * @return CreateCaptureRequestBuilder Инстанс текущего билдера
     * @since 1.0.2
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
     * @return CreateCaptureRequestBuilder Инстанс текущего билдера
     * @since 1.0.2
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
     * @return CreateCaptureRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueTypeException Генерируется если было передано значение невалидного типа
     *
     * @since 1.0.2
     */
    public function setReceiptEmail($value)
    {
        $this->receipt->setEmail($value);
        return $this;
    }

    /**
     * Устанавливает телефон получателя чека
     * @param string $value Телефон получателя чека
     * @return CreateCaptureRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueException Генерируется если был передан не телефон, а что-то другое
     * @throws InvalidPropertyValueTypeException Генерируется если было передано значение невалидного типа
     *
     * @since 1.0.2
     */
    public function setReceiptPhone($value)
    {
        $this->receipt->setPhone($value);
        return $this;
    }

    /**
     * Устанавливает код системы налогообложения.
     * @param int $value Код системы налогообложения. Число 1-6.
     * @return CreateCaptureRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueTypeException Выбрасывается если переданный аргумент - не число
     * @throws InvalidPropertyValueException Выбрасывается если переданный аргумент меньше одного или больше шести
     *
     * @since 1.0.2
     */
    public function setTaxSystemCode($value)
    {
        $this->receipt->setTaxSystemCode($value);
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
        if ($this->amount->getValue() > 0) {
            $this->currentObject->setAmount($this->amount);
        }
        if ($this->receipt->notEmpty()) {
            $this->currentObject->setReceipt($this->receipt);
        }
        return parent::build();
    }
}
