<?php

use YooKassa\Model\ConfirmationType;
use YooKassa\Model\Notification\NotificationFactory;
use YooKassa\Model\Notification\NotificationRefundSucceeded;
use YooKassa\Model\Notification\NotificationSucceeded;
use YooKassa\Model\Notification\NotificationWaitingForCapture;
use YooKassa\Model\NotificationEventType;
use YooKassa\Model\PaymentMethodType;
use YooKassa\Model\PaymentStatus;
use YooMoneyModule\Model\KassaModel;

/**
 * Класс контроллера модуля оплаты с помощью ЮMoney
 *
 * @property ModelExtensionPaymentYoomoney $model_extension_payment_yoomoney
 * @property ModelPaymentYoomoney $model_payment_yoomoney
 * @property ModelCheckoutOrder $model_checkout_order
 * @property ModelAccountOrder $model_account_order
 * @property \Cart\Customer $customer
 */
class ControllerExtensionPaymentYoomoney extends Controller
{
    const MODULE_NAME = 'yoomoney';
    const MODULE_VERSION = '2.1.3';

    /**
     * @var ModelExtensionPaymentYoomoney
     */
    private $_model;

    /**
     * Экшен генерирующий страницу оплаты с помощью ЮMoney
     * @return string
     */
    public function index()
    {
        $this->load->language('extension/payment/'.self::MODULE_NAME);
        $this->document->setTitle($this->language->get('heading_title'));

        if (isset($this->session->data['confirmation_token'])) {
            $this->session->data['confirmation_token'] = null;
        }
        $model = $this->getModel()->getPaymentModel();
        if ($model === null) {
            $this->failure('YooKassa module disabled');
        }

        if ($model->getMinPaymentAmount() > 0 && $model->getMinPaymentAmount() > $this->cart->getSubTotal()) {
            $this->failure(sprintf($this->language->get('error_minimum'),
                $this->currency->format($model->getMinPaymentAmount(), $this->session->data['currency'])));
        }

        $this->load->model('checkout/order');
        $orderInfo = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $template  = $model->applyTemplateVariables($this, $data, $orderInfo);

        if ($this->currency->has('RUB')) {
            $data['amount'] = sprintf('%.2f', $this->currency->format($orderInfo['total'], 'RUB', '', false));
        } else {
            $data['amount'] = $this->getModel()->convertFromCbrf($orderInfo, 'RUB');
        }

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
     * @param $orderInfo
     * @param bool $fullView
     * @return mixed
     */
    private function payment($orderInfo, $fullView = false)
    {
        $this->load->language('extension/payment/'.self::MODULE_NAME);
        $this->document->setTitle($this->language->get('heading_title'));

        $model = $this->getModel()->getPaymentModel();
        if (!$model->isEnabled()) {
            $this->failure('YooKassa module disabled');
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
            $this->failure('YooKassa module disabled');
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
                'extension/payment/yoomoney/repay', 'order_id='.$orderId, 'SSL'
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
        if ($query->row['payment_code'] !== 'yoomoney') {
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
        } elseif ($payment->getStatus() === PaymentStatus::CANCELED) {
            $this->failure('Платеж не прошел. Попробуйте еще или выберите другой способ оплаты');
        }
        $confirmation = $payment->getConfirmation();
        if ($confirmation !== null && $confirmation->getType() === ConfirmationType::REDIRECT) {
            $this->response->redirect($confirmation->getConfirmationUrl());
        }
        $this->session->data['error'] = 'Не удалось инициализировать платёж';
        $this->response->redirect(
            $this->url->link('account/order/info', 'order_id='.$orderId, true)
        );
    }

    /**
     * @param $error
     * @param bool $display
     */
    public function failure($error, $display = true)
    {
        if ($display) {
            $this->session->data['error'] = $error;
        }
        $this->getModel()->log('info', $error);
        $this->response->redirect($this->url->link('checkout/checkout', '', true));
    }

    /**
     * @param $message
     */
    public function jsonError($message)
    {
        $this->getModel()->log('error', $message);
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
            $this->jsonError('YooKassa module disabled');
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
        $successUrl = $this->url->link('checkout/success', '', true);

        if ($paymentMethod === KassaModel::CUSTOM_PAYMENT_METHOD_WIDGET
            && !empty($this->session->data['confirmation_token'])) {
            echo json_encode(array(
                'success' => true,
                'redirect' => $successUrl,
                'token' => $this->session->data['confirmation_token'],
            ));
            exit();
        }

        if ($kassa->getEPL()) {
            if (!empty($paymentMethod) && $paymentMethod !== PaymentMethodType::INSTALLMENTS) {
                $this->jsonError('Invalid payment method');
            }
        } elseif (!$kassa->isPaymentMethodEnabled($paymentMethod)) {
            $this->jsonError('Invalid payment method');
        } elseif ($paymentMethod == PaymentMethodType::QIWI) {
            $phone = isset($_GET['qiwiPhone']) ? preg_replace('/[^\d]/', '', $_GET['qiwiPhone']) : '';
            if (empty($phone)) {
                $this->jsonError('Не был указан телефон');
            }
        } elseif ($paymentMethod == PaymentMethodType::ALFABANK) {
            $login = isset($this->request->get['alphaLogin']) ? trim($this->request->get['alphaLogin']) : '';
            if (empty($login)) {
                $this->jsonError('Не указан логин в Альфа-клике');
            }
        }

        $payment = $this->getModel()->createPayment($orderId, $paymentMethod);

        if ($payment === null) {
            $this->jsonError('Платеж не прошел. Попробуйте еще или выберите другой способ оплаты');
        } elseif ($payment->getStatus() === PaymentStatus::CANCELED) {
            $this->jsonError('Платеж не прошел. Попробуйте еще или выберите другой способ оплаты');
        }

        $result = array(
            'success'  => true,
            'redirect' => $successUrl,
        );

        $confirmation = $payment->getConfirmation();

        if ($confirmation !== null) {
            if ($confirmation->getType() === ConfirmationType::REDIRECT) {
                $result['redirect'] = $confirmation->getConfirmationUrl();
            } elseif ($confirmation->getType() === ConfirmationType::EMBEDDED) {
                $result['token'] = $confirmation->getConfirmationToken();
                $this->session->data['confirmation_token'] = $result['token'];
            }
        }

        if ($kassa->getCreateOrderBeforeRedirect()) {
            $this->getModel()->log('info', 'Confirm order#'.$orderId.' after payment creation');
            $this->getModel()->confirmOrder($orderId);
        }
        if ($kassa->getClearCartBeforeRedirect()) {
            $this->getModel()->log('info', 'Clear order#'.$orderId.' cart after payment creation');
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
     * Экшен вызываемый при возврате пользователя из ЮKassa, проверяет статус платежа, добавляет в историю заказа
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
        } elseif ($payment->getStatus() === PaymentStatus::CANCELED) {
            $this->failure('Статус платежа '.$paymentId.' заказа №'.$orderId.' - canceled');
        } elseif ($payment->getStatus() !== PaymentStatus::SUCCEEDED) {
            $this->getModel()->log('info', 'Confirm order#'.$orderId.' with payment '.$payment->getId());
        }
        if ($payment->getStatus() === PaymentStatus::SUCCEEDED) {
            $this->getModel()->confirmOrderPayment(
                $orderId, $payment, $this->getModel()->getKassaModel()->getSuccessOrderStatusId()
            );
        }
        $this->response->redirect($this->url->link('checkout/success', '', true));
    }

    public function validate()
    {
        $this->load->language('extension/payment/'.self::MODULE_NAME);
        if (empty($_POST['paymentType'])) {
            $this->jsonError('Unknown payment type');
        }

        $type = $_POST['paymentType'];
        $paymentModel = $this->getModel()->getPaymentModel();
        $this->getModel()->log('info', 'type: ' . $type);

        if ($paymentModel instanceof \YooMoneyModule\Model\WalletModel) {
            if ($type !== 'AC' && $type !== 'PC') {
                $this->jsonError('Invalid payment type');
            }

            $this->load->model('checkout/order');
            $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
            $this->getModel()->log('info', 'post: ' . print_r(array($order_info, $_POST), true));
            if ($this->currency->has('RUB')) {
                $orderAmount = sprintf('%.2f', $this->currency->format($order_info['total'], 'RUB', '', false));
            } else {
                $orderAmount = sprintf('%.2f', $this->getModel()->convertFromCbrf($order_info, 'RUB'));
            }
            if ((float)$_POST['sum'] != (float)$orderAmount) {
                $this->jsonError('Invalid total amount');
            }

            if ($paymentModel->getCreateOrderBeforeRedirect()) {
                $url     = $this->url->link('extension/payment/yoomoney/repay',
                    'order_id='.$this->session->data['order_id'], true);
                $comment = '<a href="'.$url.'" class="button">'.$this->language->get('text_repay').'</a>';
                $this->getModel()->log('info', 'Create order - ' . $this->session->data['order_id']);
                $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], 1, $comment);
            }

            if ($paymentModel->getClearCartBeforeRedirect()) {
                $this->cart->clear();
            }
            echo json_encode(array('success' => true));
        } else {
            $this->jsonError('Invalid payment type');
        }
        exit();
    }

    public function resetToken()
    {
        $success = false;
        if (isset($this->session->data['confirmation_token'])) {
            $this->session->data['confirmation_token'] = null;
            $success = true;
        }

        echo json_encode(array(
            'success' => $success,
        ));
    }

    /**
     * Экшен обработки нотификации для проведения capture платежа
     */
    public function capture()
    {
        if (!$this->getModel()->getKassaModel()->isEnabled()) {
            header('HTTP/1.1 403 Module disabled');
            exit();
        }
        $source = file_get_contents('php://input');
        if (empty($source)) {
            header('HTTP/1.1 400 Empty request body');
            exit();
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
            exit();
        }

        $this->getModel()->log('info', 'Notification: '.$source);

        try {
            $factory = new NotificationFactory();
            $notification = $factory->factory($json);
        } catch (\Exception $e) {
            $this->getModel()->log('error', 'Invalid notification object - '.$e->getMessage());
            header('HTTP/1.1 400 Invalid object in body');
            exit();
        }

        $paymentId = $notification instanceof NotificationRefundSucceeded
                   ? $notification->getObject()->getPaymentId()
                   : $notification->getObject()->getId();

        $orderId = $this->getModel()->findOrderIdByPayment($paymentId);
        if ($orderId <= 0) {
            $this->getModel()->log('error', 'Order not exists for payment ' . $paymentId);
            exit();
        }

        if ($notification->getEvent() === NotificationEventType::REFUND_SUCCEEDED) {
            $this->getModel()->log('info', 'Refund success for order #'.$orderId);
            exit();
        }

        if ($notification->getEvent() === NotificationEventType::PAYMENT_CANCELED) {
            $this->getModel()->log('info', 'Payment for order #'.$orderId.' cancelled');
            exit();
        }

        $this->load->model('checkout/order');
        $orderInfo = $this->model_checkout_order->getOrder($orderId);
        if (empty($orderInfo)) {
            $this->getModel()->log('warning', 'Empty order #'.$orderId.' in notification');
            exit();
        } elseif ($orderInfo['order_status_id'] <= 0) {
            $this->getModel()->confirmOrder($orderId, $notification->getObject());
        }
        $this->getModel()->updatePaymentInfo($notification->getObject()->getId());

        $result = null;
        if ($notification instanceof NotificationWaitingForCapture) {
            $payment = $this->getModel()->fetchPaymentInfo($notification->getObject()->getId());
            if ($payment === null) {
                $this->getModel()->log('error', 'Payment not captured: capture result is null');
            } elseif ($payment->getStatus() !== PaymentStatus::WAITING_FOR_CAPTURE) {
                $this->getModel()->log('error',
                    'Payment not captured: invalid payment status "'.$payment->getStatus().'"');
            } else {
                $payment = $notification->getObject();
                $capturePaymentMethods = array(
                    PaymentMethodType::BANK_CARD,
                    PaymentMethodType::YOO_MONEY,
                    PaymentMethodType::GOOGLE_PAY,
                    PaymentMethodType::APPLE_PAY,
                );
                if (in_array($payment->getPaymentMethod()->getType(), $capturePaymentMethods)) {
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
                    } catch (\YooKassa\Common\Exceptions\ApiException $e) {
                        $this->getModel()->log('error', 'Payment not captured: Code: "'.$e->getCode().'"');
                    }
                }
            }
        } elseif ($notification instanceof NotificationSucceeded) {
            $result = $this->getModel()->fetchPaymentInfo($notification->getObject()->getId());
            if ($result === null) {
                $this->getModel()->log('error', 'Payment not captured: capture result is null');
            } elseif ($result->getStatus() !== PaymentStatus::SUCCEEDED) {
                $this->getModel()->log('error',
                    'Payment not captured: invalid payment status "'.$result->getStatus().'"');
            } else {
                $this->getModel()->confirmOrderPayment(
                    $orderId, $result, $this->getModel()->getKassaModel()->getSuccessOrderStatusId()
                );
            }
        }

        echo json_encode(array('success' => $result));
        exit();
    }

    public function callback()
    {
        $data   = $_POST;
        $wallet = $this->getModel()->getWalletModel();
        $this->getModel()->log('info', "callback:  request \n" . print_r($_REQUEST, true));
        $orderId = !empty($data['label']) ? (int)$data['label'] : 0;
        if ($wallet->isEnabled()) {
            $this->getModel()->log('info', 'callback: orderid='.$orderId);
            if ($this->getModel()->checkSign($data, $wallet->getPassword())) {
                $this->load->model('checkout/order');
                $orderInfo = $this->model_checkout_order->getOrder($orderId);
                $orderAmount = sprintf('%.2f', $this->currency->format($orderInfo['total'], 'RUB', '', false));
                $this->getModel()->log('info', 'Total order = ' . $orderAmount . ',  Total paid = ' . $data['amount'] . ',  Total withdraw = ' . $data['withdraw_amount'] . ';');
                if ($data['withdraw_amount'] == $orderAmount) {
                    $this->model_checkout_order->addOrderHistory(
                        $orderId,
                        $wallet->getSuccessOrderStatusId(),
                        'Платёж номер "' . $data['operation_id'] . '" подтверждён'
                    );
                    $this->getModel()->log('info', 'callback: Payment amount is valid.');
                } else {
                    $message = 'Получен платёж номер "' . $data['operation_id'] . '" на сумму ' . $data['amount'] . ' RUB';
                    $this->getModel()->addOrderHistory(
                        $orderId,
                        $orderInfo['order_status_id'] ?: 1,
                        $message
                    );
                    $this->getModel()->log('error', 'callback: Payment amount is not valid.');
                }
            } else {
                $this->getModel()->log('error', 'callback: Payment is not signed.');
            }
        } else {
            $this->getModel()->log('info', 'callback: You aren\'t YooMoney.');
            exit('You aren\'t YooMoney.');
        }
    }

    public function repay()
    {
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link(
                'extension/payment/yoomoney/repay', 'order_id='.$this->request->get['order_id'], true
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

    /**
     * @param $route
     * @param $args
     */
    public function hookOrderStatusChange(&$route, &$args)
    {
        $orderId  = (int)$args[0];
        $statusId = (int)$args[1];
        $this->getModel()->hookOrderStatusChange($orderId, $statusId);
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
     * @return ModelExtensionPaymentYoomoney
     */
    public function getModel()
    {
        if ($this->_model === null) {
            $this->load->model('extension/payment/yoomoney');
            $this->_model = $this->model_extension_payment_yoomoney;
        }

        return $this->_model;
    }

}
