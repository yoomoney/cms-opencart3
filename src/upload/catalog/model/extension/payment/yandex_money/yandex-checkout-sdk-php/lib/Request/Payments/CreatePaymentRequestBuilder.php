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
use YandexCheckout\Common\Exceptions\EmptyPropertyValueException;
use YandexCheckout\Common\Exceptions\InvalidPropertyValueException;
use YandexCheckout\Common\Exceptions\InvalidPropertyValueTypeException;
use YandexCheckout\Common\Exceptions\InvalidRequestException;
use YandexCheckout\Model\AmountInterface;
use YandexCheckout\Model\ConfirmationAttributes\AbstractConfirmationAttributes;
use YandexCheckout\Model\ConfirmationAttributes\ConfirmationAttributesFactory;
use YandexCheckout\Model\Metadata;
use YandexCheckout\Model\MonetaryAmount;
use YandexCheckout\Model\PaymentData\AbstractPaymentData;
use YandexCheckout\Model\PaymentData\PaymentDataFactory;
use YandexCheckout\Model\Receipt;
use YandexCheckout\Model\ReceiptInterface;
use YandexCheckout\Model\ReceiptItem;
use YandexCheckout\Model\ReceiptItemInterface;
use YandexCheckout\Model\Recipient;
use YandexCheckout\Model\RecipientInterface;

/**
 * Класс билдера объектов запрсов к API на создание платежа
 *
 * @package YandexCheckout\Request\Payments
 */
class CreatePaymentRequestBuilder extends AbstractRequestBuilder
{
    /**
     * @var CreatePaymentRequest Собираемый объект запроса
     */
    protected $currentObject;

    /**
     * @var Recipient Получатель платежа
     */
    private $recipient;

    /**
     * @var Receipt Объект с информацией о чеке
     */
    private $receipt;

    /**
     * @var MonetaryAmount Сумма заказа
     */
    private $amount;

    /**
     * @var PaymentDataFactory Фабрика методов проведения платежей
     */
    private $paymentDataFactory;

    /**
     * @var ConfirmationAttributesFactory Фабрика объектов методов подтверждения платежей
     */
    private $confirmationFactory;

    /**
     * Инициализирует объект запроса, который в дальнейшем будет собираться билдером
     * @return CreatePaymentRequest Инстанс собираемого объекта запроса к API
     */
    protected function initCurrentObject()
    {
        $request = new CreatePaymentRequest();

        $this->recipient = new Recipient();
        $this->receipt = new Receipt();
        $this->amount = new MonetaryAmount();

        return $request;
    }

    /**
     * Устанавливает идентификатор магазина получателя платежа
     * @param string $value Идентификатор магазина
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
     *
     * @throws EmptyPropertyValueException Выбрасывается если было передано пустое значение
     * @throws InvalidPropertyValueTypeException Выбрасывается если было передано не строковое значение
     */
    public function setAccountId($value)
    {
        $this->recipient->setAccountId($value);
        return $this;
    }

    /**
     * Устанавливает идентификатор шлюза
     * @param string $value Идентификатор шлюза
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
     *
     * @throws EmptyPropertyValueException Выбрасывается если было передано пустое значение
     * @throws InvalidPropertyValueTypeException Выбрасывается если было передано не строковое значение
     */
    public function setGatewayId($value)
    {
        $this->recipient->setGatewayId($value);
        return $this;
    }

    /**
     * Устанавливает получателя платежа из объекта или ассоциативного массива
     * @param RecipientInterface|array $value Получатель платежа
     * @throws InvalidPropertyValueTypeException Выбрасывается если передан аргумент не валидного типа
     */
    public function setRecipient($value)
    {
        if (is_array($value)) {
            $this->recipient->fromArray($value);
        } elseif ($value instanceof RecipientInterface) {
            $this->recipient->setAccountId($value->getAccountId());
            $this->recipient->setGatewayId($value->getGatewayId());
        } else {
            throw new InvalidPropertyValueTypeException('Invalid recipient value', 0, 'recipient', $value);
        }
    }

    /**
     * Устанавливает сумму заказа
     * @param AmountInterface|string|array $value Сумма заказа
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
     *
     * @throws EmptyPropertyValueException Выбрасывается если было передано пустое значение
     * @throws InvalidPropertyValueException Выбрасывается если был передан ноль или отрицательное значение
     * @throws InvalidPropertyValueTypeException Выбрасывается если было передано не строковое значение
     */
    public function setAmount($value)
    {
        if ($value instanceof AmountInterface) {
            $this->amount->setValue($value->getValue());
            $this->amount->setCurrency($value->getCurrency());
        } elseif ($value === null || $value === '') {
            throw new EmptyPropertyValueException('Empty payment amount value', 0, 'CreatePaymentRequest.amount');
        } elseif (is_array($value)) {
            $this->amount->fromArray($value);
        } elseif (!is_numeric($value)) {
            throw new InvalidPropertyValueTypeException(
                'Invalid payment amount value type', 0, 'CreatePaymentRequest.amount', $value
            );
        } elseif ($value > 0) {
            $this->amount->setValue($value);
        } else {
            throw new InvalidPropertyValueException(
                'Invalid payment amount value', 0, 'CreatePaymentRequest.amount', $value
            );
        }
        return $this;
    }

    /**
     * Устанавливает валюту в которой заказ оплачивается
     * @param string $value Код валюты заказа
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
     *
     * @throws EmptyPropertyValueException Генерируется если было передано пустое значение
     * @throws InvalidPropertyValueTypeException Генерируется если было передано значение невалидного типа
     * @throws InvalidPropertyValueException Генерируется если был передан неподдерживаемый код валюты
     */
    public function setCurrency($value)
    {
        $this->amount->setCurrency($value);
        foreach ($this->receipt->getItems() as $item) {
            $item->getPrice()->setCurrency($value);
        }
        return $this;
    }

    /**
     * Устанавливает чек
     * @param ReceiptInterface|array $value Инстанс чека или ассоциативный массив с данными чека
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
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
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
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
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
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
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
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
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
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
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
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
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
     * Устанавливает одноразовый токен для проведения оплаты
     * @param string $value Одноразовый токен для проведения оплаты
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueException Выбрасывается если переданное значение превышает допустимую длину
     * @throws InvalidPropertyValueTypeException Выбрасывается если переданное значение не является строкой
     */
    public function setPaymentToken($value)
    {
        $this->currentObject->setPaymentToken($value);
        return $this;
    }

    /**
     * Устанавливает идентификатор записи о сохранённых данных покупателя
     * @param string $value Идентификатор записи о сохраненных платежных данных покупателя
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueTypeException Генерируется если переданные значение не является строкой или null
     */
    public function setPaymentMethodId($value)
    {
        $this->currentObject->setPaymentMethodId($value);
        return $this;
    }

    /**
     * Устанавливает объект с информацией для создания метода оплаты
     * @param AbstractPaymentData|string|array|null $value Объект с создания метода оплаты или null
     * @param array $options Настройки способа оплаты в виде ассоциативного массива
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueTypeException Выбрасывается если был передан объект невалидного типа
     */
    public function setPaymentMethodData($value, array $options = null)
    {
        if (is_string($value) && $value !== '') {
            if (empty($options)) {
                $value = $this->getPaymentDataFactory()->factory($value);
            } else {
                $value = $this->getPaymentDataFactory()->factoryFromArray($options, $value);
            }
        } elseif (is_array($value)) {
            $value = $this->getPaymentDataFactory()->factoryFromArray($value);
        }
        $this->currentObject->setPaymentMethodData($value);
        return $this;
    }

    /**
     * Устанавливает способ подтверждения платежа
     * @param AbstractConfirmationAttributes|string|array|null $value Способ подтверждения платежа
     * @param array|null $options Настройки способа подтверждения платежа в виде массива
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueTypeException Выбрасывается если переданное значение не является объектом типа
     * AbstractConfirmationAttributes или null
     */
    public function setConfirmation($value, array $options = null)
    {
        if (is_string($value) && $value !== '') {
            if (empty($options)) {
                $value = $this->getConfirmationFactory()->factory($value);
            } else {
                $value = $this->getConfirmationFactory()->factoryFromArray($options, $value);
            }
        } elseif (is_array($value)) {
            $value = $this->getConfirmationFactory()->factoryFromArray($value);
        }
        $this->currentObject->setConfirmation($value);
        return $this;
    }

    /**
     * Устанавливает флаг сохранения платёжных данных. Значение true инициирует создание многоразового payment_method.
     * @param bool $value Сохранить платежные данные для последующего использования
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueTypeException Генерируется если переданный аргумент не кастится в bool
     */
    public function setSavePaymentMethod($value)
    {
        $this->currentObject->setSavePaymentMethod($value);
        return $this;
    }

    /**
     * Устанавливает флаг автоматического принятия поступившей оплаты
     * @param bool $value Автоматически принять поступившую оплату
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueTypeException Генерируется если переданный аргумент не кастится в bool

     */
    public function setCapture($value)
    {
        $this->currentObject->setCapture($value);
        return $this;
    }

    /**
     * Устанавливает IP адрес покупателя
     * @param string $value IPv4 или IPv6-адрес покупателя
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueTypeException Выбрасывается если переданный аргумент не является строкой
     */
    public function setClientIp($value)
    {
        $this->currentObject->setClientIp($value);
        return $this;
    }

    /**
     * Устанавливает метаданные, привязанные к платежу
     * @param Metadata|array|null $value Метаданные платежа, устанавливаемые мерчантом
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueTypeException Выбрасывается если переданные данные не удалось интерпретировать как
     * метаданные платежа
     */
    public function setMetadata($value)
    {
        $this->currentObject->setMetadata($value);
        return $this;
    }

    /**
     * Устанавливает описание транзакции
     * @param string $value Описание транзакции
     * @return CreatePaymentRequestBuilder Инстанс текущего билдера
     *
     * @throws InvalidPropertyValueException Выбрасывается если переданное значение превышает допустимую длину
     * @throws InvalidPropertyValueTypeException Выбрасывается если переданное значение не является строкой
     */
    public function setDescription($value)
    {
        $this->currentObject->setDescription($value);
        return $this;
    }

    /**
     * Строит и возвращает объект запроса для отправки в API яндекс денег
     * @param array|null $options Массив параметров для установки в объект запроса
     * @return CreatePaymentRequestInterface Инстанс объекта запроса
     *
     * @throws InvalidRequestException Выбрасывается если собрать объект запроса не удалось
     */
    public function build(array $options = null)
    {
        if (!empty($options)) {
            $this->setOptions($options);
        }
        $accountId = $this->recipient->getAccountId();
        $gatewayId = $this->recipient->getGatewayId();
        if (!empty($accountId) && !empty($gatewayId)) {
            $this->currentObject->setRecipient($this->recipient);
        }
        if ($this->receipt->notEmpty()) {
            $this->currentObject->setReceipt($this->receipt);
        }
        $this->currentObject->setAmount($this->amount);
        return parent::build();
    }

    /**
     * Возвращает фабрику методов проведения платежей
     * @return PaymentDataFactory Фабрика методов проведения платежей
     */
    protected function getPaymentDataFactory()
    {
        if ($this->paymentDataFactory === null) {
            $this->paymentDataFactory = new PaymentDataFactory();
        }
        return $this->paymentDataFactory;
    }

    /**
     * Возвращает фабрику для создания методов подтверждения платежей
     * @return ConfirmationAttributesFactory Фабрика объектов методов подтверждения платежей
     */
    protected function getConfirmationFactory()
    {
        if ($this->confirmationFactory === null) {
            $this->confirmationFactory = new ConfirmationAttributesFactory();
        }
        return $this->confirmationFactory;
    }
}