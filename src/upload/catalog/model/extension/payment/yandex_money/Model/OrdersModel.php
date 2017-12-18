<?php

namespace YandexMoneyModule\Model;

/**
 * Class OrdersModel
 * @package YandexMoneyModule\Model
 *
 * @property-read \Loader $load
 * @property-read \Config $config
 * @property-read \Db $db
 * @property-read \ModelCheckoutOrder $model_checkout_order
 */
class OrdersModel
{
    /**
     * @var \Registry
     */
    private $registry;

    public function __construct($model)
    {
        $this->registry = $model;
    }

    public function __get($property)
    {
        return $this->registry->get($property);
    }

    public function getscript($id)
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
        foreach($product_array as $k => $product) {
            $products[$k]['id'] = $product['product_id'];
            $products[$k]['name'] = $product['name'];
            $products[$k]['quantity'] = $product['quantity'];
            $products[$k]['price'] = $product['price'];
        }

        $ret['goods'] = $products;
        if ($this->config->get('yandex_money_metrika_order')) {
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
        $query = $this->db->query(
            "SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'"
        );
        return $query->rows;
    }

    public function getCountryId($name)
    {
        if ($name == "Россия") {
            $name = "Российская Федерация";
        }
        $query = $this->db->query(
            "SELECT `country_id` `id` FROM `" . DB_PREFIX . "country` WHERE `name` ='".$name."'"
        );
        if ($query && $query->num_rows) {
            return $query->row['id'];
        }
        return 0;
    }

    public function getQuoteShipping($shipping_id, $address)
    {
        $for23 = (version_compare(VERSION, "2.3.0", '>='))?"extension/":"";
        $this->load->model($for23.'shipping/'.$shipping_id);
        $quote = $this->registry->{'model_'.str_replace("/", "_", $for23).'shipping_'.$shipping_id}->getQuote($address);
        if ($quote) {
            return $quote['quote'][$shipping_id];
        }
        return "";
    }

    public function getZoneId($name, $country_id)
    {
        $query = $this->db->query(
            "SELECT `zone_id` `id` FROM `" . DB_PREFIX . "zone` WHERE `name` LIKE ('%".$name."%') AND `country_id`='".$country_id."'"
        );
        return (isset($query->row['id'])) ? $query->row['id'] : 0;
    }

    public function getOrders()
    {
        $number = $this->config->get('yandex_money_pokupki_number');
        return $data = $this->sendResponse('/campaigns/'.$number.'/orders', array(), array(), 'GET');
    }

    public function sendDelivery($delivery, $id)
    {
        $params = array(
            'delivery' => $delivery
        );
        $number = $this->config->get('yandex_money_pokupki_number');
        return $data = $this->sendResponse('/campaigns/'.$number.'/orders/'.$id.'/delivery', array(), $params, 'PUT');
    }

    public function getOutlets()
    {
        $number = $this->config->get('yandex_money_pokupki_number');
        $data = $this->sendResponse('/campaigns/'.$number.'/outlets', array(), array(), 'GET');
        $array = array('outlets' => array());
        foreach ($data->outlets as $o) {
            $array['outlets'][] = array('id' => (int)$o->shopOutletId);
        }
        return array(
            'json' => $array,
            'array' => $data->outlets
        );
    }

    public function getOrder($id)
    {
        $number = $this->config->get('yandex_money_pokupki_number');
        $data = $this->sendResponse('/campaigns/'.$number.'/orders/'.$id, array(), array(), 'GET');
        return $data;
    }

    public function sendOrder($state, $id)
    {
        $params = array(
            'order' => array(
                'status' => $state,
            )
        );
        $number = $this->config->get('yandex_money_pokupki_number');
        if($state == 'CANCELLED') $params['order']['substatus'] = 'SHOP_FAILED';

        return $data = $this->sendResponse('/campaigns/'.$number.'/orders/'.$id.'/status', array(), $params, 'PUT');
    }

    public function sendResponse($to, $headers, $params, $type)
    {
        $app_id = $this->config->get('yandex_money_pokupki_idapp');
        $url = 'https://api.partner.market.yandex.ru/v2';//$this->config->get('yandex_money_pokupki_yapi');
        //$login = $this->config->get('yandex_money_pokupki_login');
        //$app_pw = $this->config->get('yandex_money_pokupki_upw');
        $ya_token = $this->config->get('yandex_money_pokupki_gtoken');
        $response = $this->post($url.$to.'.json?oauth_token='.$ya_token.'&oauth_client_id='.$app_id, $headers, $params, $type);
        $data = json_decode($response->body);
        if (isset($data->error)) {
            $this->log_save($response->body);
        }
        if ($response->status_code == 200) {
            return $data;
        }
        else {
            die(print_r($response));
        }
    }

    public static function log_save($logtext)
    {
        $error_log = new \Log('error.log');
        $error_log->write($logtext.PHP_EOL);
        $error_log = null;
    }

    public static function post($url, $headers, $params, $type)
    {
        $curlOpt = array(
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLINFO_HEADER_OUT => 1,
        );

        switch (strtoupper($type)){
            case 'DELETE':
                $curlOpt[CURLOPT_CUSTOMREQUEST] = "DELETE";
            case 'GET':
                if (!empty($params))
                    $url .= (strpos($url, '?')===false ? '?' : '&') . http_build_query($params);
                break;
            case 'PUT':
                $headers[] = 'Content-Type: application/json;';
                $body = json_encode($params);
                $fp = tmpfile();
                fwrite($fp, $body, strlen($body));
                fseek($fp, 0);
                $curlOpt[CURLOPT_PUT] = true;
                $curlOpt[CURLOPT_INFILE] = $fp;
                $curlOpt[CURLOPT_INFILESIZE] = strlen($body);
                break;
        }

        $curlOpt[CURLOPT_HTTPHEADER] = $headers;
        $curl = curl_init($url);
        curl_setopt_array($curl, $curlOpt);
        $rbody = curl_exec($curl);
        $errno = curl_errno($curl);
        $error = curl_error($curl);
        $rcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        $result = new \stdClass();
        $result->status_code = $rcode;
        $result->body = $rbody;
        $result->error = $error;
        return $result;
    }
}
