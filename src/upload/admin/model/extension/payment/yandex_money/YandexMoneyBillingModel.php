<?php

require_once DIR_CATALOG . 'model/extension/payment/yandex_money/autoload.php';

class YandexMoneyBillingModel extends \YandexMoneyModule\Model\BillingModel
{
    public function setIsEnabled($value)
    {
        $this->enabled = $value ? true : false;
    }

    public function setFormId($value)
    {
        $this->formId = $value;
    }

    public function setPurpose($value)
    {
        $this->purpose = $value;
    }

    public function setSuccessOrderStatusId($value)
    {
        $this->successOrderStatus = (int)$value;
    }

    public function setMinPaymentAmount($value)
    {
        if ($value < 0) {
            $value = 0;
        }
        $this->minPaymentAmount = (int)$value;
    }

    public function setGeoZoneId($value)
    {
        $this->geoZone = $value;
    }

    public function setDisplayName($value)
    {
        $this->displayName = $value;
    }
}