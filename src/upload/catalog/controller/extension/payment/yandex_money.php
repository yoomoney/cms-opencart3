<?php

use YandexCheckout\Model\Notification\NotificationSucceeded;
use YandexCheckout\Model\Notification\NotificationWaitingForCapture;
use YandexMoneyModule\YandexMarket\Currency;
use YandexMoneyModule\YandexMarket\Offer;
use YandexMoneyModule\YandexMarket\YandexMarket;

/**
 * Класс контроллера модуля оплаты с помощью Яндекс.Денег
 *
 * @property ModelExtensionPaymentYandexMoney $model_extension_payment_yandex_money
 * @property ModelPaymentYandexMoney $model_payment_yandex_money
 * @property ModelCheckoutOrder $model_checkout_order
 * @property ModelAccountOrder $model_account_order
 * @property \Cart\Customer $customer
 */
class ControllerExtensionPaymentYandexMoney extends Controller
{
    /** @var string */
    const MODULE_NAME = 'yandex_money';

    /**
     * @var ModelExtensionPaymentYandexMoney
     */
    private $_model;

    /**
     * @var \YandexMoneyModule\Model\MarketModel
     */
    private $_marketModel;

    /**
     * Экшен генерирующий страницу оплаты с помощью Яндекс.Денег
     * @return string
     */
    public function index()
    {
        $this->load->language('extension/payment/'.self::MODULE_NAME);
        $this->document->setTitle($this->language->get('heading_title'));

        $model = $this->getModel()->getPaymentModel();
        if ($model === null) {
            $this->failure('Yandex.Kassa module disabled');
        }

        if ($model->getMinPaymentAmount() > 0 && $model->getMinPaymentAmount() > $this->cart->getSubTotal()) {
            $this->failure(sprintf($this->language->get('error_minimum'),
                $this->currency->format($model->getMinPaymentAmount(), $this->session->data['currency'])));
        }

        $this->load->model('checkout/order');
        $orderInfo = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $template  = $model->applyTemplateVariables($this, $data, $orderInfo);

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

    private function payment($orderInfo, $fullView = false)
    {
        $this->load->language('extension/payment/'.self::MODULE_NAME);
        $this->document->setTitle($this->language->get('heading_title'));

        $model = $this->getModel()->getPaymentModel();
        if (!$model->isEnabled()) {
            $this->failure('Yandex.Kassa module disabled');
        }

        if ($model->getMinPaymentAmount() > 0 && $model->getMinPaymentAmount() > $orderInfo['total']) {
            $this->failure(
                sprintf(
                    $this->language->get('error_minimum'),
                    $this->currency->format($model->getMinPaymentAmount(), $orderInfo['currency_id'])
                )
            );
        }

        $template = $model->applyTemplateVariables($this, $data, $orderInfo);

        $data['language'] = $this->language;
        $data['fullView'] = $fullView;

        if ($fullView) {
            $data['column_left']    = $this->load->controller('common/column_left');
            $data['column_right']   = $this->load->controller('common/column_right');
            $data['content_top']    = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer']         = $this->load->controller('common/footer');
            $data['header']         = $this->load->controller('common/header');
        }

        return $this->load->view($template, $data);
    }

    public function simplePayment()
    {
        $kassa = $this->getModel()->getKassaModel();
        if (!$kassa->isEnabled()) {
            $this->failure('Yandex.Kassa module disabled');
        }
        if (!isset($this->request->get['order_id'])) {
            $this->failure('Order id not send');
        }
        $orderId = (int)$this->request->get['order_id'];
        if ($orderId <= 0) {
            $this->failure('Invalid order id');
        }
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link(
                'extension/payment/yandex_money/repay', 'order_id='.$orderId, 'SSL'
            );
            $this->response->redirect($this->url->link('account/login', '', true));
        }

        $this->load->model('account/order');
        $order = $this->model_account_order->getOrder($orderId);
        if (empty($order)) {
            $this->response->redirect(
                $this->url->link('account/order/info', 'order_id='.$orderId, true)
            );
        }

        $query = $this->db->query("SELECT `payment_code`, `order_status_id` FROM `".DB_PREFIX."order` WHERE order_id = '".$orderId."'");
        if (empty($query)) {
            $this->response->redirect(
                $this->url->link('account/order/info', 'order_id='.$orderId, true)
            );
        }
        if ($query->row['payment_code'] !== 'yandex_money') {
            $this->session->data['error'] = 'Не верный способ оплаты заказа';
            $this->response->redirect(
                $this->url->link('account/order/info', 'order_id='.$orderId, true)
            );
        }
        if ($query->row['order_status_id'] == $kassa->getSuccessOrderStatusId()) {
            $this->session->data['error'] = 'Заказ уже оплачен';
            $this->response->redirect(
                $this->url->link('account/order/info', 'order_id='.$orderId, true)
            );
        }

        $this->getModel()->log('info', 'Создание платежа для заказа №'.$orderId);

        $payment = $this->getModel()->createOrderPayment($order, false);
        if ($payment === null) {
            $this->failure('Платеж не прошел. Попробуйте еще или выберите другой способ оплаты');
        } elseif ($payment->getStatus() === \YandexCheckout\Model\PaymentStatus::CANCELED) {
            $this->failure('Платеж не прошел. Попробуйте еще или выберите другой способ оплаты');
        }
        $confirmation = $payment->getConfirmation();
        if ($confirmation !== null && $confirmation->getType() === \YandexCheckout\Model\ConfirmationType::REDIRECT) {
            $this->response->redirect($confirmation->getConfirmationUrl());
        }
        $this->session->data['error'] = 'Не удалось инициализировать платёж';
        $this->response->redirect(
            $this->url->link('account/order/info', 'order_id='.$orderId, true)
        );
    }

    public function failure($error, $display = true)
    {
        if ($display) {
            $this->session->data['error'] = $error;
        }
        $this->getModel()->log('info', $error);
        $this->response->redirect($this->url->link('checkout/checkout', '', true));
    }

    public function jsonError($message)
    {
        $this->getModel()->log('info', $message);
        echo json_encode(array(
            'success' => false,
            'error'   => $message,
        ));
        exit();
    }

    /**
     * Экшен проведения платежа, вызываемый после подтверждения заказа пользователем
     */
    public function create()
    {
        ob_start();
        $kassa = $this->getModel()->getKassaModel();
        if (!$kassa->isEnabled()) {
            $this->jsonError('Yandex.Kassa module disabled');
        }
        if (!isset($this->session->data['order_id'])) {
            $this->jsonError('Cart is empty');
        }
        $orderId = $this->session->data['order_id'];
        $this->getModel()->log('info', 'Создание платежа для заказа №'.$orderId);
        if (!isset($this->request->get['paymentType'])) {
            $this->jsonError('Payment method not specified');
        }
        $paymentMethod = $this->request->get['paymentType'];
        if ($kassa->getEPL()) {
            if (!empty($paymentMethod)) {
                $this->jsonError('Invalid payment method');
            }
        } elseif (!$kassa->isPaymentMethodEnabled($paymentMethod)) {
            $this->jsonError('Invalid payment method');
        } elseif ($paymentMethod == \YandexCheckout\Model\PaymentMethodType::QIWI) {
            $phone = isset($_GET['qiwiPhone']) ? preg_replace('/[^\d]/', '', $_GET['qiwiPhone']) : '';
            if (empty($phone)) {
                $this->jsonError('Не был указан телефон');
            }
        } elseif ($paymentMethod == \YandexCheckout\Model\PaymentMethodType::ALFABANK) {
            $login = isset($this->request->get['alphaLogin']) ? trim($this->request->get['alphaLogin']) : '';
            if (empty($login)) {
                $this->jsonError('Не указан логин в Альфа-клике');
            }
        }

        $payment = $this->getModel()->createPayment($orderId, $paymentMethod);
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
     * Экшен вызываемый при возврате пользователя из кассы, проверяет статус платежа, добавляет в историю заказа
     * событие о создании платежа
     */
    public function confirm()
    {
        $this->load->language('extension/payment/'.self::MODULE_NAME);
        if (empty($_GET['order_id'])) {
            $this->failure('Не был передан идентификатор платежа');
        }
        $this->getModel()->log('info', 'Подтверждение платежа для заказа №'.$_GET['order_id']);
        $kassa = $this->getModel()->getKassaModel();
        if (!$kassa->isEnabled()) {
            $this->failure('Модуль оплаты выключен');
        }
        $orderId   = (int)$_GET['order_id'];
        $paymentId = $this->getModel()->findPaymentIdByOrderId($orderId);
        if (empty($paymentId)) {
            $this->failure('Не удалось получить ID платежа для заказа №'.$orderId);
        }
        $this->load->model('checkout/order');
        $payment = $this->getModel()->updatePaymentInfo($paymentId);
        if ($payment === null) {
            $this->failure('Не найден платёж '.$paymentId.' для заказа №'.$orderId);
        } elseif (!$payment->getPaid()) {
            $this->failure('Платёж не был проведён');
        } elseif ($payment->getStatus() === \YandexCheckout\Model\PaymentStatus::CANCELED) {
            $this->failure('Статус платежа '.$paymentId.' заказа №'.$orderId.' - canceled');
        } elseif ($payment->getStatus() !== \YandexCheckout\Model\PaymentStatus::SUCCEEDED) {
            $this->getModel()->log('info', 'Confirm order#'.$orderId.' with payment '.$payment->getId());
        }
        if ($payment->getStatus() === \YandexCheckout\Model\PaymentStatus::SUCCEEDED) {
            $this->getModel()->confirmOrderPayment(
                $orderId, $payment, $this->getModel()->getKassaModel()->getSuccessOrderStatusId()
            );
        }
        $this->response->redirect($this->url->link('checkout/success', '', true));
    }

    public function validate()
    {
        $this->load->language('extension/payment/'.self::MODULE_NAME);
        if (empty($_GET['payment_type'])) {
            $this->jsonError('Unknown payment type');
        }

        $type = $_GET['payment_type'];
        $paymentModel = $this->getModel()->getPaymentModel();

        if ($paymentModel instanceof \YandexMoneyModule\Model\WalletModel) {
            if ($type !== 'AC' && $type !== 'PC') {
                $this->jsonError('Invalid payment type');
            }

            if ($paymentModel->getCreateOrderBeforeRedirect()) {
                $this->load->model('checkout/order');
                $url     = $this->url->link('extension/payment/yandex_money/repay',
                    'order_id='.$this->session->data['order_id'], true);
                $comment = '<a href="'.$url.'" class="button">'.$this->language->get('text_repay').'</a>';
                $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], 1, $comment);
            }
            if ($paymentModel->getClearCartBeforeRedirect()) {
                $this->cart->clear();
            }
        } else {
            $this->jsonError('Invalid payment type');
        }
    }

    /**
     * Экшен обработки нотификации для проведения capture платежа
     */
    public function capture()
    {
        if (!$this->getModel()->getKassaModel()->isEnabled()) {
            header('HTTP/1.1 403 Module disabled');

            return;
        }
        $source = file_get_contents('php://input');
        if (empty($source)) {
            header('HTTP/1.1 400 Empty request body');

            return;
        }
        $json = json_decode($source, true);
        if (empty($json)) {
            if (json_last_error() === JSON_ERROR_NONE) {
                $message = 'empty object in body';
            } else {
                $message = 'invalid object in body: '.$source;
            }
            $this->getModel()->log('warning', 'Invalid parameters in capture notification controller - '.$message);
            header('HTTP/1.1 400 Invalid json object in body');

            return;
        }

        $this->getModel()->log('info', 'Notification: '.$source);

        try {
            $notification = ($json['event'] === YandexCheckout\Model\NotificationEventType::PAYMENT_SUCCEEDED)
                ? new NotificationSucceeded($json)
                : new NotificationWaitingForCapture($json);

        } catch (\Exception $e) {
            $this->getModel()->log('error', 'Invalid notification object - '.$e->getMessage());
            header('HTTP/1.1 400 Invalid object in body');

            return;
        }
        $orderId = $this->getModel()->findOrderIdByPayment($notification->getObject());

        if ($orderId <= 0) {
            $this->getModel()->log('error', 'Order not exists for payment '.$notification->getObject()->getId());
            header('HTTP/1.1 404 Order not exists');

            return;
        }
        $this->load->model('checkout/order');
        $orderInfo = $this->model_checkout_order->getOrder($orderId);
        if (empty($orderInfo)) {
            $this->getModel()->log('warning', 'Empty order#'.$orderId.' in notification');
            header('HTTP/1.1 405 Invalid order payment method');
            exit();
        } elseif ($orderInfo['order_status_id'] <= 0) {
            $this->getModel()->confirmOrder($orderId, $notification->getObject());
        }
        $this->getModel()->updatePaymentInfo($notification->getObject()->getId());
        $result = null;
        if ($notification instanceof NotificationWaitingForCapture) {
            $payment = $this->getModel()->fetchPaymentInfo($notification->getObject()->getId());
            if ($payment === null) {
                header('HTTP/1.1 400 Payment capture error');
                $this->getModel()->log('error', 'Payment not captured: capture result is null');
            } elseif ($payment->getStatus() !== \YandexCheckout\Model\PaymentStatus::WAITING_FOR_CAPTURE) {
                header('HTTP/1.1 400 Invalid payment status');
                $this->getModel()->log('error',
                    'Payment not captured: invalid payment status "'.$payment->getStatus().'"');
            } else {
                $payment = $notification->getObject();
                if ($payment->getPaymentMethod()->getType() == \YandexCheckout\Model\PaymentMethodType::BANK_CARD) {
                    $this->getModel()->confirmOrder($orderId);
                    $kassa = $this->getModel()->getKassaModel();
                    $this->model_checkout_order->addOrderHistory(
                        $orderId,
                        $kassa->getHoldOrderStatusId(),
                        $this->language->get('text_payment_on_hold')
                    );
                } else {
                    try {
                        $this->getModel()->capturePayment($payment);
                    } catch (\YandexCheckout\Common\Exceptions\ApiException $e) {
                        $this->getModel()->log('error', 'Payment not captured: Code: "'.$e->getCode().'"');
                    }
                }
            }
        } elseif ($notification instanceof NotificationSucceeded) {
            $result = $this->getModel()->fetchPaymentInfo($notification->getObject()->getId());
            if ($result === null) {
                header('HTTP/1.1 400 Payment capture error');
                $this->getModel()->log('error', 'Payment not captured: capture result is null');
            } elseif ($result->getStatus() !== \YandexCheckout\Model\PaymentStatus::SUCCEEDED) {
                header('HTTP/1.1 400 Invalid payment status');
                $this->getModel()->log('error',
                    'Payment not captured: invalid payment status "'.$result->getStatus().'"');
            } else {
                $this->getModel()->confirmOrderPayment(
                    $orderId, $result, $this->getModel()->getKassaModel()->getSuccessOrderStatusId()
                );
            }
        }
        echo json_encode(array('success' => $result));
    }

    public function callback()
    {
        $data   = $_POST;
        $wallet = $this->getModel()->getWalletModel();
        $this->getModel()->log('info', 'callback:  request '.serialize($_REQUEST));
        $orderId = isset($data['label']) ? (int)$data['label'] : 0;
        if ($wallet->isEnabled()) {
            $this->getModel()->log('info', 'callback:  orderid='.$orderId);
            if ($this->getModel()->checkSign($data, $wallet->getPassword())) {
                $this->load->model('checkout/order');
                $this->model_checkout_order->addOrderHistory(
                    $orderId,
                    $wallet->getSuccessOrderStatusId(),
                    'Платёж номер подтверждён'
                );
            }
        } else {
            exit("You aren't Yandex.Money.");
        }
    }

    public function repay()
    {
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link(
                'extension/payment/yandex_money/repay', 'order_id='.$this->request->get['order_id'], true
            );
            $this->response->redirect($this->url->link('account/login', '', true));
        }
        $this->load->model('account/order');
        $order = $this->model_account_order->getOrder((int)$this->request->get['order_id']);
        if (empty($order)) {
            $this->response->redirect(
                $this->url->link('account/order/info', 'order_id='.$this->request->get['order_id'], true)
            );
        }
        $this->response->setOutput($this->payment($order, true));
    }

    public function productInfo()
    {
        $productId = !empty($this->request->post['id']) ?
            $this->request->post['id']
            : 0;

        $this->load->model('catalog/product');
        $product     = $this->model_catalog_product->getProduct($productId);
        $productInfo = array(
            'id'      => $product['product_id'],
            'name'    => (string)$product['name'],
            'price'   => (float)$product['price'],
            'brand'   => (string)$product['manufacturer'],
            'variant' => (string)$product['model'],
        );

        $this->response->setOutput(json_encode($productInfo));
    }

    /**
     * @throws Exception
     */
    public function market()
    {
        $this->load->model('catalog/product');
        $this->load->model('tool/image');
        $this->load->model('localisation/currency');

        $this->getModel();
        $model           = $this->getMarketModel();
        $categories      = $model->getCategories();
        $allCategoryIds  = array_map(function ($category) {
            return $category['category_id'];
        }, $categories);
        $allowCategories = (array)$this->config->get('yandex_money_market_category_list');
        $allowAllCat     = $this->config->get('yandex_money_market_category_all');
        if (!empty($allowCategories) || $allowAllCat) {
            $strCategoryIds = $allowAllCat ? null : implode(',', $allowCategories);
        } else {
            die("Need select categories");
        }
        $products         = $model->getProducts($strCategoryIds);
        $currencies       = $this->model_localisation_currency->getCurrencies();
        $offers_currency  = $this->config->get('config_currency');
        $currency_default = $model->getCurrencyByISO($offers_currency);
        if (!isset($currency_default['value'])) {
            die("Not exist RUB");
        }

        $market     = new YandexMarket();
        $currencies = array_intersect_key($currencies, array_flip(Currency::getAvailableCurrencies()));

        $market->setShop(
            $this->config->get('yandex_money_market_shopname'),
            $this->config->get('yandex_money_market_full_shopname'),
            $this->config->get('config_url')
        );
        $market->getShop()->setPlatform('ya_opencart');
        $market->getShop()->setVersion(VERSION);

        for ($index = 1; $index <= 5; $index++) {
            $enabled = $this->getConfig('delivery_enabled', $index);
            if ($enabled !== 'on') {
                continue;
            }
            $cost = $this->getConfig('delivery_cost', $index);
            if ($cost === '') {
                continue;
            }
            $daysFrom = $this->getConfig('delivery_days_from', $index);
            $daysTo   = $this->getConfig('delivery_days_to', $index);
            $days     = empty($daysTo) || $daysFrom === $daysTo ? $daysFrom : $daysFrom.'-'.$daysTo;
            if ($days === '') {
                continue;
            }
            $orderBefore = $this->getConfig('delivery_order_before', $index);
            $market->getShop()->addDeliveryOption($cost, $days, $orderBefore);
        }

        $cmsCurrencyIds = array_keys($this->model_localisation_currency->getCurrencies());
        foreach (Currency::getAvailableCurrencies() as $currencyId) {
            if (!in_array($currencyId, $cmsCurrencyIds)) {
                continue;
            }
            $enabled = $this->getConfig('currency_enabled', $currencyId);
            if ($enabled !== 'on') {
                continue;
            }
            if ($currencyId === $offers_currency) {
                $rate = '1';
                $plus = null;
            } else {
                $rate = $this->getConfig('currency_rate', $currencyId);
                if ($rate === '1') {
                    continue;
                }
                $plus = (float)$this->getConfig('currency_plus', $currencyId, 0.0);
                if ($rate === '__cms') {
                    if (!isset($currencies[$currencyId])) {
                        continue;
                    }
                    $rate = (float)$currency_default['value'] / (float)$currencies[$currencyId]['value'];
                }
            }
            $market->addCurrency($currencyId, $rate, $plus);
        }

        foreach ($categories as $category) {
            if ($this->config->get('yandex_money_market_category_all') !== 'on') {
                if (!in_array($category['category_id'], $allowCategories)) {
                    continue;
                }
            }
            $market->addCategory($category['name'], $category['category_id'], $category['parent_id']);
        }

        $additionalConditionIds     = array();
        $additionalConditionMap     = array();
        $additionalConditionEnabled = (array)$this->config->get('yandex_money_market_additional_condition_enabled');
        foreach ($additionalConditionEnabled as $id => $value) {
            if ($value === 'on') {
                $additionalConditionIds[] = $id;
            }
        }
        if (!empty($additionalConditionIds)) {
            foreach ($additionalConditionIds as $conditionId) {
                $additionalConditionCategoryIds
                    = $this->getConfig('additional_condition_for_all_cat', $conditionId) === 'on'
                    ? $allCategoryIds
                    : $this->getConfig('additional_condition_categories', $conditionId, array());
                foreach ($additionalConditionCategoryIds as $categoryId) {
                    $additionalConditionMap[$categoryId][] = $conditionId;
                }
            }
        }

        $nameTemplate = explode('%', $this->config->get('yandex_money_market_name_template'));
        foreach ($products as $product) {
            $statusId  = $product['quantity'] > 0 ? 'non-zero-quantity' : $product['stock_status_id'];
            $useStatus = $this->getConfig("available_enabled", $statusId) === 'on';
            $available = $this->getConfig("available_available", $statusId);
            if ($useStatus && $available === 'none') {
                continue;
            }

            $offer = $market->createOffer($product['product_id'], $product['category_id']);
            if (!$offer) {
                continue;
            }
            $offer
                ->setUrl(htmlspecialchars_decode($this->url->link('product/product',
                    'product_id='.$product['product_id'], true)))
                ->setModel($product['name'])
                ->setVendor($product['manufacturer'])
                ->setDescription($product['description'])
                ->setCurrencyId($currency_default['code']);

            if ($useStatus) {
                $offer
                    ->setAvailable($available === 'true')
                    ->setDelivery($this->getConfig("available_delivery", $statusId) === 'on')
                    ->setPickup($this->getConfig("available_pickup", $statusId) === 'on')
                    ->setStore($this->getConfig("available_store", $statusId) === 'on');
            }

            $offer->setPrice(round(floatval($product['price']), 2));
            if ($product['special'] && $product['special'] < $product['price']) {
                $offer->setOldPrice($offer->getPrice());
                $offer->setPrice(round(floatval($product['special']), 2));
            }

            if (isset($product['image'])) {
                $offer->addPicture($this->model_tool_image->resize($product['image'], 600, 600));
            }
            foreach ($this->model_catalog_product->getProductImages($product['product_id']) as $pic) {
                $offer->addPicture($this->model_tool_image->resize($pic['image'], 600, 600));
                if (count($offer->getPictures()) === 10) {
                    break;
                }
            }
            if ($product['weight'] > 0) {
                $offer->setWeight(number_format($product['weight'], 1, '.', ''), $product['weight_unit']);
            }
            if (($this->config->get('yandex_money_market_dimensions') === 'on')
                && $product['length'] > 0 && $product['width'] > 0 && $product['height'] > 0
            ) {
                $offer->setDimensions(number_format($product['length'], 1, '.', ''),
                    number_format($product['width'], 1, '.', ''), number_format($product['height'], 1, '.', ''));
            }

            if ($this->config->get('yandex_money_market_vat_enabled') === 'on') {
                $vatRates = $this->config->get('yandex_money_market_vat');
                if (isset($vatRates[$product['tax_class_id']])) {
                    $offer->setVat($vatRates[$product['tax_class_id']]);
                }
            }

            if ($this->config->get('yandex_money_market_simple')) {
                $name = '';
                foreach ($nameTemplate as $namePart) {
                    $name .= isset($product[$namePart]) ? $product[$namePart] : $namePart;
                }
                $offer->setName($name);
            }

            if ($this->config->get('yandex_money_market_features') === 'on') {
                $attributes = $this->model_catalog_product->getProductAttributes($product['product_id']);
                foreach ($attributes as $attr) {
                    foreach ($attr['attribute'] as $val) {
                        $offer->addParameter($val['name'], $val['text']);
                    }
                }
            }

            $extraCategories   = $model->getProductCategories($product['product_id']);
            $extraCategories[] = (string)$offer->getCategoryId();
            $allCategories     = array_unique($extraCategories);

            foreach ($allCategories as $category) {
                if (isset($additionalConditionMap[$category])) {
                    foreach ($additionalConditionMap[$category] as $conditionId) {
                        $tag       = $this->getConfig('additional_condition_tag', $conditionId);
                        $typeValue = $this->getConfig('additional_condition_type_value', $conditionId);
                        if ($typeValue === 'static') {
                            $value = $this->getConfig('additional_condition_static_value', $conditionId);

                        } else {
                            $dataValue = $this->getConfig('additional_condition_data_value', $conditionId);
                            $value     = isset($product[$dataValue]) ? $product[$dataValue] : '';
                        }
                        $join = $this->getConfig('additional_condition_join', $conditionId) === 'on';

                        if (!empty($tag) && $value !== '') {
                            $offer->addCustomTag($tag, $value, $join);
                        }
                    }
                }
            }

            if (!$this->makeOfferColorSizeCombination($offer, $product, $market)) {
                $offer->setPrice($this->formatPrice($offer->getPrice()));
                if ($offer->getOldPrice()) {
                    $offer->setOldPrice($this->formatPrice($offer->getOldPrice()));
                }
                $market->addOffer($offer);
            }
        }
        $this->response->addHeader('Content-Type: application/xml; charset=utf-8');
        $this->response->setOutput($market->getXml($this->config->get('yandex_money_market_simple')));
    }

    /**
     * @param Offer $commonOffer
     * @param $product
     * @param YandexMarket $market
     *
     * @return bool
     */
    private function makeOfferColorSizeCombination(
        $commonOffer,
        $product,
        $market
    ) {
        $colors = array();
        $sizes  = array();

        if ($this->config->get('yandex_money_market_option_color_enabled') === 'on') {
            $colorOptionId = $this->config->get('yandex_money_market_option_color_option_id');
            $colors        = $this->getMarketModel()->getProductOptions(array('option_id' => $colorOptionId),
                $product['product_id']);
        }

        if ($this->config->get('yandex_money_market_option_size_enabled') === 'on') {
            $sizeOptionId = $this->config->get('yandex_money_market_option_size_option_id');
            $sizes        = $this->getMarketModel()->getProductOptions(array('option_id' => $sizeOptionId),
                $product['product_id']);
        }

        if (!count($colors) && !count($sizes)) {
            return false;
        }

        /** @var Offer[] $colorOffers */
        $colorOffers = array();
        if (count($colors)) {
            foreach ($colors as $option) {
                $offer = clone $commonOffer;
                $offer->setGroupId($product['product_id']);
                $offer->setId($product['product_id'].'c'.$option['option_value_id']);
                $offer->setModel($offer->getModel().', '.$option['name']);
                $offer->addParameter($option['option_name'], $option['name']);
                $this->updateOfferPrice($offer, $option['price_prefix'], $option['price']);
                $this->updateOfferWeight($offer, $option, $product['weight_unit']);
                $offer->setUrl($offer->getUrl().'#'.$option['product_option_value_id']);
                $colorOffers[] = $offer;
            }
        } else {
            $colorOffers[] = $commonOffer;
        }

        foreach ($colorOffers as $colorOffer) {
            if (count($sizes)) {
                foreach ($sizes as $option) {
                    $offer = clone $colorOffer;
                    $offer->setGroupId($product['product_id']);
                    $offer->setId($offer->getId().'s'.$option['option_value_id']);
                    $offer->setModel($offer->getModel().', '.$option['name']);
                    $offer->addParameter($option['option_name'], $option['name']);
                    $this->updateOfferPrice($offer, $option['price_prefix'], $option['price']);
                    $this->updateOfferWeight($offer, $option, $product['weight_unit']);
                    $separator = count($colors) ? '-' : '#';
                    $offer->setUrl($offer->getUrl().$separator.$option['product_option_value_id']);
                    $offer->setPrice($this->formatPrice($offer->getPrice()));
                    if ($offer->getOldPrice()) {
                        $offer->setOldPrice($this->formatPrice($offer->getOldPrice()));
                    }
                    $market->addOffer($offer);
                }
            } else {
                $colorOffer->setPrice($this->formatPrice($colorOffer->getPrice()));
                if ($colorOffer->getOldPrice()) {
                    $colorOffer->setOldPrice($this->formatPrice($colorOffer->getOldPrice()));
                }
                $market->addOffer($colorOffer);
            }
        }

        return true;
    }

    /**
     * @param $price
     *
     * @return string
     */
    private function formatPrice($price)
    {
        $price = number_format((float)$price, 2, '.', '');

        return $price < 0 ? 0 : $price;
    }

    /**
     * @param Offer $offer
     * @param $price_prefix
     * @param $price
     */
    private function updateOfferPrice($offer, $price_prefix, $price)
    {
        if ($price_prefix === '+') {
            $offer->incPrice($price);
            if ($offer->getOldPrice()) {
                $offer->incOldPrice($price);
            }
        } elseif ($price_prefix === '-') {
            $offer->decPrice($price);
            if ($offer->getOldPrice()) {
                $offer->decOldPrice($price);
            }
        } elseif ($price_prefix === '=') {
            $offer->setPrice($price);
            if ($offer->getOldPrice()) {
                $offer->setOldPrice(0);
            }
        }
    }

    /**
     * @param Offer $offer
     * @param array $option
     * @param string $weightUnit
     */
    private function updateOfferWeight($offer, $option, $weightUnit)
    {
        if (!isset($option['weight']) || !isset($option['weight_prefix'])) {
            return;
        }
        $weight = $offer->getWeight();
        if (!$weight) {
            return;
        }
        if ($option['weight_prefix'] === '+') {
            $weight += $option['weight'];
        } elseif ($option['weight_prefix'] === '-') {
            $weight -= $option['weight'];
        }

        $offer->setWeight($weight, $weightUnit);
    }

    private function setOptionedWeight($product, $option)
    {
        if (isset($option['weight']) && isset($option['weight_prefix'])) {
            foreach ($product['param'] as $i => $param) {
                if (isset($param['id']) && ($param['id'] == 'WEIGHT')) {
                    if ($option['weight_prefix'] == '+') {
                        $product['param'][$i]['value'] += $option['weight'];
                    } elseif ($option['weight_prefix'] == '-') {
                        $product['param'][$i]['value'] -= $option['weight'];
                    }
                    break;
                }
            }
        }

        return $product;
    }

    /**
     * @return ModelExtensionPaymentYandexMoney
     */
    public function getModel()
    {
        if ($this->_model === null) {
            $this->load->model('extension/payment/yandex_money');
            $this->_model = $this->model_extension_payment_yandex_money;
        }

        return $this->_model;
    }

    /**
     * @return \YandexMoneyModule\Model\MarketModel
     */
    private function getMarketModel()
    {
        if ($this->_marketModel === null) {
            $this->load->model('extension/payment/yandex_money');
            $this->_marketModel = new \YandexMoneyModule\Model\MarketModel($this->registry);
        }

        return $this->_marketModel;
    }

    /**
     * @param array $array
     * @param string $key
     * @param null $default
     *
     * @return null
     */
    private function array_get($array, $key, $default = null)
    {
        return isset($array[$key]) ? $array[$key] : $default;
    }

    /**
     * @param $key
     * @param $index
     * @param null $default
     *
     * @return null
     */
    private function getConfig($key, $index = null, $default = null)
    {
        return $index === null
            ? $this->config->get('yandex_money_market_'.$key)
            : $this->array_get(
                $this->config->get('yandex_money_market_'.$key),
                $index,
                $default
            );
    }
}
