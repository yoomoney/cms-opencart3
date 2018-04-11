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

use YandexCheckout\Common\AbstractRequest;
use YandexCheckout\Model\AmountInterface;
use YandexCheckout\Model\Receipt;
use YandexCheckout\Model\ReceiptInterface;

/**
 * Класс объекта запроса к API на подтверждение оплаты
 *
 * @property AmountInterface $amount Подтверждаемая сумма оплаты
 * @property ReceiptInterface $receipt Данные фискального чека 54-ФЗ
 */
class CreateCaptureRequest extends AbstractRequest implements CreateCaptureRequestInterface
{
    /**
     * @var AmountInterface Подтверждаемая сумма оплаты
     */
    private $_amount;

    /**
     * @var Receipt Данные фискального чека 54-ФЗ
     * @since 1.0.2
     */
    private $_receipt;

    /**
     * Возвращает подтвердаемую сумму оплаты
     * @return AmountInterface Подтверждаемая сумма оплаты
     */
    public function getAmount()
    {
        return $this->_amount;
    }

    /**
     * Проверяет была ли установлена сумма оплаты
     * @return bool True если сумма оплаты была установлена, false если нет
     */
    public function hasAmount()
    {
        return !empty($this->_amount);
    }

    /**
     * Устанавливает сумму оплаты
     * @param AmountInterface $value Подтверждаемая сумма оплаты
     */
    public function setAmount(AmountInterface $value)
    {
        $this->_amount = $value;
    }

    /**
     * Возвращает чек, если он есть
     * @return ReceiptInterface|null Данные фискального чека 54-ФЗ или null если чека нет
     * @since 1.0.2
     */
    public function getReceipt()
    {
        return $this->_receipt;
    }

    /**
     * Устанавливает чек
     * @param ReceiptInterface $value Данные фискального чека 54-ФЗ
     * @since 1.0.2
     */
    public function setReceipt(ReceiptInterface $value)
    {
        $this->_receipt = $value;
    }

    /**
     * Проверяет наличие чека в создаваемом платеже
     * @return bool True если чек есть, false если нет
     * @since 1.0.2
     */
    public function hasReceipt()
    {
        return $this->_receipt !== null;
    }

    /**
     * Удаляет чек из запроса
     * @since 1.0.2
     */
    public function removeReceipt()
    {
        $this->_receipt = null;
    }

    /**
     * Валидирует объект запроса
     * @return bool True если запрос валиден и его можно отправить в API, false если нет
     */
    public function validate()
    {
        if ($this->_amount !== null) {
            $value = $this->_amount->getValue();
            if (empty($value) || $value <= 0.0) {
                $this->setValidationError('Invalid amount value: ' . $value);
                return false;
            }
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
     * Возвращает билдер объектов запросов на подтверждение оплаты
     * @return CreateCaptureRequestBuilder Инстанс билдера
     */
    public static function builder()
    {
        return new CreateCaptureRequestBuilder();
    }
}
