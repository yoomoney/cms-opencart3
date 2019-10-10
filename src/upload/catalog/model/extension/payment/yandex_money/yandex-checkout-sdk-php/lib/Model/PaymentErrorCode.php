<?php

namespace YandexCheckout\Model;

use YandexCheckout\Common\AbstractEnum;

class PaymentErrorCode extends AbstractEnum
{
    const ORDER_REFUSED = 'order_refused';
    const AUTHORIZATION_REJECTED = 'authorization_rejected';
    const PAYMENT_EXPIRED = 'payment_expired';
    const IDENTIFICATION_REQUIRED = 'identification_required';
    const INSUFFICIENT_FUNDS = 'insufficient_funds';
    const PAYER_NOT_FOUND = 'payer_not_found';
    const INAPPROPRIATE_STATUS = 'inappropriate_status';

    protected static $validValues = array(
        self::ORDER_REFUSED => true,
        self::AUTHORIZATION_REJECTED => true,
        self::PAYMENT_EXPIRED => true,
        self::IDENTIFICATION_REQUIRED => true,
        self::INSUFFICIENT_FUNDS => true,
        self::PAYER_NOT_FOUND => true,
        self::INAPPROPRIATE_STATUS => true,
    );
}