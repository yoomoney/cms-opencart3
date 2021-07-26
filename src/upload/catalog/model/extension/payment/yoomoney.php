<?php


use YooKassa\Model\ConfirmationType;
use YooKassa\Model\CurrencyCode;
use YooKassa\Model\Payment;
use YooKassa\Model\PaymentInterface;
use YooKassa\Model\PaymentMethodType;
use YooKassa\Request\Payments\CreatePaymentRequest;
use YooMoneyModule\Model\CBRAgent;
use YooMoneyModule\Model\KassaModel;

require_once __DIR__.DIRECTORY_SEPARATOR.'yoomoney'.DIRECTORY_SEPARATOR.'autoload.php';

/**
 * Class ModelExtensionPaymentYoomoney
 *
 * @property ModelAccountOrder $model_account_order
 * @property ModelCatalogProduct $model_catalog_product
 */
class ModelExtensionPaymentYoomoney extends Model
{
    const MODULE_VERSION = '2.1.3';

    private $kassaModel;
    private $walletModel;
    private $client;

    /**
     * @return KassaModel
     */
    public function getKassaModel()
    {
        if ($this->kassaModel === null) {
            $this->kassaModel = new KassaModel($this->config);
        }

        return $this->kassaModel;
    }

    /**
     * @return \YooMoneyModule\Model\WalletModel
     */
    public function getWalletModel()
    {
        if ($this->walletModel === null) {
            $this->walletModel = new \YooMoneyModule\Model\WalletModel($this->config);
        }

        return $this->walletModel;
    }

    /**
     * @return \YooMoneyModule\Model\AbstractPaymentModel|null
     */
    public function getPaymentModel()
    {
        if ($this->getKassaModel()->isEnabled()) {
            return $this->getKassaModel();
        } elseif ($this->getWalletModel()->isEnabled()) {
            return $this->getWalletModel();
        }

        return null;
    }

    public function getClient()
    {
        if ($this->client === null) {
            $this->client = new \YooKassa\Client();
            $this->client->setAuth(
                $this->getKassaModel()->getShopId(),
                $this->getKassaModel()->getPassword()
            );
            $this->client->setLogger($this);
            $userAgent = $this->client->getApiClient()->getUserAgent();
            $userAgent->setCms('OpenCart', VERSION);
            $userAgent->setModule('YooMoney',self::MODULE_VERSION);
        }

        return $this->client;
    }

    public function getMethod($address, $total)
    {
        $result = array();
        $this->load->language('extension/payment/yoomoney');

        $model = $this->getPaymentModel();
        if ($model->getMinPaymentAmount() > 0 && $model->getMinPaymentAmount() > $total) {
            return $result;
        }

        if ($model->getGeoZoneId() > 0) {
            $query = $this->db->query(
                "SELECT * FROM `".DB_PREFIX."zone_to_geo_zone` WHERE `geo_zone_id` = '"
                .(int)$model->getGeoZoneId()."' AND country_id = '".(int)$address['country_id']
                ."' AND (zone_id = '".(int)$address['zone_id']."' OR zone_id = '0')"
            );
            if (empty($query->num_rows)) {
                return $result;
            }
        }
        $result = array(
            'code'       => 'yoomoney',
            'title'      => $model->getDisplayName(),
            'terms'      => '',
            'sort_order' => $model->getSortOrder(),
        );

        return $result;
    }

    /**
     * @param int $orderId
     * @param string $paymentMethod
     *
     * @return PaymentInterface
     */
    public function createPayment($orderId, $paymentMethod)
    {
        $this->load->model('checkout/order');
        $orderInfo = $this->model_checkout_order->getOrder($orderId);
        if (empty($orderInfo)) {
            return null;
        }

        $returnUrl = htmlspecialchars_decode(
            $this->url->link('extension/payment/yoomoney/confirm', 'order_id='.$orderId, true)
        );

        $kassaCurrency = $this->getKassaModel()->getCurrency();
        $this->log('info', "Amount calc \n{data}", array(
            'data' => json_encode(array(
            'order_total' => $orderInfo['total'],
            'kassa_currency' => $kassaCurrency,
            'has_currency' => $this->currency->has($kassaCurrency) ? 'true' : 'false',
        ), JSON_PRETTY_PRINT)));

        if ($this->currency->has($kassaCurrency)) {
            $amount = $this->currency->format($orderInfo['total'], $kassaCurrency, '', false);
        } else {
            if ($this->getKassaModel()->getCurrencyConvert()) {
                $amount = $this->convertFromCbrf($orderInfo, $kassaCurrency);
            } else {
                $amount = $orderInfo['total'];
            }
        }

        try {
            $builder      = CreatePaymentRequest::builder();
            $description  = $this->generatePaymentDescription($orderInfo);
            $captureValue = $this->getKassaModel()->getCaptureValue($paymentMethod);
            $builder->setAmount($amount)
                    ->setCurrency($kassaCurrency)
                    ->setDescription($description)
                    ->setClientIp($_SERVER['REMOTE_ADDR'])
                    ->setCapture($captureValue)
                    ->setMetadata(array(
                        'order_id'       => $orderId,
                        'cms_name'       => 'yoo_opencart3',
                        'module_version' => self::MODULE_VERSION,
                    ));

            $confirmation = array(
                'type'      => ConfirmationType::REDIRECT,
                'returnUrl' => $returnUrl,
            );
            if (!$this->getKassaModel()->getEPL()) {

                if ($paymentMethod === PaymentMethodType::ALFABANK) {
                    $data         = array(
                        'type'  => $paymentMethod,
                        'login' => trim($_GET['alphaLogin']),
                    );
                    $confirmation = ConfirmationType::EXTERNAL;
                } elseif ($paymentMethod === PaymentMethodType::QIWI) {
                    $data = array(
                        'type'  => $paymentMethod,
                        'phone' => preg_replace('/[^\d]/', '', $_GET['qiwiPhone']),
                    );
                } elseif ($paymentMethod === KassaModel::CUSTOM_PAYMENT_METHOD_WIDGET) {
                    $confirmation = ConfirmationType::EMBEDDED;
                } else {
                    $data = $paymentMethod;
                }

                if (isset($data)) {
                    $builder->setPaymentMethodData($data);
                }
            } elseif ($paymentMethod === PaymentMethodType::INSTALLMENTS) {
                $builder->setPaymentMethodData($paymentMethod);
            }

            $builder->setConfirmation($confirmation);

            if ($this->getKassaModel()->isSendReceipt()) {
                $this->addReceipt($builder, $orderInfo);
            }

            $request = $builder->build();

            if ($this->getKassaModel()->isSendReceipt() && $request->getReceipt() !== null) {
                $request->getReceipt()->normalize($request->getAmount());
            }

        } catch (InvalidArgumentException $e) {
            $this->log('error', 'Failed to create create payment request: '.$e->getMessage());

            return null;
        }

        try {
            $payment = $this->getClient()->createPayment($request);
        } catch (Exception $e) {
            $this->log('error', 'Failed to create payment: '.$e->getMessage());
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
            $returnUrl = $this->url->link('account/order/info', 'order_id='.$order['order_id'], true);
        }

        $amount = $this->currency->format($order['total'], 'RUB', '', false);

        try {
            $builder = CreatePaymentRequest::builder();
            $builder->setAmount($amount)
                    ->setCurrency('RUB')
                    ->setClientIp($_SERVER['REMOTE_ADDR'])
                    ->setCapture(true);

            $confirmation = array(
                'type'      => ConfirmationType::REDIRECT,
                'returnUrl' => $returnUrl,
            );
            if (!$this->getKassaModel()->getEPL() && !empty($paymentMethod)) {
                if ($paymentMethod === PaymentMethodType::ALFABANK) {
                    $data         = array(
                        'type'  => $paymentMethod,
                        'login' => trim($_GET['alphaLogin']),
                    );
                    $confirmation = ConfirmationType::EXTERNAL;
                } elseif ($paymentMethod === PaymentMethodType::QIWI) {
                    $data = array(
                        'type'  => $paymentMethod,
                        'phone' => preg_replace('/[^\d]/', '', $_GET['qiwiPhone']),
                    );
                } else {
                    $data = $paymentMethod;
                }
                $builder->setPaymentMethodData($data);
            }
            $builder->setConfirmation($confirmation);

            if ($this->getKassaModel()->isSendReceipt()) {
                $this->addReceipt($builder, $order);
            }
            $request = $builder->build();
            if ($this->getKassaModel()->isSendReceipt() && $request->getReceipt() !== null) {
                $request->getReceipt()->normalize($request->getAmount());
            }
        } catch (InvalidArgumentException $e) {
            $this->log('error', 'Failed to create create payment request: '.$e->getMessage());

            return null;
        }

        try {
            $payment = $this->getClient()->createPayment($request);
        } catch (Exception $e) {
            $this->log('error', 'Failed to create payment: '.$e->getMessage());
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
            if ($payment->getStatus() !== \YooKassa\Model\PaymentStatus::PENDING) {
                $this->updatePaymentInDatabase($payment);
            }
        }

        return $payment;
    }

    /**
     * @param int $orderId
     */
    public function confirmOrder($orderId)
    {
        $this->load->model('checkout/order');
        $url     = $this->url->link('extension/payment/yoomoney/repay', 'order_id='.$orderId, true);
        $comment = '<a href="'.$url.'" class="button">'.$this->language->get('text_repay').'</a>';
        $orderStatusId = $this->config->get('config_order_status_id');
        $this->model_checkout_order->addOrderHistory($orderId, $orderStatusId, $comment);
    }

    /**
     * @param int $orderId
     * @param $payment
     * @param $statusId
     */
    public function confirmOrderPayment($orderId, $payment, $statusId)
    {
        $message     = '';
        if ($payment->getPaymentMethod()->getType() == PaymentMethodType::B2B_SBERBANK) {
            $payerBankDetails = $payment->getPaymentMethod()->getPayerBankDetails();

            $fields = array(
                'fullName'   => 'Полное наименование организации',
                'shortName'  => 'Сокращенное наименование организации',
                'adress'     => 'Адрес организации',
                'inn'        => 'ИНН организации',
                'kpp'        => 'КПП организации',
                'bankName'   => 'Наименование банка организации',
                'bankBranch' => 'Отделение банка организации',
                'bankBik'    => 'БИК банка организации',
                'account'    => 'Номер счета организации',
            );

            foreach ($fields as $field => $caption) {
                if (isset($requestData[$field])) {
                    $message .= $caption.': '.$payerBankDetails->offsetGet($field).'\n';
                }
            }
        }

        $this->log('info', 'Confirm captured payment '.$payment->getId().' with status '.$statusId);
        $this->load->model('checkout/order');
        $this->model_checkout_order->addOrderHistory(
            $orderId,
            $statusId,
            'Платёж номер "'.$payment->getId().'" подтверждён' . $message
        );
        $sql = 'UPDATE `'.DB_PREFIX.'order_history` SET `comment` = \'Платёж подтверждён\' WHERE `order_id` = '
               .(int)$orderId.' AND `order_status_id` <= 1';
        $this->db->query($sql);
    }


    /**
     * @param PaymentInterface $payment
     * @param bool $fetchPaymentInfo
     * @param $amount
     *
     * @return PaymentInterface
     */
    public function capturePayment($payment, $fetchPaymentInfo = true, $amount = null)
    {
        if ($fetchPaymentInfo) {
            $tmp = $this->fetchPaymentInfo($payment->getId());
            if ($tmp === null) {
                return $tmp;
            }
            $payment = $tmp;
        }
        if ($payment->getStatus() === \YooKassa\Model\PaymentStatus::WAITING_FOR_CAPTURE) {
            try {
                $builder = \YooKassa\Request\Payments\Payment\CreateCaptureRequest::builder();
                if (is_null($amount)) {
                    $builder->setAmount($payment->getAmount());
                } else {
                    $builder->setAmount($amount);
                }

                $request = $builder->build();
            } catch (InvalidArgumentException $e) {
                $this->log('error', 'Failed to create capture payment request: '.$e->getMessage());

                return $payment;
            }

            try {
                $response = $this->getClient()->capturePayment($request, $payment->getId());
            } catch (Exception $e) {
                $this->log('error', 'Failed to capture payment: '.$e->getMessage());
                $response = null;
            }
            if ($response !== null) {
                $payment = $response;
                $this->updatePaymentInDatabase($payment);
            }
        }

        return $payment;
    }

    public function cancelPayment($payment)
    {
        try {
            $response = $this->getClient()->cancelPayment($payment->getId());
        } catch (Exception $e) {
            $this->log('error', 'Failed to capture payment: '.$e->getMessage());
            $response = null;
        }
        if ($response !== null) {
            $payment = $response;
            $this->updatePaymentInDatabase($payment);
        }

        return $payment;
    }

    /**
     * @param $orderId
     * @param $newStatusId
     */
    public function hookOrderStatusChange($orderId, $newStatusId)
    {
        if ($newStatusId < 1) {
            return;
        }

        $this->load->model('checkout/order');
        $this->load->language('extension/payment/yoomoney');

        $paymentId   = $this->findPaymentIdByOrderId($orderId);
        $orderInfo   = $this->model_checkout_order->getOrder($orderId);
        $paymentInfo = $this->fetchPaymentInfo($paymentId);

        $secondReceipt = new \YooMoneyModule\Model\KassaSecondReceiptModel($this->config, $this->session, $orderId, $paymentInfo, $orderInfo);

        if ($secondReceipt->sendSecondReceipt($newStatusId)) {
            $settlementsSum = $secondReceipt->getSettlementsSum();
            $comment = sprintf($this->language->get('second_receipt_history'), $settlementsSum);
            $this->addOrderHistory($orderId, $newStatusId, $comment);
        }
    }

    /**
     * @param int $orderId
     * @param int $newStatusId
     * @param string $comment
     */
    public function addOrderHistory($orderId, $newStatusId, $comment)
    {
        $this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int)$orderId . "', order_status_id = '" . (int)$newStatusId . "', notify = '0', comment = '" . $this->db->escape($comment) . "', date_added = NOW()");
    }

    /**
     * @param int $orderId
     *
     * @return string|null
     */
    public function findPaymentIdByOrderId($orderId)
    {
        $sql       = 'SELECT `payment_id` FROM `'.DB_PREFIX.'yoomoney_payment` WHERE `order_id` = '.(int)$orderId;
        $resultSet = $this->db->query($sql);
        if (empty($resultSet) || empty($resultSet->num_rows)) {
            return null;
        }

        return $resultSet->row['payment_id'];
    }

    /**
     * @param string $paymentId
     *
     * @return int
     */
    public function findOrderIdByPayment($paymentId)
    {
        $sql       = 'SELECT `order_id` FROM `'.DB_PREFIX.'yoomoney_payment` WHERE `payment_id` = \''.
                     $this->db->escape($paymentId).'\'';
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
            $log     = new Log('yoomoney.log');
            $search  = array();
            $replace = array();
            if (!empty($context)) {
                foreach ($context as $key => $value) {
                    $search[]  = '{'.$key.'}';
                    $replace[] = (is_array($value)||is_object($value)) ? json_encode($value, JSON_PRETTY_PRINT) : $value;
                }
            }
            $sessionId = $this->session->getId();
            $userId    = 0;
            if (isset($this->session->data['user_id'])) {
                $userId = $this->session->data['user_id'];
            }
            if (empty($search)) {
                $log->write('['.$level.'] ['.$userId.'] ['.$sessionId.'] - '.$message);
            } else {
                foreach ($search as $object) {
                    if (stripos($message, $object) === false) {
                        $label = trim($object, "{}");
                        $message .= " \n{$label}: {$object}";
                    }
                }
                $log->write(
                    '['.$level.'] ['.$userId.'] ['.$sessionId.'] - '
                    .str_replace($search, $replace, $message)
                );
            }
        }
    }

    public function checkSign($callbackParams, $password)
    {
        $string = $callbackParams['notification_type'].'&'
                  .$callbackParams['operation_id'].'&'
                  .$callbackParams['amount'].'&'
                  .$callbackParams['currency'].'&'
                  .$callbackParams['datetime'].'&'
                  .$callbackParams['sender'].'&'
                  .$callbackParams['codepro'].'&'
                  .$password.'&'
                  .$callbackParams['label'];
        if (sha1($string) !== $callbackParams['sha1_hash']) {
            header('HTTP/1.0 401 Unauthorized');

            return false;
        }

        return true;
    }

    /**
     * @param \YooKassa\Request\Payments\CreatePaymentRequestBuilder $builder
     * @param array $orderInfo
     */
    private function addReceipt($builder, $orderInfo)
    {
        $this->load->model('account/order');
        $this->load->model('catalog/product');

        if (!empty($orderInfo['email']) && filter_var($orderInfo['email'], FILTER_VALIDATE_EMAIL)) {
            $builder->setReceiptEmail($orderInfo['email']);
        } elseif (!empty($orderInfo['telephone'])) {
            $builder->setReceiptPhone($orderInfo['telephone']);
        }
        $taxRates                       = $this->config->get('yoomoney_kassa_tax_rates');
        $defaultVatCode                 = $this->config->get('yoomoney_kassa_tax_rate_default');
        $defaultTaxSystemCode           = $this->config->get('yoomoney_kassa_tax_system_default');
        $defaultPaymentSubject          = $this->config->get('yoomoney_kassa_payment_subject_default');
        $defaultPaymentMode             = $this->config->get('yoomoney_kassa_payment_mode_default');
        $defaultDeliveryPaymentSubject  = $this->config->get('yoomoney_kassa_payment_subject_default');
        $defaultDeliveryPaymentMode     = $this->config->get('yoomoney_kassa_payment_mode_default');

        $orderProducts = $this->model_account_order->getOrderProducts($orderInfo['order_id']);
        foreach ($orderProducts as $prod) {
            $properties  = $this->getPaymentProperties($prod['product_id']);
            if (!empty($properties)) {
                $paymentMode    = !empty($properties['payment_mode']) ? $properties['payment_mode'] : $defaultPaymentMode;
                $paymentSubject = !empty($properties['payment_subject']) ? $properties['payment_subject'] : $defaultPaymentSubject;
            } else {
                $paymentMode    = $defaultPaymentMode;
                $paymentSubject = $defaultPaymentSubject;
            }
            $productInfo = $this->model_catalog_product->getProduct($prod['product_id']);
            $price       = $this->currency->format($prod['price'], 'RUB', '', false);
            $vatCode     = isset($taxRates[$productInfo['tax_class_id']])
                ? $taxRates[$productInfo['tax_class_id']]
                : $defaultVatCode;
            $builder->addReceiptItem($prod['name'], $price, $prod['quantity'], $vatCode, $paymentMode, $paymentSubject);
        }

        $order_totals = $this->model_account_order->getOrderTotals($orderInfo['order_id']);
        foreach ($order_totals as $total) {
            if (isset($total['code']) && $total['code'] === 'shipping') {
                $price = $this->currency->format($total['value'], 'RUB', '', false);
                $builder->addReceiptShipping(
                    $total['title'], $price, $defaultVatCode,
                    $defaultDeliveryPaymentMode ?: $defaultPaymentMode,
                    $defaultDeliveryPaymentSubject ?: $defaultPaymentSubject
                );
            }
        }

        if (!empty($defaultTaxSystemCode)) {
            $builder->setTaxSystemCode($defaultTaxSystemCode);
        }
    }

    /**
     * @param string $paymentId
     *
     * @return PaymentInterface|null
     */
    public function fetchPaymentInfo($paymentId)
    {
        try {
            $payment = $this->getClient()->getPaymentInfo($paymentId);
        } catch (Exception $e) {
            $this->log('error', 'Failed to fetch payment info: '.$e->getMessage());
            $payment = null;
        }

        return $payment;
    }

    public function getMetricsJavaScript($id)
    {
        $this->load->model('checkout/order');
        $order              = $this->model_checkout_order->getOrder($id);
        $product_array      = $this->getOrderProducts($id);
        $products           = array();
        foreach ($product_array as $k => $product) {
            $products[$k]['id']       = $product['product_id'];
            $products[$k]['name']     = $product['name'];
            $products[$k]['quantity'] = (int)$product['quantity'];
            $products[$k]['price']    = (float)$product['price'];
        }

        $ecommerce = array(
            'currencyCode' => $order['currency_code'],
            'purchase'     => array(
                'actionField' => array(
                    'id'      => $order['order_id'],
                    'revenue' => $order['total'],
                ),
                'products'    => $products,
            ),
        );

        $data = '<script type="text/javascript">
            $(window).on("load", function() {
                window.dataLayer = window.dataLayer || [];
                dataLayer.push({ecommerce: '.json_encode($ecommerce).'});
            });
            </script>';

        return $data;
    }

    public function getOrderProducts($order_id)
    {
        $query = $this->db->query('SELECT * FROM `'.DB_PREFIX.'order_product` WHERE order_id = '.(int)$order_id);

        return $query->rows;
    }

    /**
     * @param PaymentInterface $payment
     * @param int $orderId
     */
    private function insertPayment($payment, $orderId)
    {
        $paymentMethodId = '';
        if ($payment->getPaymentMethod() !== null) {
            $paymentMethodId = $payment->getPaymentMethod()->getId();
        }
        $sql = 'INSERT INTO `'.DB_PREFIX.'yoomoney_payment` (`order_id`, `payment_id`, `status`, `amount`, '
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

    /**
     * @param PaymentInterface $payment
     */
    private function updatePaymentInDatabase($payment)
    {
        $updates = array(
            "`status` = '".$this->db->escape($payment->getStatus())."'",
            "`paid` = '".($payment->getPaid() ? 'Y' : 'N')."'",
        );
        if ($payment->getCapturedAt() !== null) {
            $updates[] = "`captured_at` = '".$this->db->escape($payment->getCapturedAt()->format('Y-m-d H:i:s'))."'";
        }
        $sql = 'UPDATE `'.DB_PREFIX.'yoomoney_payment` SET '.implode(',', $updates)
               .' WHERE `payment_id` = \''.$this->db->escape($payment->getId())."'";
        $this->db->query($sql);
    }

    /**
     * @param $orderInfo
     *
     * @return string
     */
    private function generatePaymentDescription($orderInfo)
    {
        $this->load->language('extension/payment/yoomoney');

        $descriptionTemplate = $this->getKassaModel()->getPaymentDescription();
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

    public function getPaymentProperties($productId)
    {
        $res         = $this->db->query('SELECT * FROM `'.DB_PREFIX.'yoomoney_product_properties` WHERE product_id='.$productId);
        $productProp = $res->row;

        return $productProp;
    }

    public function getCbrfCourses()
    {
        $courses = $this->cache->get('cbrf_courses');
        if (!$courses) {
            $cbrf = new CBRAgent();
            $courses = $cbrf->getList();
            $this->cache->set('cbrf_courses', $courses);
            $this->log('info', "Get CBRF courses \n{courses}", array('courses' => $courses));
        }
        return $courses;
    }

    public function convertFromCbrf($order, $currency)
    {
        $config_currency = $this->config->get('config_currency');

        if ($config_currency == $currency) {
            return $order['total'];
        }

        $courses = $this->getCbrfCourses();
        if ((!empty($courses[$currency]) || $currency === CurrencyCode::RUB)
            && (!empty($courses[$config_currency]) || $config_currency === CurrencyCode::RUB)) {
            $input  = $config_currency != CurrencyCode::RUB ? $courses[$config_currency] : 1.0;
            $output = $currency != CurrencyCode::RUB ? $courses[$currency] : 1.0;

            return number_format($order['total'] * $input / $output, 2, '.', '');
        }

        return $order['total'];
    }
}
