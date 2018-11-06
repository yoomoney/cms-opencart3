<?php

namespace YandexMoneyModule\Model;

use Config;

class BillingModel extends AbstractPaymentModel
{
    protected $formId;
    protected $purpose;

    public function __construct(Config $config)
    {
        parent::__construct($config, 'billing');
        $this->formId = $this->getConfigValue('form_id');
        $this->purpose = $this->getConfigValue('purpose');

        $this->createOrderBeforeRedirect = true;
        $this->clearCartAfterOrderCreation = true;
    }

    public function getFormId()
    {
        return $this->formId;
    }

    public function getPurpose()
    {
        return $this->purpose;
    }

    /**
     * @param array $templateData
     * @param $controller
     * @param array $orderInfo
     * @return string
     */
    public function applyTemplateVariables($controller, &$templateData, $orderInfo)
    {
        $templateData['billing'] = $this;
        $templateData['image_base_path'] = HTTPS_SERVER . 'image/catalog/payment/yandex_money';
        $templateData['validate_url'] = $controller->url->link('extension/payment/yandex_money/confirm', '', true);
        $templateData['action'] = 'https://money.yandex.ru/fastpay/confirm';

        $fio = array();
        if (!empty($orderInfo['lastname'])) {
            $fio[] = $orderInfo['lastname'];
        }
        if (!empty($orderInfo['firstname'])) {
            $fio[] = $orderInfo['firstname'];
        }
        $templateData['customerName'] = implode(' ', $fio);
        $templateData['purpose'] = $this->parsePlaceholders($this->purpose, $orderInfo);

        $templateData['amount'] = $orderInfo['total'];
        $templateData['orderId'] = $orderInfo['order_id'];
        return 'extension/payment/yandex_money/billing_form';
    }

    private function parsePlaceholders($template, $order)
    {
        $replace = array();
        foreach ($order as $key => $value) {
            if (is_scalar($value)) {
                $replace['%' . $key . '%'] = $value;
            }
        }
        return strtr($template, $replace);
    }
}
