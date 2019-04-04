<?php

use YandexCheckout\Model\CurrencyCode;
use YandexCheckout\Model\Payment;
use YandexCheckout\Model\PaymentData\B2b\Sberbank\VatData;
use YandexCheckout\Model\PaymentData\B2b\Sberbank\VatDataType;
use YandexCheckout\Model\PaymentData\PaymentDataB2bSberbank;

require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'yandex_money.php';

class ControllerExtensionPaymentYandexMoneyB2bSberbank extends ControllerExtensionPaymentYandexMoney
{
    /**
     * Экшен генерирующий страницу оплаты с помощью Яндекс.Денег
     * @return string
     */
    public function index()
    {
        $this->load->language('extension/payment/'.self::MODULE_NAME);
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('checkout/order');

        $orderInfo = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $template  = $this->applyTemplateVariables($this, $data, $orderInfo);

        $data['language'] = $this->language;
        $data['fullView'] = false;

        $data['column_left']    = $this->load->controller('common/column_left');
        $data['column_right']   = $this->load->controller('common/column_right');
        $data['content_top']    = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer']         = $this->load->controller('common/footer');
        $data['header']         = $this->load->controller('common/header');

        return $this->load->view($template, $data);
    }

    /**
     * @param array $templateData
     * @param $controller
     *
     * @return string
     */
    public function applyTemplateVariables($controller, &$templateData, $orderInfo)
    {
        $templateData['kassa']           = $this;
        $templateData['image_base_path'] = HTTPS_SERVER.'image/catalog/payment/yandex_money';
        $templateData['validate_url']    = $controller->url->link('extension/payment/yandex_money_b2b_sberbank/create',
            '', true);

        $templateData['amount']         = $orderInfo['total'];
        $templateData['comment']        = $orderInfo['comment'];
        $templateData['orderId']        = $orderInfo['order_id'];
        $templateData['customerNumber'] = trim($orderInfo['order_id'].' '.$orderInfo['email']);
        $templateData['orderText']      = $orderInfo['comment'];

        return 'extension/payment/yandex_money/kassa_form_b2b';
    }

    /**
     * Экшен проведения платежа, вызываемый после подтверждения заказа пользователем
     */
    public function create()
    {
        $kassa = $this->getModel()->getKassaModel();
        ob_start();
        if (!$kassa->isEnabled()) {
            $this->jsonError('Yandex.Kassa module disabled');
        }
        if (!isset($this->session->data['order_id'])) {
            $this->jsonError('Cart is empty');
        }

        $paymentMethod = $this->request->get['paymentType'];
        if (!$kassa->getB2bSberbankEnabled() || $paymentMethod != YandexCheckout\Model\PaymentMethodType::B2B_SBERBANK) {
            $this->jsonError('Invalid payment method');
        }

        $orderId = $this->session->data['order_id'];
        $this->getModel()->log('info', 'Создание платежа для заказа №'.$orderId);
        if (!isset($this->request->get['paymentType'])) {
            $this->jsonError('Payment method not specified');
        }

        $payment = $this->createPayment($orderId);

        if ($payment === null) {
            $this->jsonError('Платеж не прошел. Попробуйте еще или выберите другой способ оплаты');
        } elseif ($payment->getStatus() === \YandexCheckout\Model\PaymentStatus::CANCELED) {
            $this->jsonError('Платеж не прошел. Попробуйте еще или выберите другой способ оплаты');
        }
        $result       = array(
            'success'  => true,
            'redirect' => $this->url->link('checkout/success', '', true),
        );
        $confirmation = $payment->getConfirmation();
        if ($confirmation !== null && $confirmation->getType() === \YandexCheckout\Model\ConfirmationType::REDIRECT) {
            $result['redirect'] = $confirmation->getConfirmationUrl();
        }

        if ($kassa->getCreateOrderBeforeRedirect()) {
            $this->getModel()->log('debug', 'Confirm order#'.$orderId.' after payment creation');
            $this->getModel()->confirmOrder($orderId, $payment);
        }
        if ($kassa->getClearCartBeforeRedirect()) {
            $this->getModel()->log('debug', 'Clear order#'.$orderId.' cart after payment creation');
            $this->cart->clear();
        }

        $output = ob_get_clean();
        if (!empty($output)) {
            $this->getModel()->log('warning', 'Non empty buffer: '.$output);
        }

        echo json_encode($result);
        exit();
    }

    /**
     * @param $orderId
     *
     * @return null
     */
    public function createPayment($orderId)
    {
        $this->load->model('checkout/order');
        $orderInfo = $this->model_checkout_order->getOrder($orderId);
        if (empty($orderInfo)) {
            return null;
        }

        $returnUrl = htmlspecialchars_decode(
            $this->url->link('extension/payment/yandex_money/confirm', 'order_id='.$orderId, true)
        );

        $amount = $orderInfo['total'];
        if ($orderInfo['currency_code'] !== 'RUB') {
            $amount = $this->currency->convert($amount, $orderInfo['currency_code'], 'RUB');
        }

        try {
            $builder     = \YandexCheckout\Request\Payments\CreatePaymentRequest::builder();
            $description = $this->generatePaymentPurpose($orderInfo);
            $builder->setAmount($amount)
                    ->setCurrency('RUB')
                    ->setDescription($description)
                    ->setClientIp($_SERVER['REMOTE_ADDR'])
                    ->setCapture(true)
                    ->setMetadata(array(
                        'order_id'       => $orderId,
                        'cms_name'       => 'ya_api_ycms_opencart',
                        'module_version' => '1.2.8',
                    ));

            $confirmation = array(
                'type'      => \YandexCheckout\Model\ConfirmationType::REDIRECT,
                'returnUrl' => $returnUrl,
            );

            $paymentMethodData = new PaymentDataB2bSberbank();

            $paymentPurpose = $this->generatePaymentDescription($orderInfo);

            try {
                $vatTypeAndRate = $this->calculateVatTypeAndRate($orderInfo);
            } catch (Exception $e) {
                $this->jsonError($this->language->get('b2b_tax_rates_error'));
            }

            if ($vatTypeAndRate['vatType'] === VatDataType::CALCULATED) {
                $vatRate      = $vatTypeAndRate['vatRate'];
                $vatSumAmount = $this->currency->format($orderInfo['total'],
                        $orderInfo['currency_code'], $orderInfo['currency_value'],
                        false) * $vatTypeAndRate['vatRate'] / 100;
                $vatData      = new VatData(
                    VatDataType::CALCULATED,
                    $vatRate,
                    ['value' => round($vatSumAmount, 2), 'currency' => CurrencyCode::RUB]
                );
            } else {
                $vatData = new VatData(VatDataType::UNTAXED);
            }

            $paymentMethodData->setPaymentPurpose($paymentPurpose);
            $paymentMethodData->setVatData($vatData);


            $builder->setPaymentMethodData($paymentMethodData);

            $builder->setConfirmation($confirmation);

            $request = $builder->build();

        } catch (InvalidArgumentException $e) {
            $this->getModel()->log('error', 'Failed to create create payment request: '.$e->getMessage());

            return null;
        }

        try {
            $payment = $this->getModel()->getClient()->createPayment($request);
        } catch (Exception $e) {
            $this->getModel()->log('error', 'Failed to create payment: '.$e->getMessage());
            $payment = null;
        }
        if ($payment !== null) {
            $this->insertPayment($payment, $orderId);
        }

        return $payment;
    }

    /**
     * @param $order
     *
     * @return array
     * @throws Exception
     */
    private function calculateVatTypeAndRate($order)
    {
        $this->load->model('account/order');
        $this->load->model('catalog/product');

        $kassa = $this->getModel()->getKassaModel();

        $taxRates = $kassa->getB2bTaxRates();

        $usedTaxes = array();

        $products = $this->model_account_order->getOrderProducts($order['order_id']);
        foreach ($products as $product) {
            $product_info = $this->model_catalog_product->getProduct($product["product_id"]);
            $usedTax      = isset($product_info['tax_class_id']) && isset($taxRates[$product_info['tax_class_id']])
                ? $taxRates[$product_info['tax_class_id']]
                : $taxRates['default'];
            $usedTaxes[]  = $usedTax;
        }
        $usedTaxes = array_unique($usedTaxes);

        if (count($usedTaxes) !== 1) {
            throw new Exception();
        }

        $vatType = reset($usedTaxes);
        if ($vatType === VatDataType::UNTAXED) {
            return array(
                'vatType' => $vatType,
            );
        }

        return array(
            'vatType' => VatDataType::CALCULATED,
            'vatRate' => $vatType,
        );
    }

    /**
     * @param $orderInfo
     *
     * @return string
     */
    private function generatePaymentDescription($orderInfo)
    {
        $this->load->language('extension/payment/yandex_money');

        $descriptionTemplate = $this->getModel()->getKassaModel()->getPaymentDescription();
        if (!$descriptionTemplate) {
            $descriptionTemplate = $this->language->get('kassa_default_payment_description');
        }
        $replace = array();
        foreach ($orderInfo as $key => $value) {
            if (is_scalar($value)) {
                $replace['%'.$key.'%'] = $value;
            }
        }
        $description = strtr($descriptionTemplate, $replace);

        $description = (string)mb_substr($description, 0, Payment::MAX_LENGTH_DESCRIPTION);

        return $description;
    }

    /**
     * @param $orderInfo
     *
     * @return string
     */
    private function generatePaymentPurpose($orderInfo)
    {
        $this->load->language('extension/payment/yandex_money');

        $descriptionTemplate = $this->getModel()->getKassaModel()->getB2bSberbankPaymentPurpose();
        if (!$descriptionTemplate) {
            $descriptionTemplate = $this->language->get('kassa_default_payment_description');
        }
        $replace = array();
        foreach ($orderInfo as $key => $value) {
            if (is_scalar($value)) {
                $replace['%'.$key.'%'] = $value;
            }
        }
        $description = strtr($descriptionTemplate, $replace);

        $description = (string)mb_substr($description, 0, 210);

        return $description;
    }

    /**
     * @param \YandexCheckout\Model\PaymentInterface $payment
     * @param int $orderId
     */
    private function insertPayment($payment, $orderId)
    {
        $paymentMethodId = '';
        if ($payment->getPaymentMethod() !== null) {
            $paymentMethodId = $payment->getPaymentMethod()->getId();
        }
        $sql = 'INSERT INTO `'.DB_PREFIX.'ya_money_payment` (`order_id`, `payment_id`, `status`, `amount`, '
               .'`currency`, `payment_method_id`, `paid`, `created_at`) VALUES ('
               .(int)$orderId.','
               ."'".$this->db->escape($payment->getId())."',"
               ."'".$this->db->escape($payment->getStatus())."',"
               ."'".$this->db->escape($payment->getAmount()->getValue())."',"
               ."'".$this->db->escape($payment->getAmount()->getCurrency())."',"
               ."'".$this->db->escape($paymentMethodId)."',"
               ."'".($payment->getPaid() ? 'Y' : 'N')."',"
               ."'".$this->db->escape($payment->getCreatedAt()->format('Y-m-d H:i:s'))."'"
               .') ON DUPLICATE KEY UPDATE '
               .'`payment_id` = VALUES(`payment_id`),'
               .'`status` = VALUES(`status`),'
               .'`amount` = VALUES(`amount`),'
               .'`currency` = VALUES(`currency`),'
               .'`payment_method_id` = VALUES(`payment_method_id`),'
               .'`paid` = VALUES(`paid`),'
               .'`created_at` = VALUES(`created_at`)';
        $this->db->query($sql);
    }

}