<?php

namespace YandexCheckout\Model;

use YandexCheckout\Common\AbstractObject;
use YandexCheckout\Common\Exceptions\EmptyPropertyValueException;
use YandexCheckout\Common\Exceptions\InvalidPropertyValueException;
use YandexCheckout\Common\Exceptions\InvalidPropertyValueTypeException;
use YandexCheckout\Helpers\TypeCast;

/**
 * PaymentError - Отказ в проведении платежа или операции над платежом.
 * Код ошибки | Описание
 * --- | ---
 * order_refused | Магазин отказался принять оплату по этому заказу или нет ни одного доступного метода оплаты этого заказа.
 * authorization_rejected | Отказ в проведении платежа. Возможные причины:
 *   * транзакция с текущими параметрами запрещена для этого пользователя;
 *   * превышен лимит для этого пользователя;
 *   * банк-эмитент отклонил транзакцию по карте;
 *   * истек срок действия банковской карты;
 *   * отказ в совершении операции от сторонней системы, например, если покупатель не отправил смс с подтверждением оплаты за отведенное для этого время. |
 * payment_expired | Истекло отведенное для оплаты заказа время.
 * identification_required | Для оплаты этого заказа необходима идентификация покупателя.
 * insufficient_funds | Недостаточно средств для оплаты заказа.
 * payer_not_found | При оплате заказа с помощью стороннего сервиса заказ отвергнут, учетная запись покупателя не найдена в стороннем сервисе. Например, не найдена учетная запись пользователя в Сбербанке Онлайн.
 * inappropriate_status | Состояние платежа не позволяет провести запрошенную операцию (клиринг, отмену)
 * 
 * @property string $code Код ошибки
 * @property string $description Дополнительное текстовое пояснение ошибки
 */
class PaymentError extends AbstractObject implements PaymentErrorInterface
{
    /**
     * @var string Код ошибки
     */
    private $_code;

    /**
     * @var string Дополнительное текстовое пояснение ошибки
     */
    private $_description;

    /**
     * @return string Код ошибки
     */
    public function getCode()
    {
        return $this->_code;
    }

    /**
     * @param string $value Код ошибки
     */
    public function setCode($value)
    {
        if ($value === null || $value === '') {
            throw new EmptyPropertyValueException('Empty code value in PaymentError', 0, 'PaymentError.code');
        } elseif (TypeCast::canCastToEnumString($value)) {
            $castedValue = (string)$value;
            if (PaymentErrorCode::valueExists($castedValue)) {
                $this->_code = $castedValue;
            } else {
                throw new InvalidPropertyValueException(
                    'Invalid code value in PaymentError', 0, 'PaymentError.code', $value
                );
            }
        } else {
            throw new InvalidPropertyValueTypeException(
                'Invalid code value type in PaymentError', 0, 'PaymentError.code', $value
            );
        }
    }

    /**
     * @return string Дополнительное текстовое пояснение ошибки
     */
    public function getDescription()
    {
        return $this->_description;
    }

    /**
     * @param string $value Дополнительное текстовое пояснение ошибки
     */
    public function setDescription($value)
    {
        if ($value === null || $value === '') {
            $this->_description = null;
        } elseif (TypeCast::canCastToString($value)) {
            $this->_description = (string)$value;
        } else {
            throw new InvalidPropertyValueTypeException(
                'Invalid description value type in PaymentError', 0, 'PaymentError.description', $value
            );
        }
    }
}
