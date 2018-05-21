<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'yandex_money' . DIRECTORY_SEPARATOR . 'autoload.php';

/**
 * Class ModelExtensionPaymentYandexMoney
 *
 * @property ModelAccountOrder $model_account_order
 * @property ModelCatalogProduct $model_catalog_product
 */
class ModelExtensionPaymentYandexMoney extends Model
{
    private $kassaModel;
    private $walletModel;
    private $billingModel;
    private $client;
    private $_prefix;

    /**
     * @return \YandexMoneyModule\Model\KassaModel
     */
    public function getKassaModel()
    {
        if ($this->kassaModel === null) {
            $this->kassaModel = new \YandexMoneyModule\Model\KassaModel($this->config);
        }
        return $this->kassaModel;
    }

    /**
     * @return \YandexMoneyModule\Model\WalletModel
     */
    public function getWalletModel()
    {
        if ($this->walletModel === null) {
            $this->walletModel = new \YandexMoneyModule\Model\WalletModel($this->config);
        }
        return $this->walletModel;
    }

    /**
     * @return \YandexMoneyModule\Model\BillingModel
     */
    public function getBillingModel()
    {
        if ($this->billingModel === null) {
            $this->billingModel = new \YandexMoneyModule\Model\BillingModel($this->config);
        }
        return $this->billingModel;
    }

    /**
     * @return \YandexMoneyModule\Model\AbstractPaymentModel|null
     */
    public function getPaymentModel()
    {
        if ($this->getKassaModel()->isEnabled()) {
            return $this->getKassaModel();
        } elseif ($this->getWalletModel()->isEnabled()) {
            return $this->getWalletModel();
        } elseif ($this->getBillingModel()->isEnabled()) {
            return $this->getBillingModel();
        }
        return null;
    }

    protected function getClient()
    {
        if ($this->client === null) {
            $this->client = new \YandexCheckout\Client();
            $this->client->setAuth(
                $this->getKassaModel()->getShopId(),
                $this->getKassaModel()->getPassword()
            );
            $this->client->setLogger($this);
        }
        return $this->client;
    }

    public function getMethod($address, $total)
    {
        $result = array();
        $this->load->language('extension/payment/yandex_money');

        $model = $this->getPaymentModel();
        if ($model->getMinPaymentAmount() > 0 && $model->getMinPaymentAmount() > $total) {
            return $result;
        }

        if ($model->getGeoZoneId() > 0) {
            $query = $this->db->query(
                "SELECT * FROM `" . DB_PREFIX . "zone_to_geo_zone` WHERE `geo_zone_id` = '"
                . (int)$model->getGeoZoneId() . "' AND country_id = '" . (int)$address['country_id']
                . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')"
            );
            if (empty($query->num_rows)) {
                return $result;
            }
        }
        $result = array(
            'code'       => 'yandex_money',
            'title'      => $model->getDisplayName(),
            'terms'      => '',
            'sort_order' => $this->config->get('yandex_money_sort_order')
        );
        return $result;
    }

    /**
     * @param int $orderId
     * @param string $paymentMethod
     * @return \YandexCheckout\Model\PaymentInterface
     */
    public function createPayment($orderId, $paymentMethod)
    {
        $this->load->model('checkout/order');
        $orderInfo = $this->model_checkout_order->getOrder($orderId);
        if (empty($orderInfo)) {
            return null;
        }

        $returnUrl = htmlspecialchars_decode(
            $this->url->link('extension/payment/yandex_money/confirm', 'order_id=' . $orderId, true)
        );

        $amount = $orderInfo['total'];
        if ($orderInfo['currency_code'] !== 'RUB') {
            $amount = $this->currency->convert($amount, $orderInfo['currency_code'], 'RUB');
        }

        try {
            $builder = \YandexCheckout\Request\Payments\CreatePaymentRequest::builder();
            $builder->setAmount($amount)
                ->setCurrency('RUB')
                ->setClientIp($_SERVER['REMOTE_ADDR'])
                ->setCapture(true)
                ->setMetadata(array(
                    'order_id' => $orderId,
                    'cms_name' => 'ya_api_ycms_opencart',
                    'module_version' => '1.0.8',
                ));

            $confirmation = array(
                'type' => \YandexCheckout\Model\ConfirmationType::REDIRECT,
                'returnUrl' => $returnUrl,
            );
            if (!$this->getKassaModel()->getEPL()) {
                if ($paymentMethod === \YandexCheckout\Model\PaymentMethodType::ALFABANK) {
                    $data = array(
                        'type' => $paymentMethod,
                        'login' => trim($_GET['alphaLogin']),
                    );
                    $confirmation = \YandexCheckout\Model\ConfirmationType::EXTERNAL;
                } elseif ($paymentMethod === \YandexCheckout\Model\PaymentMethodType::QIWI) {
                    $data = array(
                        'type' => $paymentMethod,
                        'phone' => preg_replace('/[^\d]/', '', $_GET['qiwiPhone']),
                    );
                } else {
                    $data = $paymentMethod;
                }
                $builder->setPaymentMethodData($data);
            }
            $builder->setConfirmation($confirmation);

            if ($this->getKassaModel()->sendReceipt()) {
                $this->addReceipt($builder, $orderInfo);
            }
            $request = $builder->build();
            if ($this->getKassaModel()->sendReceipt() && $request->getReceipt() !== null) {
                $request->getReceipt()->normalize($request->getAmount());
            }
        } catch (InvalidArgumentException $e) {
            $this->log('error', 'Failed to create create payment request: ' . $e->getMessage());
            return null;
        }

        try {
            $key = uniqid('', true);
            $tries = 0;
            do {
                $payment = $this->getClient()->createPayment($request, $key);
                $tries++;
                if ($payment === null) {
                    if ($tries > 3) {
                        break;
                    }
                    sleep(2);
                }
            } while ($payment === null);
        } catch (Exception $e) {
            $this->log('error', 'Failed to create payment: ' . $e->getMessage());
            $payment = null;
        }
        if ($payment !== null) {
            $this->insertPayment($payment, $orderId);
        }

        return $payment;
    }

    public function createOrderPayment($order, $returnUrl = null, $paymentMethod = false)
    {
        if (empty($returnUrl)) {
            $returnUrl = $this->url->link('account/order/info', 'order_id=' . $order['order_id'], true);
        }

        $amount = $order['total'];
        if ($order['currency_code'] !== 'RUB') {
            $amount = $this->currency->convert($amount, $order['currency_code'], 'RUB');
        }

        try {
            $builder = \YandexCheckout\Request\Payments\CreatePaymentRequest::builder();
            $builder->setAmount($amount)
                ->setCurrency('RUB')
                ->setClientIp($_SERVER['REMOTE_ADDR'])
                ->setCapture(true);

            $confirmation = array(
                'type' => \YandexCheckout\Model\ConfirmationType::REDIRECT,
                'returnUrl' => $returnUrl,
            );
            if (!$this->getKassaModel()->getEPL() && !empty($paymentMethod)) {
                if ($paymentMethod === \YandexCheckout\Model\PaymentMethodType::ALFABANK) {
                    $data = array(
                        'type' => $paymentMethod,
                        'login' => trim($_GET['alphaLogin']),
                    );
                    $confirmation = \YandexCheckout\Model\ConfirmationType::EXTERNAL;
                } elseif ($paymentMethod === \YandexCheckout\Model\PaymentMethodType::QIWI) {
                    $data = array(
                        'type' => $paymentMethod,
                        'phone' => preg_replace('/[^\d]/', '', $_GET['qiwiPhone']),
                    );
                } else {
                    $data = $paymentMethod;
                }
                $builder->setPaymentMethodData($data);
            }
            $builder->setConfirmation($confirmation);

            if ($this->getKassaModel()->sendReceipt()) {
                $this->addReceipt($builder, $order);
            }
            $request = $builder->build();
            if ($this->getKassaModel()->sendReceipt() && $request->getReceipt() !== null) {
                $request->getReceipt()->normalize($request->getAmount());
            }
        } catch (InvalidArgumentException $e) {
            $this->log('error', 'Failed to create create payment request: ' . $e->getMessage());
            return null;
        }

        try {
            $key = uniqid('', true);
            $tries = 0;
            do {
                $payment = $this->getClient()->createPayment($request, $key);
                $tries++;
                if ($payment === null) {
                    if ($tries > 3) {
                        break;
                    }
                    sleep(2);
                }
            } while ($payment === null);
        } catch (Exception $e) {
            $this->log('error', 'Failed to create payment: ' . $e->getMessage());
            $payment = null;
        }
        if ($payment !== null) {
            $this->insertPayment($payment, $order['order_id']);
        }

        return $payment;
    }

    public function updatePaymentInfo($paymentId)
    {
        $payment = $this->fetchPaymentInfo($paymentId);
        if ($payment !== null) {
            if ($payment->getStatus() !== \YandexCheckout\Model\PaymentStatus::PENDING) {
                $this->updatePaymentInDatabase($payment);
            }
        }
        return $payment;
    }

    /**
     * @param int $orderId
     * @param \YandexCheckout\Model\PaymentInterface $payment
     */
    public function confirmOrder($orderId, $payment)
    {
        $this->load->model('checkout/order');
        $url = $this->url->link('extension/payment/yandex_money/repay', 'order_id=' . $orderId, true);
        $comment = '<a href="' . $url . '" class="button">' . $this->language->get('text_repay') . '</a>';
        $this->model_checkout_order->addOrderHistory($orderId, 1, $comment, true);
    }

    /**
     * @param int $orderId
     * @param $payment
     * @param $statusId
     */
    public function confirmOrderPayment($orderId, $payment, $statusId)
    {
        $this->log('info', 'Confirm captured payment ' . $payment->getId() . ' with status ' . $statusId);
        $this->load->model('checkout/order');
        $this->model_checkout_order->addOrderHistory(
            $orderId,
            $statusId,
            'Платёж номер "' . $payment->getId() . '" подтверждён',
            true
        );
        $sql = 'UPDATE `' . DB_PREFIX . 'order_history` SET `comment` = \'Платёж подтверждён\' WHERE `order_id` = '
            . (int)$orderId . ' AND `order_status_id` <= 1';
        $this->db->query($sql);
    }


    /**
     * @param \YandexCheckout\Model\PaymentInterface $payment
     * @param bool $fetchPaymentInfo
     * @return \YandexCheckout\Model\PaymentInterface
     */
    public function capturePayment($payment, $fetchPaymentInfo = true)
    {
        if ($fetchPaymentInfo) {
            $tmp = $this->fetchPaymentInfo($payment->getId());
            if ($tmp === null) {
                return $tmp;
            }
            $payment = $tmp;
        }
        if ($payment->getStatus() === \YandexCheckout\Model\PaymentStatus::WAITING_FOR_CAPTURE) {
            try {
                $builder = \YandexCheckout\Request\Payments\Payment\CreateCaptureRequest::builder();
                $builder->setAmount($payment->getAmount());
                $request = $builder->build();
            } catch (InvalidArgumentException $e) {
                $this->log('error', 'Failed to create capture payment request: ' . $e->getMessage());
                return $payment;
            }

            try {
                $key = uniqid('', true);
                $tries = 0;
                do {
                    $response = $this->getClient()->capturePayment($request, $payment->getId(), $key);
                    if ($response === null) {
                        $tries++;
                        if ($tries > 3) {
                            break;
                        }
                        sleep(2);
                    }
                } while ($response === null);
            } catch (Exception $e) {
                $this->log('error', 'Failed to capture payment: ' . $e->getMessage());
                $response = null;
            }
            if ($response !== null) {
                $payment = $response;
                $this->updatePaymentInDatabase($payment);
            }
        }
        return $payment;
    }

    /**
     * @param int $orderId
     * @return string|null
     */
    public function findPaymentIdByOrderId($orderId)
    {
        $sql = 'SELECT `payment_id` FROM `' . DB_PREFIX . 'ya_money_payment` WHERE `order_id` = ' . (int)$orderId;
        $resultSet = $this->db->query($sql);
        if (empty($resultSet) || empty($resultSet->num_rows)) {
            return null;
        }
        return $resultSet->row['payment_id'];
    }

    /**
     * @param \YandexCheckout\Model\PaymentInterface $payment
     * @return int
     */
    public function findOrderIdByPayment($payment)
    {
        $sql = 'SELECT `order_id` FROM `' . DB_PREFIX . 'ya_money_payment` WHERE `payment_id` = \'' .
            $this->db->escape($payment->getId()) . '\'';
        $resultSet = $this->db->query($sql);
        if (empty($resultSet) || empty($resultSet->num_rows)) {
            return -1;
        }
        return (int)$resultSet->row['order_id'];
    }

    /**
     * @param string $level
     * @param string $message
     * @param array $context
     */
    public function log($level, $message, $context = array())
    {
        if ($this->getKassaModel()->getDebugLog()) {
            $log = new Log('yandex-money.log');
            $search = array();
            $replace = array();
            if (!empty($context)) {
                foreach ($context as $key => $value) {
                    $search[] = '{' . $key . '}';
                    $replace[] = $value;
                }
            }
            $sessionId = $this->session->getId();
            $userId = 0;
            if (isset($this->session->data['user_id'])) {
                $userId = $this->session->data['user_id'];
            }
            if (empty($search)) {
                $log->write('[' . $level . '] [' . $userId . '] [' . $sessionId . '] - ' . $message);
            } else {
                $log->write(
                    '[' . $level . '] [' . $userId . '] [' . $sessionId . '] - '
                    . str_replace($search, $replace, $message)
                );
            }
        }
    }

    public function checkSign($callbackParams, $password)
    {
        $string = $callbackParams['notification_type'] . '&'
            . $callbackParams['operation_id'] . '&'
            . $callbackParams['amount'] . '&'
            . $callbackParams['currency'] . '&'
            . $callbackParams['datetime'] . '&'
            . $callbackParams['sender'] . '&'
            . $callbackParams['codepro'] . '&'
            . $password . '&'
            . $callbackParams['label'];
        if (sha1($string) !== $callbackParams['sha1_hash']) {
            header('HTTP/1.0 401 Unauthorized');
            return false;
        }
        return true;
    }

    /**
     * @param \YandexCheckout\Request\Payments\CreatePaymentRequestBuilder $builder
     * @param array $orderInfo
     */
    private function addReceipt($builder, $orderInfo)
    {
        $this->load->model('account/order');
        $this->load->model('catalog/product');

        if (isset($orderInfo['email'])) {
            $builder->setReceiptEmail($orderInfo['email']);
        } elseif (isset($orderInfo['phone'])) {
            $builder->setReceiptPhone($orderInfo['phone']);
        }
        $taxRates = $this->config->get('yandex_money_kassa_tax_rates');
        $builder->setTaxSystemCode($this->config->get('yandex_money_kassa_tax_rate_default'));

        $orderProducts = $this->model_account_order->getOrderProducts($orderInfo['order_id']);
        foreach ($orderProducts as $prod) {
            $productInfo = $this->model_catalog_product->getProduct($prod['product_id']);
            $price = $this->currency->format($prod['price'], 'RUB', '', false);
            if (isset($productInfo['tax_class_id'])) {
                $taxId = $productInfo['tax_class_id'];
                if (isset($taxRates[$taxId])) {
                    $builder->addReceiptItem($prod['name'], $price, $prod['quantity'], $taxRates[$taxId]);
                } else {
                    $builder->addReceiptItem($prod['name'], $price, $prod['quantity']);
                }
            } else {
                $builder->addReceiptItem($prod['name'], $price, $prod['quantity']);
            }
        }

        $order_totals = $this->model_account_order->getOrderTotals($orderInfo['order_id']);
        foreach ($order_totals as $total) {
            if (isset($total['code']) && $total['code'] === 'shipping') {
                $price = $this->currency->format($total['value'], 'RUB', '', false);
                if (isset($total['tax_class_id'])) {
                    $taxId = $total['tax_class_id'];
                    $builder->addReceiptShipping($total['title'], $price, $taxRates[$taxId]);
                } else {
                    $builder->addReceiptShipping($total['title'], $price);
                }
            }
        }
    }

    /**
     * @param string $paymentId
     * @return \YandexCheckout\Model\PaymentInterface|null
     */
    public function fetchPaymentInfo($paymentId)
    {
        try {
            $payment = $this->getClient()->getPaymentInfo($paymentId);
        } catch (Exception $e) {
            $this->log('error', 'Failed to fetch payment info: ' . $e->getMessage());
            $payment = null;
        }
        return $payment;
    }

    public function getMetricsJavaScript($id)
    {
        $this->load->model('checkout/order');
        $order = $this->model_checkout_order->getOrder($id);
        $product_array = $this->getOrderProducts($id);
        $ret = array();
        $data = '';
        $ret['order_price'] = $order['total'].' '.$order['currency_code'];
        $ret['order_id'] = $order['order_id'];
        $ret['currency'] = $order['currency_code'];
        $ret['payment'] = $order['payment_method'];
        $products = array();
        foreach ($product_array as $k => $product) {
            $products[$k]['id'] = $product['product_id'];
            $products[$k]['name'] = $product['name'];
            $products[$k]['quantity'] = $product['quantity'];
            $products[$k]['price'] = $product['price'];
        }

        $ret['goods'] = $products;
        if ($this->config->get('ya_metrika_order')) {
            $data = '<script>
                $(window).load(function() {
                    metrikaReach(\'metrikaOrder\', ' . json_encode($ret) . ');
                });
                </script>
            ';
        }
        return $data;
    }

    public function getOrderProducts($order_id)
    {
        $query = $this->db->query('SELECT * FROM `' . DB_PREFIX . 'order_product` WHERE order_id = ' . (int)$order_id);
        return $query->rows;
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
        $sql = 'INSERT INTO `' . DB_PREFIX . 'ya_money_payment` (`order_id`, `payment_id`, `status`, `amount`, '
            . '`currency`, `payment_method_id`, `paid`, `created_at`) VALUES ('
            . (int)$orderId . ','
            . "'" . $this->db->escape($payment->getId()) . "',"
            . "'" . $this->db->escape($payment->getStatus()) . "',"
            . "'" . $this->db->escape($payment->getAmount()->getValue()) . "',"
            . "'" . $this->db->escape($payment->getAmount()->getCurrency()) . "',"
            . "'" . $this->db->escape($paymentMethodId) . "',"
            . "'" . ($payment->getPaid() ? 'Y' : 'N') . "',"
            . "'" . $this->db->escape($payment->getCreatedAt()->format('Y-m-d H:i:s')) . "'"
            . ') ON DUPLICATE KEY UPDATE '
            . '`payment_id` = VALUES(`payment_id`),'
            . '`status` = VALUES(`status`),'
            . '`amount` = VALUES(`amount`),'
            . '`currency` = VALUES(`currency`),'
            . '`payment_method_id` = VALUES(`payment_method_id`),'
            . '`paid` = VALUES(`paid`),'
            . '`created_at` = VALUES(`created_at`)'
            ;
        $this->db->query($sql);
    }

    /**
     * @param \YandexCheckout\Model\PaymentInterface $payment
     */
    private function updatePaymentInDatabase($payment)
    {
        $updates = array(
            "`status` = '" . $this->db->escape($payment->getStatus()) . "'",
            "`paid` = '" . ($payment->getPaid() ? 'Y' : 'N') . "'",
        );
        if ($payment->getCapturedAt() !== null) {
            $updates[] = "`captured_at` = '" . $this->db->escape($payment->getCapturedAt()->format('Y-m-d H:i:s')) . "'";
        }
        $sql = 'UPDATE `' . DB_PREFIX . 'ya_money_payment` SET ' . implode(',', $updates)
            . ' WHERE `payment_id` = \'' . $this->db->escape($payment->getId()) . "'";
        $this->db->query($sql);
    }
}
