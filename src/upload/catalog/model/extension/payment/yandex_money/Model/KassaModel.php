<?php

namespace YandexMoneyModule\Model;

use Config;
use YandexCheckout\Model\PaymentMethodType;

class KassaModel extends AbstractPaymentModel
{
    private static $_enabledTestMethods = array(
        PaymentMethodType::YANDEX_MONEY => true,
        PaymentMethodType::BANK_CARD => true,
    );

    protected $shopId;
    protected $password;
    protected $epl;
    protected $useYandexButton;
    protected $paymentMethods;
    protected $sendReceipt;
    protected $defaultTaxRate;
    protected $taxRates;
    protected $log;
    protected $testMode;
    protected $showInFooter;

    public function __construct(Config $config)
    {
        parent::__construct($config, 'kassa');

        $this->shopId = $this->getConfigValue('shop_id');
        $this->password = $this->getConfigValue('password');
        $this->epl = $this->getConfigValue('payment_mode') !== 'shop';
        $this->useYandexButton = (bool)$this->getConfigValue('use_yandex_button');

        $this->testMode = false;
        if ($this->enabled && strncmp('test_', $this->password, 5) === 0) {
            $this->testMode = true;
        }

        $this->paymentMethods = array();
        foreach (PaymentMethodType::getEnabledValues() as $value) {
            $property = 'payment_method_' . $value;
            $enabled = (bool)$this->getConfigValue($property);
            if (!$this->testMode || array_key_exists($value, self::$_enabledTestMethods)) {
                $this->paymentMethods[$value] = $enabled;
            }
        }

        $this->sendReceipt = (bool)$this->getConfigValue('send_receipt');
        $this->defaultTaxRate = (int)$this->getConfigValue('tax_rate_default');
        $this->log = (bool)$this->getConfigValue('debug_log');

        $this->taxRates = array();
        $tmp = $this->getConfigValue('tax_rates');
        if (!empty($tmp)) {
            if (is_array($tmp)) {
                foreach ($tmp as $shopTaxRateId => $kassaTaxRateId) {
                    $this->taxRates[$shopTaxRateId] = $kassaTaxRateId;
                }
            }
        }

        $this->createOrderBeforeRedirect = $this->getConfigValue('create_order_before_redirect');
        $this->clearCartAfterOrderCreation = $this->getConfigValue('clear_cart_before_redirect');

        $this->showInFooter = $this->getConfigValue('show_in_footer');
    }

    public function isTestMode()
    {
        return $this->testMode;
    }

    public function getShopId()
    {
        return $this->shopId;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getEPL()
    {
        return !$this->testMode && $this->epl;
    }

    public function useYandexButton()
    {
        return $this->useYandexButton;
    }

    public function getPaymentMethods()
    {
        return $this->paymentMethods;
    }

    public function getEnabledPaymentMethods()
    {
        $result = array();
        foreach ($this->paymentMethods as $method => $enabled) {
            if ($enabled) {
                $result[] = $method;
            }
        }
        return $result;
    }

    public function isPaymentMethodEnabled($paymentMethod)
    {
        return isset($this->paymentMethods[$paymentMethod]) && $this->paymentMethods[$paymentMethod];
    }

    public function sendReceipt()
    {
        return $this->sendReceipt;
    }

    public function getTaxRateList()
    {
        return array(1, 2, 3, 4, 5, 6);
    }

    public function getDefaultTaxRate()
    {
        return $this->defaultTaxRate;
    }

    public function getTaxRateId($shopTaxRateId)
    {
        if (isset($this->taxRates[$shopTaxRateId])) {
            return $this->taxRates[$shopTaxRateId];
        }
        return $this->defaultTaxRate;
    }

    public function getTaxRates()
    {
        return $this->taxRates;
    }

    public function getDebugLog()
    {
        return $this->log;
    }

    public function getShowLinkInFooter()
    {
        return $this->showInFooter;
    }

    /**
     * @param array $templateData
     * @param $controller
     * @return string
     */
    public function applyTemplateVariables($controller, &$templateData, $orderInfo)
    {
        $templateData['kassa'] = $this;
        $templateData['image_base_path'] = HTTPS_SERVER . 'image/catalog/payment/yandex_money';
        $prefix = version_compare(VERSION, '2.3.0') >= 0 ? 'extension/' : '';
        $templateData['validate_url'] = $controller->url->link($prefix.'payment/yandex_money/create', '', true);

        $templateData['amount'] = $orderInfo['total'];
        $templateData['comment'] = $orderInfo['comment'];
        $templateData['orderId'] = $orderInfo['order_id'];
        $templateData['customerNumber'] = trim($orderInfo['order_id'] . ' ' . $orderInfo['email']);
        $templateData['orderText'] = $orderInfo['comment'];

        return 'extension/payment/yandex_money/kassa_form';
    }
}
