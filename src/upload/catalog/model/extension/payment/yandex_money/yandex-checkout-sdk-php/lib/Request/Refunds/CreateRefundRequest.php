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

use YandexCheckout\Common\AbstractRequest;
use YandexCheckout\Common\Exceptions\EmptyPropertyValueException;
use YandexCheckout\Common\Exceptions\InvalidPropertyValueException;
use YandexCheckout\Common\Exceptions\InvalidPropertyValueTypeException;
use YandexCheckout\Helpers\TypeCast;
use YandexCheckout\Model\AmountInterface;
use YandexCheckout\Model\MonetaryAmount;
use YandexCheckout\Model\ReceiptInterface;

/**
 * Класс объекта запроса для создания возврата
 *
 * @property string $paymentId Айди платежа для которого создаётся возврат
 * @property AmountInterface $amount Сумма возврата
 * @property string $comment Комментарий к операции возврата, основание для возврата средств покупателю.
 * @property ReceiptInterface|null $receipt Инстанс чека или null
 */
class CreateRefundRequest extends AbstractRequest implements CreateRefundRequestInterface
{
    /**
     * @var string Айди платежа для которого создаётся возврат
     */
    private $_paymentId;

    /**
     * @var MonetaryAmount Сумма возврата
     */
    private $_amount;

    /**
     * @var string Комментарий к операции возврата, основание для возврата средств покупателю.
     */
    private $_comment;

    /**
     * @var ReceiptInterface|null Чек для печати информации о возврате
     */
    private $_receipt;

    /**
     * Возвращает айди платежа для которого создаётся возврат средств
     * @return string Айди платежа для которого создаётся возврат
     */
    public function getPaymentId()
    {
        return $this->_paymentId;
    }

    /**
     * Устанавливает айди платежа для которого создаётся возврат
     * @param string $value Айди платежа
     *
     * @throws EmptyPropertyValueException Выбрасывается если передано пустое значение айди платежа
     * @throws InvalidPropertyValueException Выбрасывается если переданное значение является строкой, но не является
     * валидным значением айди платежа
     * @throws InvalidPropertyValueTypeException Выбрасывается если передано значение не валидного типа
     */
    public function setPaymentId($value)
    {
        if ($value === null || $value === '') {
            throw new EmptyPropertyValueException(
                'Empty payment id value in CreateRefundRequest', 0, 'CreateRefundRequest.paymentId'
            );
        } elseif (TypeCast::canCastToString($value)) {
            $length = mb_strlen($value, 'utf-8');
            if ($length != 36) {
                throw new InvalidPropertyValueException(
                    'Invalid payment id value in CreateRefundRequest', 0, 'CreateRefundRequest.paymentId', $value
                );
            }
            $this->_paymentId = (string)$value;
        } else {
            throw new InvalidPropertyValueException(
                'Invalid payment id value type in CreateRefundRequest', 0, 'CreateRefundRequest.paymentId', $value
            );
        }
    }

    /**
     * Возвращает сумму возвращаемых средств
     * @return AmountInterface Сумма возврата
     */
    public function getAmount()
    {
        return $this->_amount;
    }

    /**
     * Устанавливает сумму возвращаемых средств
     * @param AmountInterface $value Сумма возврата
     */
    public function setAmount(AmountInterface $value)
    {
        $this->_amount = $value;
    }

    /**
     * Возвращает комментарий к возврату или null, если комментарий не задан
     * @return string Комментарий к операции возврата, основание для возврата средств покупателю.
     */
    public function getComment()
    {
        return $this->_comment;
    }

    /**
     * Проверяет задан ли комментарий к создаваемому возврату
     * @return bool True если комментарий установлен, false если нет
     */
    public function hasComment()
    {
        return $this->_comment !== null;
    }

    /**
     * Устанавливает комментарий к возврату
     * @param string $value Комментарий к операции возврата, основание для возврата средств покупателю
     *
     * @throws InvalidPropertyValueException Выбрасывается если переданная строка длинее 250 символов
     * @throws InvalidPropertyValueTypeException Выбрасывается если была передана не строка
     */
    public function setComment($value)
    {
        if ($value === null || $value === '') {
            $this->_comment = null;
        } elseif (TypeCast::canCastToString($value)) {
            $length = mb_strlen($value, 'utf-8');
            if ($length > 250) {
                throw new InvalidPropertyValueException(
                    'Invalid commend value in CreateRefundRequest', 0, 'CreateRefundRequest.comment', $value
                );
            }
            $this->_comment = (string)$value;
        } else {
            throw new InvalidPropertyValueTypeException(
                'Invalid commend value type in CreateRefundRequest', 0, 'CreateRefundRequest.comment', $value
            );
        }
    }

    /**
     * Возвращает инстанс чека или null если чек не задан
     * @return ReceiptInterface|null Инстанс чека или null
     */
    public function getReceipt()
    {
        return $this->_receipt;
    }

    /**
     * Проверяет задан ли чек
     * @return bool True если чек есть, false если нет
     */
    public function hasReceipt()
    {
        return $this->_receipt !== null && $this->_receipt->notEmpty();
    }

    /**
     * Устанавливает чек для печати
     * @param ReceiptInterface|null $value Инстанс чека или null для удаления информации о чеке
     * @throws InvalidPropertyValueTypeException Выбрасывается если передан не инстанс класса чека и не null
     */
    public function setReceipt($value)
    {
        if ($value === null || $value instanceof ReceiptInterface) {
            $this->_receipt = $value;
        } else {
            throw new InvalidPropertyValueTypeException('Invalid receipt in Refund', 0, 'Refund.receipt', $value);
        }
    }

    /**
     * Валидирует текущий объект запроса
     * @return bool True если текущий объект запроса валиден, false если нет
     */
    public function validate()
    {
        if (empty($this->_paymentId)) {
            $this->setValidationError('Payment id not specified');
            return false;
        }
        if (empty($this->_amount)) {
            $this->setValidationError('Amount not specified');
            return false;
        }
        if ($this->_amount->getValue() <= 0.0) {
            $this->setValidationError('Invalid amount value: ' . $this->_amount->getValue());
            return false;
        }
        if ($this->_receipt !== null && $this->_receipt->notEmpty()) {
            $email = $this->_receipt->getEmail();
            $phone = $this->_receipt->getPhone();
            if (empty($email) && empty($phone)) {
                $this->setValidationError('Both email and phone values are empty in receipt');
                return false;
            }
            if ($this->_receipt->getTaxSystemCode() === null) {
                foreach ($this->_receipt->getItems() as $item) {
                    if ($item->getVatCode() === null) {
                        $this->setValidationError('Item vat_id and receipt tax_system_id not specified');
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * Возвращает билдер объектов текущего типа
     * @return CreateRefundRequestBuilder Инстанс билдера запрсов
     */
    public static function builder()
    {
        return new CreateRefundRequestBuilder();
    }
}
