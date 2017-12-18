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
 * PaymentDataYandexWallet
 * Платежные данные для проведения оплаты при помощи Яндекс Денег
 * @property string $phone Номер телефона в формате ITU-T E.164 на который зарегестрирован аккаунт в Яндекс Денгах. Необходим для оплаты `waiting` сценарием
 * @property string $accountNumber Номер кошелька в Яндекс.Деньгах, из которого спишутся деньги при оплате. Необходим для оплаты `waiting` сценарием
 */
class PaymentDataYandexWallet extends AbstractPaymentData
{
    /**
     * @var string Номер телефона в формате ITU-T E.164 на который зарегестрирован аккаунт в Яндекс Денгах. Необходим для оплаты `waiting` сценарием
     */
    private $_phone;

    /**
     * @var string Номер кошелька в Яндекс.Деньгах, из которого спишутся деньги при оплате. Необходим для оплаты `waiting` сценарием
     */
    private $_accountNumber;

    public function __construct()
    {
        $this->_setType(PaymentMethodType::YANDEX_MONEY);
    }

    /**
     * @return string Номер телефона в формате ITU-T E.164 на который зарегестрирован аккаунт в Яндекс Денгах. Необходим для оплаты `waiting` сценарием
     */
    public function getPhone()
    {
        return $this->_phone;
    }

    /**
     * @param string $value Номер телефона в формате ITU-T E.164 на который зарегестрирован аккаунт в Яндекс Денгах. Необходим для оплаты `waiting` сценарием
     */
    public function setPhone($value)
    {
        if ($value === null || $value === '') {
            throw new EmptyPropertyValueException('Empty phone value', 0, 'PaymentDataYandexWallet.phone');
        } elseif (TypeCast::canCastToString($value)) {
            if (preg_match('/^[0-9]{4,15}$/', $value)) {
                $this->_phone = (string)$value;
            } else {
                throw new InvalidPropertyValueException(
                    'Invalid phone value', 0, 'PaymentDataYandexWallet.phone', $value
                );
            }
        } else {
            throw new InvalidPropertyValueTypeException(
                'Invalid phone value type', 0, 'PaymentDataYandexWallet.phone', $value
            );
        }
    }

    /**
     * @return string Номер кошелька в Яндекс.Деньгах, из которого спишутся деньги при оплате. Необходим для оплаты `waiting` сценарием
     */
    public function getAccountNumber()
    {
        return $this->_accountNumber;
    }

    /**
     * @param string $value Номер кошелька в Яндекс.Деньгах, из которого спишутся деньги при оплате. Необходим для оплаты `waiting` сценарием
     */
    public function setAccountNumber($value)
    {
        if ($value === null || $value === '') {
            throw new EmptyPropertyValueException(
                'Empty accountNumber value', 0, 'PaymentDataYandexWallet.accountNumber'
            );
        } elseif (TypeCast::canCastToString($value)) {
            if (preg_match('/^[0-9]{11,33}$/', $value)) {
                $this->_accountNumber = (string)$value;
            } else {
                throw new InvalidPropertyValueException(
                    'Invalid accountNumber value', 0, 'PaymentDataYandexWallet.accountNumber', $value
                );
            }
        } else {
            throw new InvalidPropertyValueTypeException(
                'Invalid accountNumber value type', 0, 'PaymentDataYandexWallet.accountNumber', $value
            );
        }
    }
}
