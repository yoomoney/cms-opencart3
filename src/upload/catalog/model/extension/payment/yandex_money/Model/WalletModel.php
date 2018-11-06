<?php

namespace YandexMoneyModule\Model;

use Config;

class WalletModel extends AbstractPaymentModel
{
    protected $accountId;
    protected $password;
    protected $testMode;

    public function __construct(Config $config)
    {
        parent::__construct($config, 'wallet');
        $this->accountId = $this->getConfigValue('account_id');
        $this->password = $this->getConfigValue('password');
        $this->testMode = $this->getConfigValue('test_mode') == '1';

        $this->createOrderBeforeRedirect = $this->getConfigValue('create_order_before_redirect');
        $this->clearCartAfterOrderCreation = $this->getConfigValue('clear_cart_before_redirect');
    }

    public function getAccountId()
    {
        return $this->accountId;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getTestMode()
    {
        return $this->testMode;
    }

    public function applyTemplateVariables($controller, &$templateData, $orderInfo)
    {
        $templateData['wallet'] = $this;
        $templateData['image_base_path'] = HTTPS_SERVER . 'image/catalog/payment/yandex_money';
        $templateData['validate_url'] = $controller->url->link('extension/payment/yandex_money/validate', '', true);

        $templateData['cmsname'] = 'opencart2';

        $templateData['successUrl'] = $controller->url->link('checkout/success', '', true);

        $templateData['amount'] = $orderInfo['total'];
        $templateData['comment'] = $orderInfo['comment'];
        $templateData['orderId'] = $orderInfo['order_id'];
        $templateData['customerNumber'] = trim($orderInfo['order_id'] . ' ' . $orderInfo['email']);
        $templateData['orderText'] = $orderInfo['comment'];
        $templateData['formcomment'] = '';
        $templateData['short_dest'] = '';

        $templateData['action'] = 'https://'.($this->testMode ? 'demo' : '').'money.yandex.ru/quickpay/confirm.xml';

        return 'extension/payment/yandex_money/wallet_form';
    }
}
