<?php

namespace YandexCheckout\Common\Exceptions;


class TechnicalError extends ApiException
{
    const HTTP_CODE = 500;

    public function __construct($responseHeaders = array(), $responseBody = null)
    {
        $errorData = json_decode($responseBody, true);
        $message = sprintf('%s. Error code: %s', $errorData['description'], $errorData['code']);
        parent::__construct($message, self::HTTP_CODE, $responseHeaders, $responseBody);
    }
}