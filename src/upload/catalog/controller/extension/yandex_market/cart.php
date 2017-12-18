<?php

/*
+class: ControllerExtensionYandexMarketCart
+author: Yandex.Money & Alexander Toporkov <toporchillo@gmail.com>
*/
class ControllerExtensionYandexMarketCart extends Controller
{
    /**
     * @var ModelExtensionPaymentYandexMoney
     */
    private $_model;

    private $_prefix;

    public function getinfo()
    {
        $id_product = isset($this->request->post['product_id']) ? $this->request->post['product_id'] : 0;
        $result = array();
        if ($id_product > 0) {
            $opt = isset($this->request->post['option']) ? $this->request->post['option'] : array();
            // $opt = array('227' => 18, '228' => 20);
            $opt_flip = array_flip($opt);
            $this->load->model('catalog/product');
            $product_info = $this->model_catalog_product->getProduct($id_product);
            $result = array();
            $name = array();
            $result['data'] = date('Y-m-d H:i:s');
            $result['action'] = 'add';
            $result['name'] = $product_info['name'];
            $result['quantity'] = $product_info['quantity'] ? $product_info['quantity'] : 1;
            $result['price'] = $product_info['special'] ? $product_info['special'] : $product_info['price'];
            if (count($opt)) {
                $options = $this->model_catalog_product->getProductOptions($id_product);
                if (count($options)) {
                    foreach ($options as $option) {
                        if (in_array($option['product_option_id'], $opt_flip)) {
                            foreach ($option['product_option_value'] as $o) {
                                if (in_array($o['product_option_value_id'], $opt)) {
                                    $name[] = $o['name'];
                                    if ($o['price_prefix'] == '+') {
                                        $result['price'] += $o['price'];
                                    }
                                    if ($o['price_prefix'] == '-') {
                                        $result['price'] -= $o['price'];
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $result['name'] = $result['name'].' '.implode(' ', $name);
        }
        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        $this->response->setOutput(json_encode($result));
    }

    public function index()
    {
        $sign = $this->config->get('yandex_money_pokupki_stoken');
        $key = isset($_REQUEST['auth-token']) ? $_REQUEST['auth-token'] : '';
        if (strtoupper($sign) !== strtoupper($key)) {
            header('HTTP/1.0 403 Forbidden');
            echo '<h1>Wrong token</h1>';
            exit;
        } else {
            $json = file_get_contents("php://input");
            $this->getModel()->log('info', 'pokupki cart request: '.$json);
            if (!$json) {
                header('HTTP/1.0 404 Not Found');
                echo '<h1>No data posted</h1>';
                exit;
            } else {
                $data = json_decode($json);
                $payments = array();
                $carriers = array();
                $items = array();
                $this->load->model('catalog/product');
                $this->load->model('localisation/country');
                $shop_currency = $this->config->get('config_currency');//RUB
                $offers_currency = ($data->cart->currency == 'RUR') ? 'RUB' : $data->cart->currency;
                $decimal_place = $this->currency->getDecimalPlace($offers_currency);
                foreach ($data->cart->items as $item) {
                    $add = true;
                    $id_array = explode('c', $item->offerId);
                    $id_product = $id_array[0];
                    $price_option = 0;
                    $product_info = $this->model_catalog_product->getProduct($id_product);
                    if (!$product_info['status']) {
                        continue;
                    }

                    if (count($id_array) > 1) {
                        unset($id_array[0]);
                        foreach ($this->model_catalog_product->getProductOptions($id_product) as $option) {
                            foreach ($option['product_option_value'] as $value)
                            {
                                if (!in_array($value['option_value_id'], $id_array)) {
                                    continue;
                                }
                                if ($value['quantity'] < $item->count || $value['quantity'] <= 0) {
                                    $add = false;
                                    break;
                                }

                                if ($value['price_prefix'] == '+') {
                                    $price_option += $value['price'];
                                } elseif ($value['price_prefix'] == '-') {
                                    $price_option -= $value['price'];
                                }
                            }
                            if (!$add) {
                                break;
                            }
                        }
                    }

                    if ($add) {
                        if ($item->count < $product_info['minimum'] || $product_info['quantity'] < $item->count || $product_info['quantity'] <= 0) {
                            continue;
                        }

                        $count = min($product_info['quantity'], (int)$item->count);
                        if ($product_info['special'] && $product_info['special'] < $product_info['price']) {
                            $total = number_format($this->currency->convert($this->tax->calculate($product_info['special'] + $price_option, $product_info['tax_class_id'], $this->config->get('config_tax')), $shop_currency, $offers_currency), $decimal_place, '.', '');
                        } else {
                            $total = number_format($this->currency->convert($this->tax->calculate($product_info['price'] + $price_option, $product_info['tax_class_id'], $this->config->get('config_tax')), $shop_currency, $offers_currency), $decimal_place, '.', '');
                        }

                        $items[] = array(
                            'feedId' => $item->feedId,
                            'offerId' => $item->offerId,
                            'price' => (float)$total,
                            'count' => (int)$count,
                            'delivery' => true,
                        );
                    }
                }

                if (count($items)) {
                    $region = self::get_region($data->cart->delivery->region);
                    $paymentModel = $this->getModel();
                    $model = new \YandexMoneyModule\Model\OrdersModel($this->registry);

                    $country_id = $model->getCountryId($region['country']);
                    $zone_id = $model->getZoneId($region['zone'], $country_id);
                    // $postcode_id = $this->model_yamodel_pokupki->getPostCode($region['district_id']);
                    // "id": 3, "name": "Центральный федеральный округ", "type": "COUNTRY_DISTRICT"

                    $address_array = array(
                        'firstname'      => '',
                        'lastname'       => '',
                        'company'        => '',
                        'address_1'      => '',
                        'address_2'      => '',
                        'postcode'       => '',
                        'city'           => $region['city'],
                        'zone_id'        => $zone_id,
                        'zone'           => '',
                        'zone_code'      => '',
                        'country_id'     => $country_id,
                        'country'        => '',
                        'iso_code_2'     => '',
                        'iso_code_3'     => '',
                        'address_format' => ''
                    );
                    if (count($data->cart->items)) {
                        $this->cart->clear();
                        $count_items = 0;
                        foreach ($data->cart->items as $item) {
                            $opt = array();
                            $id_array = explode('c', $item->offerId);
                            $id_product = $id_array[0];
                            $product_info = $this->model_catalog_product->getProduct($id_product);
                            if (count($id_array) > 1) {
                                unset($id_array[0]);
                                foreach ($this->model_catalog_product->getProductOptions($id_product) as $options) {
                                    foreach ($options['product_option_value'] as $option) {
                                        if (in_array($option['option_value_id'], $id_array)) {
                                            $opt[$options['product_option_id']] = $option['product_option_value_id'];
                                        }
                                    }
                                }
                            }
                            $count_items += (int)$item->count;
                            $this->cart->add($id_product, $item->count, $opt);
                        }
                    }
                    $this->load->model('extension/extension');
                    $results = $this->model_extension_extension->getExtensions('shipping');
                    $k = 0;
                    $pickup = array();
                    foreach ($results as $result) {
                        if ($this->config->get($result['code'] . '_status')) {
                            $this->load->model('extension/shipping/'.$result['code']);
                            $quote = $this->{'model_extension_shipping_'.$result['code']}->getQuote($address_array);
                            $id = $result['code'];
                            $types = $this->config->get('yandex_money_pokupki_carrier');
                            $type = isset($types[$id]) ? $types[$id] : 'POST';
                            if ($type == "POST" && $address_array['postcode'] == "") {
                                $address_array['postcode'] = (property_exists($data->cart->delivery, "address") &&
                                    property_exists($data->cart->delivery->address, "postcode")) ? $data->cart->delivery->address->postcode : "000000"; // Индекс домашнего региона
                            }
                            if ($type == "SKIP") {
                                continue;
                            }
                            $quote = $this->{'model_extension_shipping_'.$result['code']}->getQuote($address_array);
                            if ($quote) {
                                //TODO Добавить определение названия доставок в настройках модуля
                                $title_ship = $quote['title'];//(isset($quote['title2']))?$quote['title2']: $quote['title'];

                                $carriers[$k] = array();
                                $carriers[$k]['id'] = $result['code'];//$result['extension_id'];

                                $carriers[$k]['serviceName'] = $title_ship;
                                if ($type == 'POST') {
                                    $carriers[$k]['paymentAllow'] = false;
                                }

                                $carriers[$k]['type'] = $type;
                                if (!isset($quote['quote'][$result['code']])) {
                                    $this->getModel()->log('warning', "См. Cart.php, т.к. в списке доставок отсутствует ключ ".$result['code']);
                                    continue;
                                }
                                $carriers[$k]['price'] = (float)number_format(
                                    $this->currency->convert(
                                        $this->tax->calculate(
                                            $quote['quote'][$result['code']]['cost'],
                                            $product_info['tax_class_id'],
                                            $this->config->get('config_tax')
                                        ),
                                        $shop_currency,
                                        $offers_currency
                                    ),
                                    $decimal_place, '.', '');
                                //TODO Дата доставки ставим на 24 часа от сегодняшнего дня. Добавить в настройки модуля
                                $carriers[$k]['dates'] = array(
                                    'fromDate' => date('d-m-Y', time()),
                                    'toDate' => date('d-m-Y', time()+24*60*60),
                                );
                                if($type == 'PICKUP') {
                                    $outlets = $model->getOutlets();
                                    $market_id = self::preOutlets($outlets['json']['outlets'], $quote['sort_order']);
                                    $market_outlet = (int) $outlets['array'][$market_id]->id;
                                    $carriers[$k]['outlets'][] = array(
                                        'id'=> $market_outlet
                                    );
                                }
                                $k++;
                            }
                        }
                    }

                    if ($this->config->get('yandex_money_pokupki_yandex')) {
                        $payments[] = 'YANDEX';
                    }

                    if ($this->config->get('yandex_money_pokupki_cash')) {
                        $payments[] = 'CASH_ON_DELIVERY';
                    }

                    if ($this->config->get('yandex_money_pokupki_bank')) {
                        $payments[] = 'CARD_ON_DELIVERY';
                    }
                }

                $array = array(
                    'cart' => array(
                        'items' => $items,
                        'deliveryOptions' => $carriers,
                        'paymentMethods' => $payments
                    )
                );

                $this->response->addHeader('Content-Type: application/json; charset=utf-8');
                $this->response->setOutput(json_encode($array));
            }
        }
    }

    private static function preOutlets($list, $find)
    {
        foreach ($list as $key_outlet => $val_outlet) {
            if ($val_outlet['id'] == $find) {
                return $key_outlet;
            }
        }
        return false;
    }

    private static function get_region($region)
    {
        $item = $region;
        $iStop = 0;
        $data = array(
            "district_id" => "",
            "city" => "",
            "zone" => "",
            "country" => ""
        );
        do {
            switch ($item->type) {
                case 'COUNTRY_DISTRICT':
                    $data["district_id"] = $item->id;
                    break;
                case 'CITY':
                    $data["city"] = $item->name;
                    break;
                case 'SUBJECT_FEDERATION':
                    $data["zone"] = $item->name;
                    break;
                case 'COUNTRY':
                    $data["country"] = $item->name;
                    break;

            }
            $item = (property_exists($item, 'parent')) ? $item->parent : null;
            $item = ($iStop > 15) ? null : $item;
            $iStop++;
        } while ($item !== null);
        return $data;
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
}
