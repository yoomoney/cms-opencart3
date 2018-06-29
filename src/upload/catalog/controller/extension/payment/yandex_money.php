<?php

use YandexCheckout\Model\Notification\NotificationSucceeded;
use YandexCheckout\Model\Notification\NotificationWaitingForCapture;

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
        $this->load->language('extension/payment/' . self::MODULE_NAME);
        $this->document->setTitle($this->language->get('heading_title'));

        $model = $this->getModel()->getPaymentModel();
        if ($model === null) {
            $this->failure('Yandex.Kassa module disabled');
        }

        if ($model->getMinPaymentAmount() > 0 && $model->getMinPaymentAmount() > $this->cart->getSubTotal()) {
            $this->failure(sprintf($this->language->get('error_minimum'), $this->currency->format($model->getMinPaymentAmount(), $this->session->data['currency'])));
        }

        $this->load->model('checkout/order');
        $orderInfo = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $template = $model->applyTemplateVariables($this, $data, $orderInfo);

        $data['language'] = $this->language;
        $data['fullView'] = false;

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        return $this->load->view($template, $data);
    }

    private function payment($orderInfo, $fullView = false)
    {
        $this->load->language('extension/payment/' . self::MODULE_NAME);
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
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');
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
                'extension/payment/yandex_money/repay', 'order_id=' . $orderId, 'SSL'
            );
            $this->response->redirect($this->url->link('account/login', '', true));
        }

        $this->load->model('account/order');
        $order = $this->model_account_order->getOrder($orderId);
        if (empty($order)) {
            $this->response->redirect(
                $this->url->link('account/order/info', 'order_id=' . $orderId, true)
            );
        }

        $query = $this->db->query("SELECT `payment_code`, `order_status_id` FROM `" . DB_PREFIX . "order` WHERE order_id = '" . $orderId . "'");
        if (empty($query)) {
            $this->response->redirect(
                $this->url->link('account/order/info', 'order_id=' . $orderId, true)
            );
        }
        if ($query->row['payment_code'] !== 'yandex_money') {
            $this->session->data['error'] = 'Не верный способ оплаты заказа';
            $this->response->redirect(
                $this->url->link('account/order/info', 'order_id=' . $orderId, true)
            );
        }
        if ($query->row['order_status_id'] == $kassa->getSuccessOrderStatusId()) {
            $this->session->data['error'] = 'Заказ уже оплачен';
            $this->response->redirect(
                $this->url->link('account/order/info', 'order_id=' . $orderId, true)
            );
        }

        $this->getModel()->log('info', 'Создание платежа для заказа №' . $orderId);

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
            $this->url->link('account/order/info', 'order_id=' . $orderId, true)
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

    private function jsonError($message)
    {
        $this->getModel()->log('info', $message);
        echo json_encode(array(
            'success' => false,
            'error' => $message,
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
        $this->getModel()->log('info', 'Создание платежа для заказа №' . $orderId);
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
        $result = array(
            'success' => true,
            'redirect' => $this->url->link('checkout/success', '', true),
        );
        $confirmation = $payment->getConfirmation();
        if ($confirmation !== null && $confirmation->getType() === \YandexCheckout\Model\ConfirmationType::REDIRECT) {
            $result['redirect'] = $confirmation->getConfirmationUrl();
        }

        if ($kassa->getCreateOrderBeforeRedirect()) {
            $this->getModel()->log('debug', 'Confirm order#' . $orderId . ' after payment creation');
            $this->getModel()->confirmOrder($orderId, $payment);
        }
        if ($kassa->getClearCartBeforeRedirect()) {
            $this->getModel()->log('debug', 'Clear order#' . $orderId . ' cart after payment creation');
            $this->cart->clear();
        }

        $output = ob_get_clean();
        if (!empty($output)) {
            $this->getModel()->log('warning', 'Non empty buffer: ' . $output);
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
        $this->load->language('extension/payment/' . self::MODULE_NAME);
        if (empty($_GET['order_id'])) {
            $this->failure('Не был передан идентификатор платежа');
        }
        $this->getModel()->log('info', 'Подтверждение платежа для заказа №' . $_GET['order_id']);
        $kassa = $this->getModel()->getKassaModel();
        if (!$kassa->isEnabled()) {
            $this->failure('Модуль оплаты выключен');
        }
        $orderId = (int)$_GET['order_id'];
        $paymentId = $this->getModel()->findPaymentIdByOrderId($orderId);
        if (empty($paymentId)) {
            $this->failure('Не удалось получить ID платежа для заказа №' . $orderId);
        }
        $this->load->model('checkout/order');
        $payment = $this->getModel()->updatePaymentInfo($paymentId);
        if ($payment === null) {
            $this->failure('Не найден платёж ' . $paymentId . ' для заказа №' . $orderId);
        } elseif (!$payment->getPaid()) {
            $this->failure('Платёж не был проведён');
        } elseif ($payment->getStatus() === \YandexCheckout\Model\PaymentStatus::CANCELED) {
            $this->failure('Статус платежа ' . $paymentId . ' заказа №' . $orderId . ' - canceled');
        } elseif ($payment->getStatus() !== \YandexCheckout\Model\PaymentStatus::SUCCEEDED) {
            $this->getModel()->log('info', 'Confirm order#' . $orderId . ' with payment ' . $payment->getId());
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
        $this->load->language('extension/payment/' . self::MODULE_NAME);
        if (empty($_GET['payment_type'])) {
            $this->jsonError('Unknown payment type');
        }

        $type = $_GET['payment_type'];
        if ($type !== 'wallet' && $type !== 'card') {
            $this->jsonError('Invalid payment type');
        }

        $paymentModel = $this->getModel()->getPaymentModel();
        if ($paymentModel instanceof \YandexMoneyModule\Model\WalletModel) {
            if ($paymentModel->getCreateOrderBeforeRedirect()) {
                $this->load->model('checkout/order');
                $url = $this->url->link('extension/payment/yandex_money/repay', 'order_id=' . $this->session->data['order_id'], true);
                $comment = '<a href="' . $url . '" class="button">' . $this->language->get('text_repay') . '</a>';
                $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], 1, $comment);
            }
            if ($paymentModel->getClearCartBeforeRedirect()) {
                $this->cart->clear();
            }
        } else {
            $this->load->model('checkout/order');
            $url = $this->url->link('extension/payment/yandex_money/repay', 'order_id=' . $this->session->data['order_id'], true);
            $comment = '<a href="' . $url . '" class="button">' . $this->language->get('text_repay') . '</a>';
            $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], 1, $comment);
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
                $message = 'invalid object in body: ' . $source;
            }
            $this->getModel()->log('warning', 'Invalid parameters in capture notification controller - ' . $message);
            header('HTTP/1.1 400 Invalid json object in body');
            return;
        }

        $this->getModel()->log('info', 'Notification: ' . $source);

        try {
            $notification = ($json['event'] === YandexCheckout\Model\NotificationEventType::PAYMENT_SUCCEEDED)
                ? new NotificationSucceeded($json)
                : new NotificationWaitingForCapture($json);

        } catch (\Exception $e) {
            $this->getModel()->log('error', 'Invalid notification object - ' . $e->getMessage());
            header('HTTP/1.1 400 Invalid object in body');
            return;
        }
        $orderId = $this->getModel()->findOrderIdByPayment($notification->getObject());

        if ($orderId <= 0) {
            $this->getModel()->log('error', 'Order not exists for payment ' . $notification->getObject()->getId());
            header('HTTP/1.1 404 Order not exists');
            return;
        }
        $this->load->model('checkout/order');
        $orderInfo = $this->model_checkout_order->getOrder($orderId);
        if (empty($orderInfo)) {
            $this->getModel()->log('warning', 'Empty order#' . $orderId . ' in notification');
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
        $data = $_POST;
        $wallet = $this->getModel()->getWalletModel();
        $this->getModel()->log('info', 'callback:  request ' . serialize($_REQUEST));
        $orderId = isset($data['label']) ? (int)$data['label'] : 0;
        if ($wallet->isEnabled()) {
            $this->getModel()->log('info', 'callback:  orderid='.$orderId);
            if($this->getModel()->checkSign($data, $wallet->getPassword())) {
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
                'extension/payment/yandex_money/repay', 'order_id=' . $this->request->get['order_id'], true
            );
            $this->response->redirect($this->url->link('account/login', '', true));
        }
        $this->load->model('account/order');
        $order = $this->model_account_order->getOrder((int)$this->request->get['order_id']);
        if (empty($order)) {
            $this->response->redirect(
                $this->url->link('account/order/info', 'order_id=' . $this->request->get['order_id'], true)
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

    public function market()
    {
        $this->load->model('catalog/product');
        $this->load->model('tool/image');
        $this->load->model('localisation/currency');

        $this->getModel();
        $model = $this->getMarketModel();
        $categories = $model->getCategories();
        $allow_cat_array = explode(',', $this->config->get('yandex_money_market_categories'));
        if (!empty($allow_cat_array) || $this->config->get('yandex_money_market_catall')) {
            $ids_cat = ($this->config->get('yandex_money_market_catall')) ? '' : implode(',', $allow_cat_array);
        } else {
            die("Need select categories");
        }
        $products = $model->getProducts($ids_cat);
        $currencies = $this->model_localisation_currency->getCurrencies();
        $shop_currency = $this->config->get('config_currency');
        $offers_currency = 'RUB';
        $currency_default = $model->getCurrencyByISO($offers_currency);
        if (!isset($currency_default['value'])){
            die("Not exist RUB");
        }

        $decimal_place = 2;
        $currencies = array_intersect_key($currencies, array_flip(array('RUR', 'RUB', 'USD', 'EUR', 'UAH', 'BYN')));
        $market = new \YandexMoneyModule\YandexMarket($this->config);
        $market->yml('utf-8');
        $market->set_shop(
            $this->config->get('yandex_money_market_shopname'),
            $this->config->get('config_name'),
            $this->config->get('config_url')
        );

        if ($this->config->get('yandex_money_market_allcurrencies')) {
            foreach ($currencies as $currency) {
                if ($currency['status'] == 1) {
                    $market->add_currency($currency['code'], ((float)$currency_default['value'] / (float)$currency['value']));
                }
            }
        }
        else {
            $market->add_currency($currency_default['code'], ((float)$currency_default['value']));
        }

        foreach ($categories as $category) {
            if (!$this->config->get('yandex_money_market_catall')) {
                if (!in_array($category['category_id'], $allow_cat_array)) {
                    continue;
                }
            }
            $market->add_category($category['name'], $category['category_id'], $category['parent_id']);
        }
        foreach ($products as $product) {
            if ($this->config->get('yandex_money_market_available') && $product['quantity'] < 1) {
                continue;
            }

            $available = false;
            if ($this->config->get('yandex_money_market_set_available') == 1) {
                $available = true;
            } elseif ($this->config->get('yandex_money_market_set_available') == 2) {
                if ($product['quantity'] > 0) $available = true;
            } elseif ($this->config->get('yandex_money_market_set_available') == 3) {
                $available = true;
                if ($product['quantity'] == 0) {
                    continue;
                }
            } elseif ($this->config->get('yandex_money_market_set_available') == 4) {
                $available = false;
            }
            $data = array();
            $data['id'] = $product['product_id'];
            $data['available'] = $available;
            $data['url'] = htmlspecialchars_decode($this->url->link('product/product', 'product_id=' . $product['product_id'], true));

            $data['price'] = round(floatval($product['price']), 2);
            if ($product['special'] && $product['special'] < $product['price']){
                $data['price'] = round(floatval($product['special']), 2);
                $data['oldprice'] = round(floatval($product['price']), 2);
            }

            $data['currencyId'] = $currency_default['code'];
            $data['categoryId'] = $product['category_id'];
            $data['vendor'] = $product['manufacturer'];
            $data['vendorCode'] = $product['model'];
            $data['delivery'] = ($this->config->get('yandex_money_market_delivery') && $product['shipping'] == '1')? 'true' : 'false';
            $data['pickup'] = ($this->config->get('yandex_money_market_pickup') ? 'true' : 'false');
            $data['store'] = ($this->config->get('yandex_money_market_store') ? 'true' : 'false');
            $data['description'] = $product['description'];

            if ($product['quantity'] < 1) {
                $stock_id = $product['stock_status_id'];
                $delivery_cost = $this->config->get('yandex_money_market_stock_cost');
                $delivery_days = $this->config->get('yandex_money_market_stock_days');
                $data['delivery-options'][] = array(
                    'cost' => $delivery_cost[$stock_id],
                    'days' => $delivery_days[$stock_id]
                );
            }
            if ($product['minimum'] > 1) {
                $data['sales_notes'] = 'Минимальное кол-во для заказа: ' . $product['minimum'];
            }
            if ($this->config->get('config_comment')) {
                $data['sales_notes'] = $this->config->get('config_comment');
            }

            $data['picture'] = array();
            if (isset($product['image'])) {
                $data['picture'][] = str_replace(" ", "%20", $this->model_tool_image->resize($product['image'], 600, 600));
            }
            foreach ($this->model_catalog_product->getProductImages($data['id']) as $pic) {
                if (count($data['picture']) <= 9) {
                    $data['picture'][] = str_replace(array('&amp;',' '),array('&', '%20'), $this->model_tool_image->resize($pic['image'], 600, 600));
                }
            }

            if ($this->config->get('yandex_money_market_prostoy')) {
                $data['price'] = number_format($this->currency->convert($this->tax->calculate($data['price'], $product['tax_class_id'], $this->config->get('config_tax')), $shop_currency, $offers_currency), $decimal_place, '.', '');
                $data['name'] = $product['name'];
                if ($data['price'] > 0) {
                    $market->add_offer($data['id'], $data, $data['available']);
                }
            } else {
                $data['model'] = $product['name'];
                if ($product['weight'] > 0) {
                    $data['weight'] = number_format($product['weight'], 1, '.', '');
                }
                if ($this->config->get('yandex_money_market_dimensions') && $product['length'] > 0 && $product['width'] > 0 && $product['height'] > 0) {
                    $data['dimensions'] = number_format($product['length'], 1, '.', '') . '/' . number_format($product['width'], 1, '.', '') . '/' . number_format($product['height'], 1, '.', '');
                }
                $data['downloadable'] = 'false';
                $data['rec'] = explode(',', $product['rel']);
                $data['param'][] = array('id' => 'weight', 'name' => 'Вес', 'value' => number_format($product['weight'], 1, '.', ''), 'unit' => $product['weight_unit']);
                if ($this->config->get('yandex_money_market_features')) {
                    $attributes = $this->model_catalog_product->getProductAttributes($data['id']);
                    if (count($attributes)) {
                        foreach ($attributes as $attr) {
                            foreach ($attr['attribute'] as $val) {
                                $data['param'][] = array(
                                    'id' => $val['attribute_id'],
                                    'name' => $val['name'],
                                    'value' => $val['text']
                                );
                            }
                        }
                    }
                }

                if (!$this->makeOfferCombination($data, $product, $shop_currency, $offers_currency, $decimal_place, $market)) {
                    $data['price'] = number_format(
                        $this->currency->convert(
                            $this->tax->calculate(
                                $data['price'], $product['tax_class_id'], $this->config->get('config_tax')
                            ),
                            $shop_currency,
                            $offers_currency
                        ),
                        $decimal_place,
                        '.',
                        ''
                    );
                    if ($data['price'] > 0) {
                        $market->add_offer($data['id'], $data, $data['available']);
                    }
                }
            }
        }
        $this->response->addHeader('Content-Type: application/xml; charset=utf-8');
        $this->response->setOutput($market->get_xml());
    }

    private function makeOfferCombination($data, $product, $shop_currency, $offers_currency, $decimal_place, $object)
    {
        $colors = array();
        $sizes = array();

        $opts = $this->config->get('yandex_money_market_color_options');
        if (!empty($opts) && is_array($opts)) {
            $colors = $this->getMarketModel()->getProductOptions($opts, $product['product_id']);
        }
        $opts = $this->config->get('yandex_money_market_size_options');
        if (!empty($opts) && is_array($opts)) {
            $sizes = $this->getMarketModel()->getProductOptions($opts, $product['product_id']);
        }

        if (!count($colors) && !count($sizes)) {
            return false;
        }

        if(count($colors)) {
            foreach ($colors as $option) {
                $data_temp = $data;
                $data_temp['model'].= ', '.$option['option_name'].' '.$option['name'];
                $data_temp['param'][] = array('name' => $option['option_name'], 'value' => $option['name']);
                $data_temp['id'] = $product['product_id'].'c'.$option['option_value_id'];
                $data_temp['available'] = $data['available'];
                if ($option['price_prefix'] == '+') {
                    $data_temp['price']+= $option['price'];
                    if (isset($data_temp['oldprice'])) {
                        $data_temp['oldprice'] += $option['price'];
                    }
                }
                elseif ($option['price_prefix'] == '-') {
                    $data_temp['price']-= $option['price'];
                    if (isset($data_temp['oldprice'])) {
                        $data_temp['oldprice'] -= $option['price'];
                    }
                }
                elseif ($option['price_prefix'] == '=') {
                    $data_temp['price'] = $option['price'];
                }
                $data_temp = $this->setOptionedWeight($data_temp, $option);
                $data_temp['url'].= '#'.$option['product_option_value_id'];
                $colors_array[] = $data_temp;
            }
        } else {
            $colors_array[] = $data;
        }

        unset($data_temp);
        unset($option);
        foreach($colors_array as $i => $data) {
            if (count($sizes)) {
                foreach ($sizes as $option) {
                    $data_temp = $data;
                    $data_temp['id'] .= 'c' . $option['option_value_id'];
                    $data_temp['model'] .= ', ' . $option['option_name'] . ' ' . $option['name'];
                    $data_temp['param'][] = array('name' => $option['option_name'], 'value' => $option['name']);
                    $data_temp['available'] = $data['available'];
                    if ($option['price_prefix'] == '+') {
                        $data_temp['price'] += $option['price'];
                        if (isset($data_temp['oldprice']))
                            $data_temp['oldprice'] += $option['price'];
                    } elseif ($option['price_prefix'] == '-') {
                        $data_temp['price'] -= $option['price'];
                        if (isset($data_temp['oldprice']))
                            $data_temp['oldprice'] -= $option['price'];
                    } elseif ($option['price_prefix'] == '=') {
                        $data_temp['price'] = $option['price'];
                    }

                    $data_temp = $this->setOptionedWeight($data_temp, $option);
                    if (count($colors)) {
                        $data_temp['url'] .= '-' . $option['product_option_value_id'];
                    } else {
                        $data_temp['url'] .= '#' . $option['product_option_value_id'];
                    }

                    $data_temp['price'] = number_format($this->currency->convert($this->tax->calculate($data_temp['price'], $product['tax_class_id'], $this->config->get('config_tax')), $shop_currency, $offers_currency), $decimal_place, '.', '');
                    if (isset($data_temp['oldprice'])) {
                        $data_temp['oldprice'] = number_format($this->currency->convert($this->tax->calculate($data_temp['oldprice'], $product['tax_class_id'], $this->config->get('config_tax')), $shop_currency, $offers_currency), $decimal_place, '.', '');
                    }
                    if ($data['price'] > 0) {
                        $object->add_offer($data_temp['id'], $data_temp, $data_temp['available'], $product['product_id']);
                    }
                    unset($data_temp);
                }
            } else {
                $data['price'] = number_format($this->currency->convert($this->tax->calculate($data['price'], $product['tax_class_id'], $this->config->get('config_tax')), $shop_currency, $offers_currency), $decimal_place, '.', '');
                if ($data['price'] > 0) {
                    $object->add_offer($data['id'], $data, $data['available'], $product['product_id']);
                }
            }
        }
        return true;
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
    private function getModel()
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
}
