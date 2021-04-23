<?php

require_once DIR_CATALOG.'model/extension/payment/yoomoney/autoload.php';

class YooMoneyKassaModel extends \YooMoneyModule\Model\KassaModel
{
    private $invoiceEnable;
    private $invoiceSubject;
    private $invoiceMessage;
    private $invoiceLogo;
    /** @var \YooKassa\Client */
    private $apiClient;

    public function __construct(Config $config)
    {
        parent::__construct($config);

        $this->invoiceEnable  = (bool)$config->get('yoomoney_kassa_invoice');
        $this->invoiceSubject = $config->get('yoomoney_kassa_invoice_subject');
        $this->invoiceMessage = $config->get('yoomoney_kassa_invoice_message');
        $this->invoiceLogo    = (bool)$config->get('yoomoney_kassa_invoice_logo');
    }

    /**
     * @param $apiClient
     */
    public function setApiClient($apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function setIsEnabled($value)
    {
        $this->enabled = $value ? true : false;
    }

    public function setShopId($value)
    {
        $this->shopId = $value;
        $this->apiClient->getApiClient()->setShopId($value);
    }

    public function setPassword($value)
    {
        $this->password = $value;
        $this->apiClient->getApiClient()->setShopPassword($value);
    }

    public function setEpl($value)
    {
        $this->epl = $value ? true : false;
    }

    public function setUseInstallmentsButton($value)
    {
        $this->useInstallmentsButton = (bool)$value;
    }

    public function setPaymentMethodFlag($paymentMethod, $value)
    {
        if (array_key_exists($paymentMethod, $this->paymentMethods)) {
            $this->paymentMethods[$paymentMethod] = $value ? true : false;
        }
    }

    public function setSendReceipt($value)
    {
        $this->isSendReceipt = $value ? true : false;
    }

    public function setDefaultTaxRate($value)
    {
        if (!in_array($value, $this->getTaxRateList())) {
            $value = 1;
        }
        $this->defaultTaxRate = (int)$value;
    }

    public function setDefaultTaxSystemCode($value)
    {
        if (!in_array($value, $this->getTaxSystemCodeList())) {
            $value = 0;
        }
        $this->defaultTaxSystemCode = (int)$value;
    }

    public function setTaxRates($taxRates)
    {
        $all            = $this->getTaxRateList();
        $this->taxRates = array();
        foreach ($taxRates as $shopTaxRateId => $taxRate) {
            if (in_array($taxRate, $all)) {
                $this->taxRates[$shopTaxRateId] = (int)$taxRate;
            }
        }
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

    public function setDebugLog($value)
    {
        $this->log = $value ? true : false;
    }

    public function setDisplayName($value)
    {
        $this->displayName = $value;
    }

    /**
     * @return bool
     */
    public function isInvoicesEnabled()
    {
        return $this->invoiceEnable;
    }

    /**
     * @param bool $value
     */
    public function setInvoicesEnabled($value)
    {
        $this->invoiceEnable = $value;
    }

    /**
     * @return string
     */
    public function getInvoiceSubject()
    {
        return $this->invoiceSubject;
    }

    /**
     * @param string $value
     */
    public function setInvoiceSubject($value)
    {
        $this->invoiceSubject = $value;
    }

    /**
     * @return string
     */
    public function getInvoiceMessage()
    {
        return $this->invoiceMessage;
    }

    /**
     * @param string $value
     */
    public function setInvoiceMessage($value)
    {
        $this->invoiceMessage = $value;
    }

    /**
     * @return bool
     */
    public function getSendInvoiceLogo()
    {
        return $this->invoiceLogo;
    }

    /**
     * @param bool $value
     */
    public function setSendInvoiceLogo($value)
    {
        $this->invoiceLogo = $value;
    }

    /**
     * @param bool $value
     */
    public function setCreateOrderBeforeRedirect($value)
    {
        $this->createOrderBeforeRedirect = (bool)$value;
    }

    /**
     * @param bool $value
     */
    public function setClearCartBeforeRedirect($value)
    {
        $this->clearCartAfterOrderCreation = (bool)$value;
    }

    /**
     * @return bool
     */
    public function checkConnection()
    {
        try {
            $this->apiClient->getPaymentInfo('00000000-0000-0000-0000-000000000001');
        } catch (\YooKassa\Common\Exceptions\NotFoundException $e) {
            return true;
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @param bool $value
     */
    public function setShowLinkInFooter($value)
    {
        $this->showInFooter = $value ? true : false;
    }


    /**
     * @param $value
     */
    public function setB2bSberbankEnabled($value)
    {
        $this->b2bSberbankEnabled = $value;
    }

    /**
     * @param $value
     */
    public function setB2bSberbankPaymentPurpose($value)
    {
        $this->b2bSberbankPaymentPurpose = $value;
    }


    /**
     * @param $value
     */
    public function setB2bSberbankDefaultTaxRate($value)
    {
        $this->b2bSberbankDefaultTaxRate = $value;
    }


    /**
     * @param $value
     */
    public function setB2bTaxRates($value)
    {
        $this->b2bTaxRates = $value;
    }

}
