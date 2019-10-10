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

namespace YandexCheckout\Request\Receipts;

use YandexCheckout\Common\Exceptions\InvalidPropertyValueException;
use YandexCheckout\Common\Exceptions\InvalidPropertyValueTypeException;
use YandexCheckout\Helpers\TypeCast;

/**
 * Class RefundReceipt
 * @package YandexCheckout\Model
 *
 * @property string $refund_id Идентификатор возврата в Яндекс.Кассе.
 * @property string $refundId Идентификатор возврата в Яндекс.Кассе.
 */
class RefundReceiptResponse extends AbstractReceiptResponse
{
    const LENGTH_REFUND_ID = 36;

    private $_refund_id;

    /**
     * Установка свойств, присущих конкретному объекту
     *
     * @param array $receiptData
     *
     * @return void
     */
    public function setSpecificProperties($receiptData)
    {
        $this->setRefundId($this->getObjectId());
    }

    /**
     * @return string
     */
    public function getRefundId()
    {
        return $this->_refund_id;
    }

    /**
     * Устанавливает идентификатор возврата в Яндекс.Кассе
     *
     * @param string $value идентификатор возврата в Яндекс.Кассе
     *
     * @throws InvalidPropertyValueTypeException Выбрасывается если в качестве значения была передана не строка
     * @throws InvalidPropertyValueException Выбрасывается если длина переданной строки не равна 36
     */
    public function setRefundId($value)
    {
        if ($value === null || $value === '') {
            $this->_refund_id = null;
        } elseif (!TypeCast::canCastToString($value)) {
            throw new InvalidPropertyValueTypeException('Invalid refund_id value type', 0, 'Receipt.refundId');
        } elseif (strlen((string)$value) !== self::LENGTH_REFUND_ID) {
            throw new InvalidPropertyValueException(
                'Invalid refund_id value: "'.$value.'"', 0, 'Receipt.refundId', $value
            );
        } else {
            $this->_refund_id = (string)$value;
        }
    }

}