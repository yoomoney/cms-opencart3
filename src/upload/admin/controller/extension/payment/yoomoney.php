<?php

use YooKassa\Model\CurrencyCode;
use YooKassa\Model\PaymentData\B2b\Sberbank\VatDataType;
use YooKassa\Model\PaymentStatus;

/**
 * Class ControllerExtensionPaymentYoomoney
 *
 * @property ModelSettingSetting $model_setting_setting
 */
class ControllerExtensionPaymentYoomoney extends Controller
{
    const MODULE_NAME = 'yoomoney';
    const MODULE_VERSION = '2.1.3';

    const WIDGET_INSTALL_STATUS_SUCCESS = true;
    const WIDGET_INSTALL_STATUS_FAIL    = false;

    /**
     * @var integer
     */
    private $npsRetryAfterDays = 90;

    private $error = array();

    /**
     * @var ModelExtensionPaymentYoomoney
     */
    private $_model;

    public function index()
    {
        $this->load->language('extension/payment/'.self::MODULE_NAME);
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');
        $this->load->model('catalog/option');
        $this->load->model('localisation/currency');

        if ($this->getModel()->getKassaModel()->isEnabled()) {
            $tab = 'tab-kassa';
        } elseif ($this->getModel()->getWalletModel()->isEnabled()) {
            $tab = 'tab-wallet';
        } else {
            $tab = 'tab-kassa';
        }

        if (!empty($this->request->post['last_active_tab'])) {
            $this->session->data['last-active-tab'] = $this->request->post['last_active_tab'];
        } elseif (!isset($this->session->data['last-active-tab'])) {
            $this->session->data['last-active-tab'] = $tab;
        }

        $data = array(
            'lastActiveTab' => $this->session->data['last-active-tab'],
        );

        if ($this->request->server['REQUEST_METHOD'] === 'POST') {

            if ($this->validate($this->request)) {
                $this->enableB2bSberbank();
                $this->getModel()->installEventForSecondReceipt();
            } else {
                $this->saveValidationErrors();
            }

            $this->model_setting_setting->editSetting(self::MODULE_NAME, $this->request->post);
            $this->model_setting_setting->editSetting('payment_'.self::MODULE_NAME, $this->request->post);

            $settings = $this->model_setting_setting->getSetting(self::MODULE_NAME);

            $this->session->data['success']         = $this->language->get('kassa_text_success');
            $this->session->data['last-active-tab'] = $data['lastActiveTab'];

            if (isset($this->request->post['language_reload'])) {
                $this->session->data['success-message'] = 'Настройки были сохранены';
                $this->response->redirect(
                    $this->url->link('extension/payment/'.self::MODULE_NAME,
                        'user_token='.$this->session->data['user_token'], true)
                );
            } else {
                $this->response->redirect(
                    $this->url->link(
                        'extension/extension', 'user_token='.$this->session->data['user_token'].'&type=payment',
                        true
                    )
                );
            }
        } else {
            $this->session->data['last-active-tab'] = $tab;
            $this->applyValidationErrors($data);
        }

        $data['module_version']      = self::MODULE_VERSION;
        $data['breadcrumbs']         = $this->getBreadCrumbs();
        $data['kassaTaxRates']       = $this->getKassaTaxRates();
        $data['kassaTaxSystemCodes'] = $this->getKassaTaxSystemCodes();
        $data['shopTaxRates']        = $this->getShopTaxRates();
        $data['orderStatuses']       = $this->getAvailableOrderStatuses();
        $data['geoZones']            = $this->getAvailableGeoZones();

        if (isset($this->session->data['success-message'])) {
            $data['successMessage'] = $this->session->data['success-message'];
            unset($this->session->data['success-message']);
        }

        $data['action']              = $this->url->link('extension/payment/'.self::MODULE_NAME,
            'user_token='.$this->session->data['user_token'], true);
        $data['cancel']              = $this->url->link('marketplace/extension',
            'user_token='.$this->session->data['user_token'].'&type=payment', true);
        $data['kassa_logs_link']     = $this->url->link(
            'extension/payment/'.self::MODULE_NAME.'/logs',
            'user_token='.$this->session->data['user_token'],
            true
        );
        $data['install_widget'] = htmlspecialchars_decode($this->url->link(
            'extension/payment/'.self::MODULE_NAME.'/installWidget',
            'user_token='.$this->session->data['user_token'],
            true
        ));
        $data['kassa_payments_link'] = $this->url->link(
            'extension/payment/'.self::MODULE_NAME.'/payments',
            'user_token='.$this->session->data['user_token'],
            true
        );

        $data['language'] = $this->language;

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $data['kassa']            = $this->getModel()->getKassaModel();
        $data['kassa_currencies'] = $this->createKassaCurrencyList();

        $name = $data['kassa']->getDisplayName();
        if (empty($name)) {
            $data['kassa']->setDisplayName($this->language->get('kassa_default_display_name'));
        }
        $data['wallet'] = $this->getModel()->getWalletModel();
        $name           = $data['wallet']->getDisplayName();
        if (empty($name)) {
            $data['wallet']->setDisplayName($this->language->get('wallet_default_display_name'));
        }

        $url                     = new Url(HTTP_CATALOG);
        $data['notificationUrl'] = str_replace(
            'http://',
            'https://',
            $url->link('extension/payment/'.self::MODULE_NAME.'/capture', '', true)
        );
        $data['callbackUrl']     = $url->link('extension/payment/'.self::MODULE_NAME.'/callback', '', true);

        if (isset($this->request->post['yoomoney_kassa_sort_order'])) {
            $data['yoomoney_kassa_sort_order'] = $this->request->post['yoomoney_kassa_sort_order'];
        } elseif ($this->config->get('yoomoney_kassa_sort_order')) {
            $data['yoomoney_kassa_sort_order'] = $this->config->get('yoomoney_kassa_sort_order');
        } else {
            $data['yoomoney_kassa_sort_order'] = '0';
        }

        if (isset($this->request->post['yoomoney_wallet_sort_order'])) {
            $data['yoomoney_wallet_sort_order'] = $this->request->post['yoomoney_wallet_sort_order'];
        } elseif ($this->config->get('yoomoney_wallet_sort_order')) {
            $data['yoomoney_wallet_sort_order'] = $this->config->get('yoomoney_wallet_sort_order');
        } else {
            $data['yoomoney_wallet_sort_order'] = '0';
        }

        $array_init = array();

        if (isset($this->request->get['err'])) {
            $data['err_token'] = $this->request->get['err'];
        } else {
            $data['err_token'] = '';
        }

        /**
         * Sbbol section
         */
        $data['b2bTaxRates'] = $this->getB2bTaxRates();

        /**
         * Updater section
         */
        $data = $this->setUpdaterData($data);

        // kassa
        $arLang = array(
            'p2p_os',
            'tab_row_sign',
            'tab_row_cause',
            'tab_row_primary',
            'yoomoney_version',
            'text_license',
            'active',
            'active_on',
            'active_off',
            'log',
            'button_cancel',
            'text_installed',
            'button_save',
            'button_cancel',
        );
        foreach ($arLang as $lang_name) {
            $data[$lang_name] = $this->language->get($lang_name);
        }

        $data['user_token'] = $this->session->data['user_token'];

        $results             = $this->model_catalog_option->getOptions(array('sort' => 'name'));
        $data['options']     = $results;
        $data['tab_general'] = $this->language->get('tab_general');

        $this->load->model('localisation/stock_status');
        $data['stock_statuses'] = $this->model_localisation_stock_status->getStockStatuses();
        $this->load->model('catalog/category');
        $data['categories'] = $this->model_catalog_category->getCategories(0);
        $this->document->setTitle($this->language->get('heading_title_yoomoney'));


        $data = array_merge($data, $this->initForm($array_init));
        $data = array_merge($data, $this->initErrors());

        $data['yoomoney_nps_prev_vote_time']    = $this->config->get('yoomoney_nps_prev_vote_time');
        $data['yoomoney_nps_current_vote_time'] = time();
        $data['callback_off_nps']                   = $this->url->link('extension/payment/'.self::MODULE_NAME.'/vote_nps',
            'user_token='.$this->session->data['user_token'], true);
        $data['nps_block_text']                     = sprintf($this->language->get('nps_text'),
            '<a href="#" onclick="return false;" class="yoomoney_nps_link">', '</a>');
        $isTimeForVote                              = $data['yoomoney_nps_current_vote_time'] > (int)$data['yoomoney_nps_prev_vote_time']
            + $this->npsRetryAfterDays * 86400;
        $data['is_needed_show_nps']                 = $isTimeForVote
            && substr($this->getModel()->getKassaModel()->getPassword(), 0,
                5) === 'live_'
            && $data['nps_block_text'];

        $data['load'] = $this->load;
        $data['data'] = $data;
        $this->response->setOutput($this->load->view('extension/payment/yoomoney', $data));
    }

    /**
     * Экшен для сохранения времени голосования в NPS
     */
    public function vote_nps()
    {
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSettingValue('yoomoney', 'yoomoney_nps_prev_vote_time', time());
    }

    public function logs()
    {
        $this->load->language('extension/payment/'.self::MODULE_NAME);
        $this->document->setTitle($this->language->get('kassa_breadcrumbs_heading_title'));

        $fileName = DIR_LOGS.'yoomoney.log';

        if (isset($_POST['clear-logs']) && $_POST['clear-logs'] === '1') {
            if (file_exists($fileName)) {
                unlink($fileName);
            }
        }
        if (isset($_POST['download']) && $_POST['download'] === '1') {
            if (file_exists($fileName) && filesize($fileName) > 0) {
                $this->response->addheader('Pragma: public');
                $this->response->addheader('Expires: 0');
                $this->response->addheader('Content-Description: File Transfer');
                $this->response->addheader('Content-Type: application/octet-stream');
                $this->response->addheader('Content-Disposition: attachment; filename="yoomoney_'.date('Y-m-d_H-i-s').'.log"');
                $this->response->addheader('Content-Transfer-Encoding: binary');

                $this->response->setOutput(file_get_contents($fileName));

                return;
            }
        }

        $content = '';
        if (file_exists($fileName)) {
            $content = file_get_contents($fileName);
        }
        $data['logs']        = $content;
        $data['breadcrumbs'] = $this->getBreadCrumbs(array(
            'text' => 'kassa_breadcrumbs_logs',
            'href' => 'logs',
        ));

        $data['language'] = $this->language;

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/yoomoney/logs', $data));
    }

    public function payments()
    {
        $this->load->language('extension/payment/'.self::MODULE_NAME);
        $this->load->model('setting/setting');

        if (!$this->getModel()->getKassaModel()->isEnabled()) {
            $url = $this->url->link('extension/payment/yoomoney', 'user_token='.$this->session->data['user_token'],
                true);
            $this->response->redirect($url);
        }

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }
        $limit    = $this->config->get('config_limit_admin');
        $payments = $this->getModel()->findPayments(($page - 1) * $limit, $limit);

        if (isset($this->request->get['update_statuses'])) {

            $orderIds = array();
            foreach ($payments as $row) {
                $orderIds[$row['payment_id']] = $row['order_id'];
            }

            /** @var ModelSaleOrder $orderModel */
            $this->load->model('sale/order');
            $orderModel = $this->model_sale_order;

            $paymentObjects = $this->getModel()->updatePaymentsStatuses($payments);
            if ($this->request->get['update_statuses'] == 2) {
                foreach ($paymentObjects as $payment) {
                    $this->getModel()->log('info', 'Check payment#'.$payment->getId());
                    if ($payment['status'] === \YooKassa\Model\PaymentStatus::WAITING_FOR_CAPTURE) {
                        $this->getModel()->log('info', 'Capture payment#'.$payment->getId());
                        if ($this->getModel()->capturePayment($payment, false)) {
                            $orderId   = $orderIds[$payment->getId()];
                            $orderInfo = $orderModel->getOrder($orderId);
                            if (empty($orderInfo)) {
                                $this->getModel()->log('warning', 'Empty order#'.$orderId.' in notification');
                                continue;
                            } elseif ($orderInfo['order_status_id'] <= 0) {
                                $link                         = $this->url->link('extension/payment/yoomoney/repay',
                                    'order_id='.$orderId, true);
                                $anchor                       = '<a href="'.$link.'" class="button">Оплатить</a>';
                                $orderInfo['order_status_id'] = 1;
                                $this->getModel()->updateOrderStatus($orderId, $orderInfo, $anchor);
                            }
                            $this->getModel()->confirmOrderPayment(
                                $orderId,
                                $orderInfo,
                                $payment,
                                $this->getModel()->getKassaModel()->getSuccessOrderStatusId()
                            );
                            $this->getModel()->log('info', 'Платёж для заказа №'.$orderId.' подтверждён');
                        }
                    }
                }
            }
            $link = $this->url->link('extension/payment/yoomoney/payments',
                'user_token='.$this->session->data['user_token'], true);
            $this->response->redirect($link);
        }

        $this->document->setTitle($this->language->get('kassa_payments_page_title'));

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $pagination        = new Pagination();
        $pagination->total = $this->getModel()->countPayments();
        $pagination->page  = $page;
        $pagination->limit = $limit;
        $pagination->url   = $this->url->link(
            'extension/payment/yoomoney/payments',
            'user_token='.$this->session->data['user_token'].'&page={page}',
            true
        );

        $data['language']     = $this->language;
        $data['payments']     = $payments;
        $data['breadcrumbs']  = $this->getBreadCrumbs(array(
            'text' => 'kassa_breadcrumbs_payments',
            'href' => 'payments',
        ));
        $data['update_link']  = $this->url->link(
            'extension/payment/yoomoney/payments',
            'user_token='.$this->session->data['user_token'].'&update_statuses=1',
            true
        );
        $data['capture_link'] = $this->url->link(
            'extension/payment/yoomoney/payments',
            'user_token='.$this->session->data['user_token'].'&update_statuses=2',
            true
        );
        $this->response->setOutput($this->load->view('extension/payment/yoomoney/kassa_payments_list', $data));
    }

    public function install()
    {
        $this->getModel()->install();
    }

    public function uninstall()
    {
        $this->getModel()->uninstall();
    }

    public function getModel()
    {
        if ($this->_model === null) {
            $this->load->model('extension/payment/'.self::MODULE_NAME);
            $property     = 'model_extension_payment_'.self::MODULE_NAME;
            $this->_model = $this->__get($property);
        }

        return $this->_model;
    }

    private function getBreadCrumbs($add = null)
    {
        $params = 'user_token='.$this->session->data['user_token'];
        $result = array(
            array(
                'text' => $this->language->get('kassa_breadcrumbs_home'),
                'href' => $this->url->link('common/dashboard', $params, true),
            ),
            array(
                'text' => $this->language->get('kassa_breadcrumbs_extension'),
                'href' => $this->url->link('marketplace/extension',
                    'user_token='.$this->session->data['user_token'].'&type=payment', true),
            ),
            array(
                'text' => $this->language->get('module_title'),
                'href' => $this->url->link('extension/payment/'.self::MODULE_NAME, $params, true),
            ),
        );
        if (!empty($add)) {
            $result[] = array(
                'text' => $this->language->get($add['text']),
                'href' => $this->url->link('extension/payment/'.self::MODULE_NAME.'/'.$add['href'], $params, true),
            );
        }

        return $result;
    }

    private function validate(Request $request)
    {
        $this->load->model('localisation/currency');
        if (!$this->user->hasPermission('modify', 'extension/payment/yoomoney')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        $this->validateKassa($request);
        $this->validateWallet($request);

        $enabled = false;
        if ($this->getModel()->getKassaModel()->isEnabled()) {
            $enabled = true;
            $request->post['payment_yoomoney_sort_order'] = $request->post['yoomoney_kassa_sort_order'];
        } elseif ($this->getModel()->getWalletModel()->isEnabled()) {
            $enabled = true;
            $request->post['payment_yoomoney_sort_order'] = $request->post['yoomoney_wallet_sort_order'];
        }

        $request->post['payment_yoomoney_status'] = $enabled;

        return empty($this->error);
    }

    private function validateKassa(Request $request)
    {
        $kassa   = $this->getModel()->getKassaModel();
        $enabled = false;
        if (isset($request->post['yoomoney_kassa_enabled']) && $this->isTrue($request->post['yoomoney_kassa_enabled'])) {
            $enabled = true;
        }
        $request->post['kassa_enabled'] = $enabled;
        $kassa->setIsEnabled($enabled);

        $value = isset($request->post['yoomoney_kassa_shop_id']) ? trim($request->post['yoomoney_kassa_shop_id']) : '';
        $kassa->setShopId($value);
        $request->post['yoomoney_kassa_shop_id'] = $value;
        if ($enabled && empty($value)) {
            $this->error['kassa_shop_id'] = $this->language->get('kassa_shop_id_error_required');
        }

        $value = isset($request->post['yoomoney_kassa_password']) ? trim($request->post['yoomoney_kassa_password']) : '';
        $kassa->setPassword($value);
        $request->post['yoomoney_kassa_password'] = $value;
        if ($enabled && empty($value)) {
            $this->error['kassa_password'] = $this->language->get('kassa_password_error_required');
        }

        if (empty($this->error)) {
            if (!$kassa->checkConnection()) {
                $this->error['kassa_invalid_credentials'] = $this->language->get('kassa_error_invalid_credentials');
            }
        }

        $value = isset($request->post['yoomoney_kassa_payment_mode']) ? $request->post['yoomoney_kassa_payment_mode'] : '';
        $epl   = true;
        if ($value === 'shop') {
            $epl = false;
        }
        $kassa->setEPL($epl);

        $value = isset($request->post['yoomoney_kassa_use_installments_button']) ? $request->post['yoomoney_kassa_use_installments_button'] : 'off';
        $kassa->setUseInstallmentsButton($this->isTrue($value));
        $request->post['yoomoney_kassa_use_installments_button'] = $kassa->useInstallmentsButton();

        $selected = false;
        foreach ($kassa->getPaymentMethods() as $id => $value) {
            $property = 'yoomoney_kassa_payment_method_'.$id;
            $value    = isset($request->post[$property]) ? $this->isTrue($request->post[$property]) : false;
            $kassa->setPaymentMethodFlag($id, $value);
            $request->post[$property] = $value;
            if ($value) {
                $selected = true;
            }
        }
        if (!$selected && !$epl) {
            $this->error['kassa_payment_method'] = $this->language->get('kassa_payment_method_error_required');
        }

        $value = isset($request->post['yoomoney_kassa_display_name']) ? trim($request->post['yoomoney_kassa_display_name']) : '';
        if (empty($value)) {
            $value = $this->language->get('kassa_default_display_name');
        }
        $kassa->setDisplayName($value);
        $request->post['yoomoney_kassa_display_name'] = $kassa->getDisplayName();

        $value = isset($request->post['yoomoney_kassa_tax_rate_default']) ? $request->post['yoomoney_kassa_tax_rate_default'] : 1;
        $kassa->setDefaultTaxRate($value);
        $request->post['yoomoney_kassa_tax_rate_default'] = $kassa->getDefaultTaxRate();

        $value = isset($request->post['yoomoney_kassa_tax_system_default']) ? $request->post['yoomoney_kassa_tax_system_default'] : 0;
        $kassa->setDefaultTaxSystemCode($value);
        $request->post['yoomoney_kassa_tax_system_default'] = $kassa->getDefaultTaxSystemCode();

        $value = isset($request->post['yoomoney_kassa_tax_rates']) ? $request->post['yoomoney_kassa_tax_rates'] : array();
        if (is_array($value)) {
            $kassa->setTaxRates($value);
            $request->post['yoomoney_kassa_tax_rates'] = $kassa->getTaxRates();
        }

        $value = isset($request->post['yoomoney_kassa_success_order_status']) ? $request->post['yoomoney_kassa_success_order_status'] : array();
        $kassa->setSuccessOrderStatusId($value);
        $request->post['yoomoney_kassa_success_order_status'] = $kassa->getSuccessOrderStatusId();

        $value = isset($request->post['yoomoney_kassa_minimum_payment_amount']) ? $request->post['yoomoney_kassa_minimum_payment_amount'] : array();
        $kassa->setMinPaymentAmount($value);
        $request->post['yoomoney_kassa_minimum_payment_amount'] = $kassa->getMinPaymentAmount();

        $value = isset($request->post['yoomoney_kassa_geo_zone']) ? $request->post['yoomoney_kassa_geo_zone'] : array();
        $kassa->setGeoZoneId($value);
        $request->post['yoomoney_kassa_geo_zone'] = $kassa->getGeoZoneId();

        $value = isset($request->post['yoomoney_kassa_debug_log']) ? $this->isTrue($request->post['yoomoney_kassa_debug_log']) : false;
        $kassa->setDebugLog($value);
        $request->post['yoomoney_kassa_debug_log'] = $kassa->getDebugLog();

        $value = isset($request->post['yoomoney_kassa_invoice']) ? $this->isTrue($request->post['yoomoney_kassa_invoice']) : false;
        $kassa->setInvoicesEnabled($value);
        $request->post['yoomoney_kassa_invoice'] = $kassa->isInvoicesEnabled();

        $value = isset($request->post['yoomoney_kassa_invoice_subject']) ? trim($request->post['yoomoney_kassa_invoice_subject']) : '';
        if (empty($value)) {
            $value = $this->language->get('kassa_invoice_subject_default');
        }
        $kassa->setInvoiceSubject($value);
        $request->post['yoomoney_kassa_invoice_subject'] = $kassa->getInvoiceSubject();

        $value = isset($request->post['yoomoney_kassa_invoice_message']) ? trim($request->post['yoomoney_kassa_invoice_message']) : '';
        $kassa->setInvoiceMessage($value);
        $request->post['yoomoney_kassa_invoice_message'] = $kassa->getInvoiceMessage();

        $value = isset($request->post['yoomoney_kassa_invoice_logo']) ? $this->isTrue($request->post['yoomoney_kassa_invoice_logo']) : false;
        $kassa->setSendInvoiceLogo($value);
        $request->post['yoomoney_kassa_invoice_logo'] = $kassa->getSendInvoiceLogo();

        $value = false;
        if (isset($request->post['yoomoney_kassa_create_order_before_redirect']) && $this->isTrue($request->post['yoomoney_kassa_create_order_before_redirect'])) {
            $value = true;
        }
        $request->post['yoomoney_kassa_create_order_before_redirect'] = $value;
        $kassa->setCreateOrderBeforeRedirect($value);

        $value = false;
        if (isset($request->post['yoomoney_kassa_clear_cart_before_redirect']) && $this->isTrue($request->post['yoomoney_kassa_clear_cart_before_redirect'])) {
            $value = true;
        }
        $request->post['yoomoney_kassa_clear_cart_before_redirect'] = $value;
        $kassa->setClearCartBeforeRedirect($value);

        $value = isset($request->post['yoomoney_kassa_show_in_footer']) ? $request->post['yoomoney_kassa_show_in_footer'] : 'off';
        $kassa->setShowLinkInFooter($this->isTrue($value));
        $request->post['yoomoney_kassa_show_in_footer'] = $kassa->getShowLinkInFooter();

        $value = isset($request->post['yoomoney_kassa_b2b_sberbank_enabled']) ? $request->post['yoomoney_kassa_b2b_sberbank_enabled'] : 'off';
        $kassa->setB2bSberbankEnabled($this->isTrue($value));

        $value = isset($request->post['yoomoney_kassa_b2b_tax_rate_default']) ? $request->post['yoomoney_kassa_b2b_tax_rate_default'] : VatDataType::UNTAXED;
        $kassa->setB2bSberbankDefaultTaxRate($value);
        $request->post['yoomoney_kassa_tax_rate_default'] = $kassa->getDefaultTaxRate();

        $value = isset($request->post['yoomoney_kassa_b2b_tax_rates']) ? $request->post['yoomoney_kassa_b2b_tax_rates'] : array();
        if (is_array($value)) {
            $kassa->setB2bTaxRates($value);
            $request->post['yoomoney_kassa_b2b_tax_rates'] = $kassa->getB2bTaxRates();
        }
        $this->getModel()->log('debug', print_r($request->post, true));
    }

    private function validateWallet(Request $request)
    {
        $wallet  = $this->getModel()->getWalletModel();
        $enabled = false;
        if (isset($request->post['yoomoney_wallet_enabled']) && $this->isTrue($request->post['yoomoney_wallet_enabled'])) {
            $enabled = true;
        }
        $request->post['wallet_enabled'] = $enabled;
        $wallet->setIsEnabled($enabled);

        $value = isset($request->post['yoomoney_wallet_account_id']) ? trim($request->post['yoomoney_wallet_account_id']) : '';
        $wallet->setAccountId($value);
        $request->post['yoomoney_wallet_account_id'] = $value;
        if ($enabled && empty($value)) {
            $this->error['wallet_account_id'] = $this->language->get('wallet_account_id_error_required');
        }

        $value = isset($request->post['yoomoney_wallet_password']) ? trim($request->post['yoomoney_wallet_password']) : '';
        $wallet->setPassword($value);
        $request->post['yoomoney_wallet_password'] = $value;
        if ($enabled && empty($value)) {
            $this->error['wallet_password'] = $this->language->get('wallet_password_error_required');
        }

        $value = isset($request->post['yoomoney_wallet_display_name']) ? trim($request->post['yoomoney_wallet_display_name']) : '';
        if (empty($value)) {
            $value = $this->language->get('wallet_default_display_name');
        }
        $wallet->setDisplayName($value);
        $request->post['yoomoney_wallet_display_name'] = $wallet->getDisplayName();

        $value = isset($request->post['yoomoney_wallet_success_order_status']) ? $request->post['yoomoney_wallet_success_order_status'] : array();
        $wallet->setSuccessOrderStatusId($value);
        $request->post['yoomoney_wallet_success_order_status'] = $wallet->getSuccessOrderStatusId();

        $value = isset($request->post['yoomoney_wallet_minimum_payment_amount']) ? $request->post['yoomoney_wallet_minimum_payment_amount'] : array();
        $wallet->setMinPaymentAmount($value);
        $request->post['yoomoney_wallet_minimum_payment_amount'] = $wallet->getMinPaymentAmount();

        $value = isset($request->post['yoomoney_wallet_geo_zone']) ? $request->post['yoomoney_wallet_geo_zone'] : array();
        $wallet->setGeoZoneId($value);
        $request->post['yoomoney_wallet_geo_zone'] = $wallet->getGeoZoneId();

        $value = false;
        if (isset($request->post['yoomoney_wallet_create_order_before_redirect']) && $this->isTrue($request->post['yoomoney_wallet_create_order_before_redirect'])) {
            $value = true;
        }
        $request->post['yoomoney_wallet_create_order_before_redirect'] = $value;
        $wallet->setCreateOrderBeforeRedirect($value);

        $value = false;
        if (isset($request->post['yoomoney_wallet_clear_cart_before_redirect']) && $this->isTrue($request->post['yoomoney_wallet_clear_cart_before_redirect'])) {
            $value = true;
        }
        $request->post['yoomoney_wallet_clear_cart_before_redirect'] = $value;
        $wallet->setClearCartBeforeRedirect($value);
    }

    private function saveValidationErrors()
    {
        $this->session->data['errors_settings'] = array();
        if (!empty($this->error)) {
            foreach ($this->error as $key => $error) {
                $this->session->data['errors_settings'][$key] = $error;
            }
        }
    }

    private function applyValidationErrors(&$data)
    {
        if (!empty($this->session->data['errors_settings'])) {
            foreach ($this->session->data['errors_settings'] as $key => $error) {
                $data['error_'.$key] = $error;
            }
            unset($this->session->data['errors_settings']);
        }
    }

    private function getShopTaxRates()
    {
        $this->load->model('localisation/tax_class');
        $model = $this->model_localisation_tax_class;

        $result = array();
        foreach ($model->getTaxClasses() as $taxRate) {
            $result[$taxRate['tax_class_id']] = $taxRate['title'];
        }

        return $result;
    }

    private function getKassaTaxRates()
    {
        $result = array();
        foreach ($this->getModel()->getKassaModel()->getTaxRateList() as $taxRateId) {
            $key                = 'kassa_tax_rate_'.$taxRateId.'_label';
            $result[$taxRateId] = $this->language->get($key);
        }

        return $result;
    }

    private function getKassaTaxSystemCodes()
    {
        $result = array();
        foreach ($this->getModel()->getKassaModel()->getTaxSystemCodeList() as $taxRateId) {
            $key                = 'kassa_tax_system_'.$taxRateId.'_label';
            $result[$taxRateId] = $this->language->get($key);
        }

        return $result;
    }

    private function getB2bTaxRates()
    {
        $result = array();
        foreach ($this->getModel()->getKassaModel()->getB2bTaxRateList() as $taxRateId) {
            $key                = 'b2b_tax_rate_'.$taxRateId.'_label';
            $result[$taxRateId] = $this->language->get($key);
        }

        return $result;
    }

    private function getAvailableGeoZones()
    {
        $this->load->model('localisation/geo_zone');
        $result = array();
        foreach ($this->model_localisation_geo_zone->getGeoZones() as $row) {
            $result[$row['geo_zone_id']] = $row['name'];
        }

        return $result;
    }

    private function getAvailableOrderStatuses()
    {
        $this->load->model('localisation/order_status');
        $result = array();
        foreach ($this->model_localisation_order_status->getOrderStatuses() as $row) {
            $result[$row['order_status_id']] = $row['name'];
        }

        return $result;
    }

    private function initForm($array)
    {
        foreach ($array as $a) {
            $data[$a] = $this->config->get($a);
        }

        if ($this->config->get('config_secure')) {
            $data['yoomoney_kassa_fail']               = HTTPS_CATALOG.'index.php?route=checkout/failure';
            $data['yoomoney_kassa_success']            = HTTPS_CATALOG.'index.php?route=checkout/success';
            $data['yoomoney_p2p_linkapp']              = HTTPS_CATALOG.'index.php?route=extension/payment/yoomoney/inside';
        } else {
            $data['yoomoney_kassa_fail']               = HTTP_CATALOG.'index.php?route=checkout/failure';
            $data['yoomoney_kassa_success']            = HTTP_CATALOG.'index.php?route=checkout/success';
            $data['yoomoney_p2p_linkapp']              = HTTP_CATALOG.'index.php?route=extension/payment/yoomoney/inside';
        }

        return $data;
    }

    /**
     * @param string $post
     *
     * @return string
     */
    public function goCurl($post)
    {
        $url = 'https://oauth.yandex.ru/token';
        $ch  = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 9);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode($result);
        if ($status !== 200 && empty($data->access_token)) {
            $this->getModel()->log('error', 'Failed to get OAuth token:'.$data->error_description);
            $this->response->redirect($this->url->link('extension/payment/yoomoney',
                'err='.$data->error_description.'&user_token='.$this->session->data['user_token'], true));
        }

        return $data->access_token;
    }

    private function initErrors()
    {
        $data = array();

        if (empty($data['kassa_status'])) {
            $data['kassa_status'][] = '';
        }

        return $data;
    }

    public function sendmail()
    {
        $this->language->load('extension/payment/yoomoney');

        $json     = array();
        $order_id = (isset($this->request->get['order_id'])) ? $this->request->get['order_id'] : 0;
        if ($order_id <= 0) {
            $json['error'] = $this->language->get('kassa_invoices_invalid_order_id');
            $this->sendResponseJson($json);

            return true;
        }
        $kassa = $this->getModel()->getKassaModel();
        if (!$kassa->isEnabled()) {
            $json['error'] = $this->language->get('kassa_invoices_kassa_disabled');
            $this->sendResponseJson($json);

            return true;
        }
        if (!$kassa->isInvoicesEnabled()) {
            $json['error'] = $this->language->get('kassa_invoices_disabled');
            $this->sendResponseJson($json);

            return true;
        }
        $this->load->model('sale/order');
        $order_info = $this->model_sale_order->getOrder($order_id);
        if (empty($order_info)) {
            $json['error'] = $this->language->get('kassa_invoices_order_not_exists');
            $this->sendResponseJson($json);

            return true;
        }
        $email     = $order_info['email'];
        $products  = $this->model_sale_order->getOrderProducts($order_id);
        $amount    = number_format(
            $this->currency->convert(
                $this->currency->format(
                    $order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false
                ),
                $order_info['currency_code'],
                'RUB'
            ),
            2, '.', ''
        );
        $urlHelper = new Url(HTTPS_CATALOG);
        $url       = $urlHelper->link('extension/payment/yoomoney/simplepayment', 'order_id='.$order_id, true);
        $logo      = (is_file(DIR_IMAGE.$this->config->get('config_logo'))) ? DIR_IMAGE.$this->config->get('config_logo') : '';

        $replaceMap = array(
            '%order_id%'  => $order_id,
            '%shop_name%' => $order_info['store_name'],
        );
        foreach ($order_info as $key => $value) {
            if (is_scalar($value)) {
                $replaceMap['%'.$key.'%'] = $value;
            } else {
                $replaceMap['%'.$key.'%'] = json_encode($value);
            }
        }
        $text_instruction = strtr($kassa->getInvoiceMessage(), $replaceMap);
        $subject          = strtr($kassa->getInvoiceSubject(), $replaceMap);

        $link_img = ($this->request->server['HTTPS']) ? HTTPS_CATALOG : HTTP_CATALOG;
        $data     = array(
            'shop_name'     => $order_info['store_name'],
            'shop_url'      => $order_info['store_url'],
            'shop_logo'     => 'cid:'.basename($logo),
            'b_logo'        => $kassa->getSendInvoiceLogo(),
            'customer_name' => $order_info['customer'],
            'order_id'      => $order_id,
            'sum'           => $amount,
            'link'          => $url,
            'yoomoney_button' => $link_img . 'image/catalog/payment/yoomoney/yoomoney_buttons.png',
            'total'         => $order_info['total'],
            'shipping'      => $order_info['shipping_method'],
            'products'      => $products,
            'instruction'   => $text_instruction,
        );
        $message  = $this->load->view('extension/payment/yoomoney/invoice_message', $data);

        try {
            $mail = new Mail();

            $mail->protocol      = $this->config->get('config_mail_protocol');
            $mail->parameter     = $this->config->get('config_mail_parameter');
            $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
            $mail->smtp_username = $this->config->get('config_mail_smtp_username');
            $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES,
                'UTF-8');
            $mail->smtp_port     = $this->config->get('config_mail_smtp_port');
            $mail->smtp_timeout  = $this->config->get('config_mail_smtp_timeout');

            $mail->setTo($email);
            $mail->setFrom($this->config->get('config_email'));
            $mail->setSender($this->config->get('config_email'));
            $mail->setSubject($subject);
            if ($kassa->getSendInvoiceLogo() && $logo != '') {
                $mail->addAttachment($logo);
            }
            $mail->setHtml($message);
            $mail->send();
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
            $this->sendResponseJson($json);
        }
        $json['success'] = sprintf("Счет на оплату заказа %s выставлен", $order_id);
        $this->sendResponseJson($json);
    }

    /**
     * Подтверждение холдового платежа
     */
    public function capture()
    {
        $this->load->language('extension/payment/'.self::MODULE_NAME);
        $this->load->model('setting/setting');

        $orderId = isset($this->request->get['order_id']) ? (int)$this->request->get['order_id'] : 0;

        if (empty($orderId)) {
            $this->response->redirect($this->url->link('sale/order', 'user_token='.$this->session->data['user_token'],
                true));
        }
        $this->load->model('sale/order');
        $returnUrl  = $this->url->link('sale/order',
            'user_token='.$this->session->data['user_token'].'&order_id='.$orderId, true);
        $orderModel = $this->model_sale_order;
        $orderInfo  = $this->model_sale_order->getOrder($orderId);

        if (empty($orderInfo)) {
            $this->response->redirect($returnUrl);
        }
        $kassaModel = $this->getModel()->getKassaModel();
        $paymentId  = $this->getModel()->findPaymentIdByOrderId($orderId);
        if (empty($paymentId)) {
            $this->response->redirect($returnUrl, 'SSL');
        }
        /** @var \YooKassa\Request\Payments\PaymentResponse $payment */
        $payment = $this->getModel()->fetchPaymentInfo($paymentId);
        if ($payment === null) {
            $this->response->redirect($returnUrl);
        }
        $amount  = $payment->getAmount()->getValue();
        $comment = '';

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && isset($this->request->post['kassa_capture_amount'])) {
            $action = $this->request->post['action'];

            if ($action == 'capture') {
                $orderInfo = $this->updateOrder($orderModel, $orderInfo);
                $amount    = $this->request->post['kassa_capture_amount'];
                $this->getModel()->capturePayment($payment, $amount);
            } else if ($action == 'cancel') {
                $this->getModel()->cancelPayment($payment);
                $orderInfo['order_status_id'] = $kassaModel->getOrderCanceledStatus();
                $this->getModel()->updateOrderStatus($orderId, $orderInfo);
            }

            $this->response->redirect($this->url->link(
                'extension/payment/yoomoney/capture',
                'user_token='.$this->session->data['user_token'].'&order_id='.$orderId,
                true
            ));
        }

        $paymentMethod = '';
        $paymentData   = $payment->getPaymentMethod();
        if ($paymentData !== null) {
            $paymentMethod = $this->language->get('kassa_payment_method_'.$paymentData->getType());
        }
        $paymentCaptured = in_array($payment->getStatus(), array(PaymentStatus::SUCCEEDED, PaymentStatus::CANCELED));
        $products        = $this->model_sale_order->getOrderProducts($orderId);

        $data['products']        = $products;
        $data['kassa']           = $this->getModel()->getKassaModel();
        $data['paymentCaptured'] = $paymentCaptured;
        $data['payment']         = $payment;
        $data['order']           = $orderInfo;
        $data['paymentMethod']   = $paymentMethod;
        $data['amount']          = $amount;
        $data['comment']         = $comment;
        $data['error']           = isset($this->session->data['error']) ? $this->session->data['error'] : '';
        $data['capture_amount']  = $amount;

        unset($this->session->data['error']);

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');
        $data['language']    = $this->language;

        $data['vouchers'] = array();

        $vouchers = $this->model_sale_order->getOrderVouchers($this->request->get['order_id']);

        foreach ($vouchers as $voucher) {
            $data['vouchers'][] = array(
                'description' => $voucher['description'],
                'amount'      => $this->currency->format($voucher['amount'], $orderInfo['currency_code'],
                    $orderInfo['currency_value']),
                'href'        => $this->url->link('sale/voucher/edit',
                    'user_token='.$this->session->data['user_token'].'&voucher_id='.$voucher['voucher_id'], true),
            );
        }

        $data['totals'] = array();

        $totals = $this->model_sale_order->getOrderTotals($this->request->get['order_id']);

        foreach ($totals as $total) {
            $data['totals'][] = array(
                'title' => $total['title'],
                'text'  => $this->currency->format($total['value'], $orderInfo['currency_code'],
                    $orderInfo['currency_value']),
            );
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token='.$this->session->data['user_token'], true),
        );

        $data['breadcrumbs'][] = array(
            'text' => 'Заказы',
            'href' => $this->url->link('sale/order', 'user_token='.$this->session->data['user_token'], true),
        );

        $data['breadcrumbs'][] = array(
            'text' => 'Подтверждение заказа №'.$orderId,
            'href' => $this->url->link('extension/payment/yoomoney/capture',
                'user_token='.$this->session->data['user_token'].'&order_id='.$orderId, true),
        );

        $this->response->setOutput($this->load->view('extension/payment/yoomoney/capture', $data));
    }

    /**
     * @param ModelSaleOrder $orderModel
     * @param array $order
     *
     * @return array
     */
    private function updateOrder($orderModel, $order)
    {
        require_once(DIR_CATALOG.'model/checkout/order.php');
        require_once(DIR_CATALOG.'model/account/customer.php');
        require_once(DIR_CATALOG.'model/account/order.php');
        require_once(DIR_CATALOG.'model/extension/total/voucher.php');
        require_once(DIR_CATALOG.'model/extension/total/sub_total.php');
        require_once(DIR_CATALOG.'model/extension/total/shipping.php');
        require_once(DIR_CATALOG.'model/extension/total/tax.php');
        require_once(DIR_CATALOG.'model/extension/total/total.php');

        $this->registry->set('model_checkout_order', new ModelCheckoutOrder($this->registry));
        $this->registry->set('model_account_customer', new ModelAccountCustomer($this->registry));
        $this->registry->set('model_account_order', new ModelAccountOrder($this->registry));
        $this->registry->set('model_extension_total_voucher', new ModelExtensionTotalVoucher($this->registry));
        $this->registry->set('model_extension_total_sub_total', new ModelExtensionTotalSubTotal($this->registry));
        $this->registry->set('model_extension_total_shipping', new ModelExtensionTotalShipping($this->registry));
        $this->registry->set('model_extension_total_tax', new ModelExtensionTotalTax($this->registry));
        $this->registry->set('model_extension_total_total', new ModelExtensionTotalTotal($this->registry));


        $quantity = $this->request->post['quantity'];

        $products = $orderModel->getOrderProducts($order['order_id']);
        foreach ($products as $index => $product) {
            if ($quantity[$product['product_id']] == "0") {
                unset($products[$index]);
                continue;
            }
            $products[$index]['quantity'] = $quantity[$product['product_id']];
            $products[$index]['total']    = $products[$index]['price'] * $products[$index]['quantity'];
            $products[$index]['option']   = $orderModel->getOrderOptions(
                $order['order_id'],
                $product['order_product_id']
            );
        }
        $order['products'] = array_values($products);
        $order['vouchers'] = $orderModel->getOrderVouchers($order['order_id']);
        $order['totals']   = $orderModel->getOrderTotals($order['order_id']);

        $this->model_checkout_order->editOrder($order['order_id'], $order);

        return $order;
    }

    public function refund()
    {
        $this->load->language('extension/payment/'.self::MODULE_NAME);
        $this->load->model('setting/setting');
        $error = array();

        $orderId = isset($this->request->get['order_id']) ? (int)$this->request->get['order_id'] : 0;
        if (empty($orderId)) {
            $this->response->redirect($this->url->link('sale/order', 'user_token='.$this->session->data['user_token'],
                true));
        }
        $this->load->model('sale/order');
        $returnUrl = $this->url->link('sale/order',
            'user_token='.$this->session->data['user_token'].'&order_id='.$orderId, true);
        $orderInfo = $this->model_sale_order->getOrder($orderId);
        if (empty($orderInfo)) {
            $this->response->redirect($returnUrl);
        }
        $this->getModel()->getKassaModel();
        $paymentId = $this->getModel()->findPaymentIdByOrderId($orderId);
        if (empty($paymentId)) {
            $this->response->redirect($returnUrl, 'SSL');
        }
        $payment = $this->getModel()->fetchPaymentInfo($paymentId);
        if ($payment === null) {
            $this->response->redirect($returnUrl);
        }
        $amount  = $payment->getAmount()->getValue();
        $comment = '';

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && isset($this->request->post['kassa_refund_amount'])) {
            $amount = $this->request->post['kassa_refund_amount'];
            if (!is_numeric($amount)) {
                $error['kassa_refund_amount'] = 'Сумма должна быть числом';
            } elseif ($amount > $payment->getAmount()->getValue()) {
                $error['kassa_refund_amount'] = 'Не верная сумма возврата';
            }
            $comment = trim($this->request->post['kassa_refund_comment']);
            if (empty($comment)) {
                $error['kassa_refund_comment'] = 'Укажите комментарий к возврату';
            }
            if (empty($error)) {
                if (!$this->refundPayment($payment, $orderInfo, $amount, $comment)) {
                    $this->session->data['error'] = 'Не удалось провести возврат';
                } else {
                    $this->response->redirect(
                        $this->url->link('extension/payment/yoomoney/refund',
                            'user_token='.$this->session->data['user_token'].'&order_id='.$orderId, true)
                    );
                }
            }
        }

        $paymentMethod = 'не выбран';
        $paymentData   = $payment->getPaymentMethod();
        if ($paymentData !== null) {
            $paymentMethod = $this->language->get('kassa_payment_method_'.$paymentData->getType());
        }

        $data['kassa']             = $this->getModel()->getKassaModel();
        $data['payment']           = $payment;
        $data['order']             = $orderInfo;
        $data['paymentMethod']     = $paymentMethod;
        $data['errors']            = $error;
        $data['amount']            = $amount;
        $data['comment']           = $comment;
        $data['error']             = isset($this->session->data['error']) ? $this->session->data['error'] : '';
        $data['refunds']           = $this->getModel()->getOrderRefunds($orderInfo['order_id']);
        $data['refundable_amount'] = $amount;
        foreach ($data['refunds'] as $refund) {
            if ($refund['status'] !== \YooKassa\Model\RefundStatus::CANCELED) {
                $data['refundable_amount'] -= $refund['amount'];
                if ($data['refundable_amount'] < 0) {
                    $data['refundable_amount'] = 0;
                }
            }
        }
        $data['refundable_amount'] = round($data['refundable_amount'], 2);
        unset($this->session->data['error']);

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');
        $data['language']    = $this->language;

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token='.$this->session->data['user_token'], true),
        );

        $data['breadcrumbs'][] = array(
            'text' => 'Заказы',
            'href' => $this->url->link('sale/order', 'user_token='.$this->session->data['user_token'], true),
        );

        $data['breadcrumbs'][] = array(
            'text' => 'Возвраты заказа №'.$orderId,
            'href' => $this->url->link('extension/payment/yoomoney/refund',
                'user_token='.$this->session->data['user_token'].'&order_id='.$orderId, true),
        );

        $this->response->setOutput($this->load->view('extension/payment/yoomoney/refund', $data));
    }

    public function installWidget()
    {
        $this->load->language('extension/payment/'.self::MODULE_NAME);

        $answer = array(
            'ok' => self::WIDGET_INSTALL_STATUS_SUCCESS,
            'error' => '',
        );

        if (!$this->enableApplePayForWidget()) {
            $answer = array(
                'ok' => self::WIDGET_INSTALL_STATUS_FAIL,
                'error' => $this->language->get('error_install_widget'),
            );
        }

        $this->getModel()->log('info', 'Install apple-pay for widget result: ' . print_r($answer, true));

        echo json_encode($answer);
    }

    private function enableApplePayForWidget()
    {
        clearstatcache();
        $rootPath = dirname(realpath(DIR_CATALOG));
        $this->getModel()->log('info', 'Root dir: ' . $rootPath);
        if (file_exists($rootPath . '/.well-known/apple-developer-merchantid-domain-association')) {
            $this->getModel()->log('info', 'apple-developer-merchantid-domain-association already exist');
            return true;
        } else if (!file_exists($rootPath . '/.well-known')) {
            if (!@mkdir($rootPath . '/.well-known', 0755)) {
                $this->getModel()->log('error', 'Create .well-known dir fail');
                return false;
            }
        }

        $result = @file_put_contents(
            $rootPath . '/.well-known/apple-developer-merchantid-domain-association',
            '7B227073704964223A2236354545363242363931303142343742414637434132324336344232453843314531353341373238363339453042333731454543434341324237463345354535222C2276657273696F6E223A312C22637265617465644F6E223A313536353731323134383430382C227369676E6174757265223A223330383030363039326138363438383666373064303130373032613038303330383030323031303133313066333030643036303936303836343830313635303330343032303130353030333038303036303932613836343838366637306430313037303130303030613038303330383230336536333038323033386261303033303230313032303230383638363066363939643963636137306633303061303630383261383634386365336430343033303233303761333132653330326330363033353530343033306332353431373037303663363532303431373037303663363936333631373436393666366532303439366537343635363737323631373436393666366532303433343132303264323034373333333132363330323430363033353530343062306331643431373037303663363532303433363537323734363936363639363336313734363936663665323034313735373436383666373236393734373933313133333031313036303335353034306130633061343137303730366336353230343936653633326533313062333030393036303335353034303631333032353535333330316531373064333133363330333633303333333133383331333633343330356131373064333233313330333633303332333133383331333633343330356133303632333132383330323630363033353530343033306331663635363336333264373336643730326436323732366636623635373232643733363936373665356635353433333432643533343134653434343234663538333131343330313230363033353530343062306330623639346635333230353337393733373436353664373333313133333031313036303335353034306130633061343137303730366336353230343936653633326533313062333030393036303335353034303631333032353535333330353933303133303630373261383634386365336430323031303630383261383634386365336430333031303730333432303030343832333066646162633339636637356532303263353064393962343531326536333765326139303164643663623365306231636434623532363739386638636634656264653831613235613863323165346333336464636538653261393663326636616661313933303334356334653837613434323663653935316231323935613338323032313133303832303230643330343530363038326230363031303530353037303130313034333933303337333033353036303832623036303130353035303733303031383632393638373437343730336132663266366636333733373032653631373037303663363532653633366636643266366636333733373033303334326436313730373036633635363136393633363133333330333233303164303630333535316430653034313630343134303232343330306239616565656434363331393761346136356132393965343237313832316334353330306330363033353531643133303130316666303430323330303033303166303630333535316432333034313833303136383031343233663234396334346639336534656632376536633466363238366333666132626266643265346233303832303131643036303335353164323030343832303131343330383230313130333038323031306330363039326138363438383666373633363430353031333038316665333038316333303630383262303630313035303530373032303233303831623630633831623335323635366336393631366536333635323036663665323037343638363937333230363336353732373436393636363936333631373436353230363237393230363136653739323037303631373237343739323036313733373337353664363537333230363136333633363537303734363136653633363532303666363632303734363836353230373436383635366532303631373037303663363936333631363236633635323037333734363136653634363137323634323037343635373236643733323036313665363432303633366636653634363937343639366636653733323036663636323037353733363532633230363336353732373436393636363936333631373436353230373036663663363936333739323036313665363432303633363537323734363936363639363336313734363936663665323037303732363136333734363936333635323037333734363137343635366436353665373437333265333033363036303832623036303130353035303730323031313632613638373437343730336132663266373737373737326536313730373036633635326536333666366432663633363537323734363936363639363336313734363536313735373436383666373236393734373932663330333430363033353531643166303432643330326233303239613032376130323538363233363837343734373033613266326636333732366332653631373037303663363532653633366636643266363137303730366336353631363936333631333332653633373236633330306530363033353531643066303130316666303430343033303230373830333030663036303932613836343838366637363336343036316430343032303530303330306130363038326138363438636533643034303330323033343930303330343630323231303064613163363361653862653566363466386531316538363536393337623962363963343732626539336561633332333361313637393336653461386435653833303232313030626435616662663836396633633063613237346232666464653466373137313539636233626437313939623263613066663430396465363539613832623234643330383230326565333038323032373561303033303230313032303230383439366432666266336139386461393733303061303630383261383634386365336430343033303233303637333131623330313930363033353530343033306331323431373037303663363532303532366636663734323034333431323032643230343733333331323633303234303630333535303430623063316434313730373036633635323034333635373237343639363636393633363137343639366636653230343137353734363836663732363937343739333131333330313130363033353530343061306330613431373037303663363532303439366536333265333130623330303930363033353530343036313330323535353333303165313730643331333433303335333033363332333333343336333333303561313730643332333933303335333033363332333333343336333333303561333037613331326533303263303630333535303430333063323534313730373036633635323034313730373036633639363336313734363936663665323034393665373436353637373236313734363936663665323034333431323032643230343733333331323633303234303630333535303430623063316434313730373036633635323034333635373237343639363636393633363137343639366636653230343137353734363836663732363937343739333131333330313130363033353530343061306330613431373037303663363532303439366536333265333130623330303930363033353530343036313330323535353333303539333031333036303732613836343863653364303230313036303832613836343863653364303330313037303334323030303466303137313138343139643736343835643531613565323538313037373665383830613265666465376261653464653038646663346239336531333335366435363635623335616532326430393737363064323234653762626130386664373631376365383863623736626236363730626563386538323938346666353434356133383166373330383166343330343630363038326230363031303530353037303130313034336133303338333033363036303832623036303130353035303733303031383632613638373437343730336132663266366636333733373032653631373037303663363532653633366636643266366636333733373033303334326436313730373036633635373236663666373436333631363733333330316430363033353531643065303431363034313432336632343963343466393365346566323765366334663632383663336661326262666432653462333030663036303335353164313330313031666630343035333030333031303166663330316630363033353531643233303431383330313638303134626262306465613135383333383839616134386139396465626562646562616664616362323461623330333730363033353531643166303433303330326533303263613032616130323838363236363837343734373033613266326636333732366332653631373037303663363532653633366636643266363137303730366336353732366636663734363336313637333332653633373236633330306530363033353531643066303130316666303430343033303230313036333031303036306132613836343838366637363336343036303230653034303230353030333030613036303832613836343863653364303430333032303336373030333036343032333033616366373238333531313639396231383666623335633335366361363262666634313765646439306637353464613238656265663139633831356534326237383966383938663739623539396639386435343130643866396465396332666530323330333232646435343432316230613330353737366335646633333833623930363766643137376332633231366439363466633637323639383231323666353466383761376431623939636239623039383932313631303639393066303939323164303030303331383230313863333038323031383830323031303133303831383633303761333132653330326330363033353530343033306332353431373037303663363532303431373037303663363936333631373436393666366532303439366537343635363737323631373436393666366532303433343132303264323034373333333132363330323430363033353530343062306331643431373037303663363532303433363537323734363936363639363336313734363936663665323034313735373436383666373236393734373933313133333031313036303335353034306130633061343137303730366336353230343936653633326533313062333030393036303335353034303631333032353535333032303836383630663639396439636361373066333030643036303936303836343830313635303330343032303130353030613038313935333031383036303932613836343838366637306430313039303333313062303630393261383634383836663730643031303730313330316330363039326138363438383666373064303130393035333130663137306433313339333033383331333333313336333033323332333835613330326130363039326138363438383666373064303130393334333131643330316233303064303630393630383634383031363530333034303230313035303061313061303630383261383634386365336430343033303233303266303630393261383634383836663730643031303930343331323230343230306463316331626362653237356662363066663361663437363239636464353866396263323138333034653866323738613463313830316237353466653839363330306130363038326138363438636533643034303330323034343733303435303232313030396563323139666431396663326661326536373232393730393538333831343338366265343264353864323634303262643665383265633833323636336539333032323033363863323238616362313731393261653434626538366535386235313461636235386337396438663839373936323735653837363730373435363735333432303030303030303030303030227D'
        );


        $this->getModel()->log('info', 'Result apple-pay file write ' . $result);

        return $result !== false;
    }

    /**
     * @param \YooKassa\Model\PaymentInterface $payment
     * @param array $order
     * @param float $amount
     * @param string $comment
     *
     * @return bool
     */
    private function refundPayment($payment, $order, $amount, $comment)
    {
        $response = $this->getModel()->refundPayment($payment, $order, $amount, $comment);
        if ($response === null) {
            return false;
        }

        return true;
    }

    protected function sendResponseJson($json)
    {
        if (isset($this->request->server['HTTP_ORIGIN'])) {
            $this->response->addHeader('Access-Control-Allow-Origin: '.$this->request->server['HTTP_ORIGIN']);
            $this->response->addHeader('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
            $this->response->addHeader('Access-Control-Max-Age: 1000');
            $this->response->addHeader('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function errors_alert($text)
    {
        $html = '<div class="alert alert-danger">
            <i class="fa fa-exclamation-circle"></i> '.$text.'
                <button type="button" class="close" data-dismiss="alert">×</button>
        </div>';

        return $html;
    }

    /**
     * Экшен автообления.
     */
    public function update()
    {
        $data = array();
        $link = $this->url->link('extension/payment/'.self::MODULE_NAME,
            'user_token='.$this->session->data['user_token'],
            true);

        $versionInfo = $this->getModel()->checkModuleVersion();

        if (isset($this->request->post['update']) && $this->request->post['update'] == '1') {
            $fileName = $this->getModel()->downloadLastVersion($versionInfo['tag']);
            $logs     = $this->url->link('extension/payment/'.self::MODULE_NAME.'/logs',
                'user_token='.$this->session->data['user_token'], true);
            if (!empty($fileName)) {
                if ($this->getModel()->createBackup(self::MODULE_VERSION)) {
                    if ($this->getModel()->unpackLastVersion($fileName)) {
                        $this->session->data['flash_message'] = sprintf($this->language->get('updater_success_message'),
                            $this->request->post['version']);
                        $this->response->redirect($link);
                    } else {
                        $data['errors'][] = sprintf($this->language->get('updater_error_unpack_failed'), $fileName);
                    }
                } else {
                    $data['errors'][] = sprintf($this->language->get('updater_error_backup_create_failed'), $logs);
                }
            } else {
                $data['errors'][] = sprintf($this->language->get('updater_error_archive_load'), $logs);
            }
        }

        $this->response->redirect($link);
    }

    /**
     * Экшен работы с бекапами.
     */
    public function backups()
    {
        $link = $this->url->link('extension/payment/'.self::MODULE_NAME,
            'user_token='.$this->session->data['user_token'],
            true);

        if (!empty($this->request->post['action'])) {
            $logs = $this->url->link('extension/payment/'.self::MODULE_NAME.'/logs',
                'user_token='.$this->session->data['user_token'],
                true
            );
            switch ($this->request->post['action']) {
                case 'restore';
                    if (!empty($this->request->post['file_name'])) {
                        if ($this->getModel()->restoreBackup($this->request->post['file_name'])) {
                            $this->session->data['flash_message'] = sprintf($this->language->get('updater_restore_backup_message'),
                                $this->request->post['version'], $this->request->post['file_name']);
                            $this->response->redirect($link);
                        }
                        $data['errors'][] = sprintf($this->language->get('updater_error_restore_backup'), $logs);
                    }
                    break;
                case 'remove':
                    if (!empty($this->request->post['file_name'])) {
                        if ($this->getModel()->removeBackup($this->request->post['file_name'])) {
                            $this->session->data['flash_message'] = sprintf($this->language->get('updater_backup_deleted_message'),
                                $this->request->post['file_name']);
                            $this->response->redirect($link);
                        }
                        $data['errors'][] = sprintf($this->language->get('updater_error_delete_backup'),
                            $this->request->post['file_name'], $logs);
                    }
                    break;
            }
        }

        $this->response->redirect($link);
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    private function setUpdaterData($data)
    {
        $data['update_action']       = $this->url->link('extension/payment/'.self::MODULE_NAME.'/update',
            'user_token='.$this->session->data['user_token'], true);
        $data['backup_action']       = $this->url->link('extension/payment/'.self::MODULE_NAME.'/backups',
            'user_token='.$this->session->data['user_token'], true);
        $version_info                = $this->getModel()->checkModuleVersion(false);
        $data['kassa_payments_link'] = $this->url->link('extension/payment/'.self::MODULE_NAME.'/payments',
            'user_token='.$this->session->data['user_token'], true);
        if (!empty($version_info) && version_compare($version_info['version'], self::MODULE_VERSION) > 0) {
            $data['new_version_available'] = true;
            $data['changelog']             = $this->getModel()->getChangeLog(self::MODULE_VERSION,
                $version_info['version']);
            $data['new_version']           = $version_info['version'];
        } else {
            $data['new_version_available'] = false;
            $data['changelog']             = '';
            $data['new_version']           = self::MODULE_VERSION;
        }
        $data['new_version_info'] = $version_info;
        $data['backups']          = $this->getModel()->getBackupList();

        return $data;
    }

    private function enableB2bSberbank()
    {
        if ($this->request->post['payment_yoomoney_status']
            && isset($this->request->post['yoomoney_kassa_b2b_sberbank_enabled'])
            && $this->request->post['yoomoney_kassa_b2b_sberbank_enabled'] == 'on'
        ) {
            $this->model_setting_setting->editSetting('payment_yoomoney_b2b_sberbank', array(
                'payment_yoomoney_b2b_sberbank_status' => true,
                'payment_yoomoney_sort_order'          => 0,
            ));
        } else {
            $this->model_setting_setting->editSetting('payment_yoomoney_b2b_sberbank', array(
                'payment_yoomoney_b2b_sberbank_status' => false,
            ));
        }
    }

    /**
     * @return array
     */
    private function createKassaCurrencyList()
    {
        $all_currencies = $this->model_localisation_currency->getCurrencies();
        $kassa_currencies = CurrencyCode::getEnabledValues();

        $available_currencies = array();
        foreach ($all_currencies as $key => $item) {
            if (in_array($key, $kassa_currencies) && $item['status'] == 1) {
                $available_currencies[$key] = $item;
            }
        }

        return array_merge(array(
            'RUB' => array(
                'title' => 'Российский рубль',
                'code' => CurrencyCode::RUB,
                'symbol_left' => '',
                'symbol_right' => '₽',
                'decimal_place' => '2',
                'status' => '1',
            )
        ), $available_currencies);
    }

    /**
     * @param $val
     * @param bool $return_null
     * @return bool|mixed|null
     */
    public function isTrue($val, $return_null=false)
    {
        $boolVal = ( is_string($val) ? filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : (bool) $val );
        return ( $boolVal===null && !$return_null ? false : $boolVal );
    }
}
