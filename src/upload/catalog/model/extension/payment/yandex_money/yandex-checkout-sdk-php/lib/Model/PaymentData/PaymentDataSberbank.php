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

namespace YandexCheckout\Model\PaymentData;

use YandexCheckout\Common\Exceptions\EmptyPropertyValueException;
use YandexCheckout\Common\Exceptions\InvalidPropertyValueException;
use YandexCheckout\Common\Exceptions\InvalidPropertyValueTypeException;
use YandexCheckout\Helpers\TypeCast;
use YandexCheckout\Model\PaymentMethodType;

/**
 * PaymentDataSberbank
 * Платежные данные для проведения оплаты при помощи Сбербанк Онлайн.
 * @property string $phone
 * @property string $bindId Идентификатор привязки клиента СБОЛ.
 */
class PaymentDataSberbank extends AbstractPaymentData
{
    /**
     * Номер телефона в формате ITU-T E.164 на который зарегистрирован аккаунт в Сбербанк Онлайн. Необходим для оплаты `waiting` сценарием
     * @var string
     */
    private $_phone;

    /**
     * Необходим для безакцептной оплаты привязкой созданной через deep link приложения Сбербанк Онлайн.
     * @var string Идентификатор привязки клиента СБОЛ.
     */
    private $_bindId;

    public function __construct()
    {
        $this->_setType(PaymentMethodType::SBERBANK);
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->_phone;
    }

    /**
     * @param string $value
     */
    public function setPhone($value)
    {
        if ($value === null || $value === '') {
            throw new EmptyPropertyValueException('Empty phone value', 0, 'PaymentDataSberbank.phone');
        } elseif (TypeCast::canCastToString($value)) {
            if (preg_match('/^[0-9]{4,15}$/', $value)) {
                $this->_phone = (string)$value;
            } else {
                throw new InvalidPropertyValueException(
                    'Invalid phone value', 0, 'PaymentDataSberbank.phone', $value
                );
            }
        } else {
            throw new InvalidPropertyValueTypeException(
                'Invalid phone value type', 0, 'PaymentDataSberbank.phone', $value
            );
        }
    }

    /**
     * @return string Идентификатор привязки клиента СБОЛ.
     */
    public function getBindId()
    {
        return $this->_bindId;
    }

    /**
     * @param string $value Идентификатор привязки клиента СБОЛ.
     */
    public function setBindId($value)
    {
        if ($value === null || $value === '') {
            throw new EmptyPropertyValueException('Empty bindId value', 0, 'PaymentDataSberbank.bindId');
        } elseif (TypeCast::canCastToString($value)) {
            $this->_bindId = (string)$value;
        } else {
            throw new InvalidPropertyValueTypeException(
                'Invalid bindId value type', 0, 'PaymentDataSberbank.bindId', $value
            );
        }
    }
}
