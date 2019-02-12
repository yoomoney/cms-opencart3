<?php

require_once DIR_CATALOG . 'model/extension/payment/yandex_money/autoload.php';

class YandexMoneyMetrikaModel
{
    const URL = 'https://api-metrika.yandex.ru/management/v1/';

    /**
     * @param int $number
     * @param string $token
     * @return mixed
     */
    public function getCounterCode($number, $token)
    {
        return $this->SendResponse('counter/'.$number, $token, array(), array(), 'GET');
    }

    /**
     * @param int $number
     * @param string $token
     * @param array $options
     * @return mixed
     */
    public function saveCounterOptions($number, $token, $options)
    {
        $params = array(
            'counter' => array(
                'code_options' => array(
                    'clickmap'   => (int)$options['clickmap'],
                    'visor'      => (int)$options['visor'],
                    'track_hash' => (int)$options['track_hash'],
                    'ecommerce'  => 1,
                    'informer' => array(
                        'enabled' => 0,
                    ),
                ),
            ),
        );

        return $this->SendResponse('counter/'.$number, $token, array(), $params, 'PUT');
    }

    /**
     * @param string $to
     * @param string $token
     * @param array $headers
     * @param array $params
     * @param string $type
     * @return mixed
     */
    private function SendResponse($to, $token, $headers, $params, $type)
    {
        $headers[] = 'Authorization: OAuth '.$token;
        return $this->post(self::URL.$to, $headers, $params, $type);
    }

    /**
     * @param string $url
     * @param array $headers
     * @param array $params
     * @param string $type
     * @return stdClass
     */
    private static function post($url, $headers, $params, $type)
    {
        $curlOpt = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLINFO_HEADER_OUT    => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        );

        switch (strtoupper($type)) {
            case 'GET':
                if (!empty($params)) {
                    $url .= (strpos($url, '?') === false ? '?' : '&').http_build_query($params);
                }
                break;
            case 'PUT':
                $json      = json_encode($params);
                $headers[] = 'Content-Type: application/json';
                $headers[] = 'Content-Length: '.strlen($json);

                $curlOpt[CURLOPT_CUSTOMREQUEST]  = 'PUT';
                $curlOpt[CURLOPT_POSTFIELDS]     = $json;
                break;
        }

        $curlOpt[CURLOPT_HTTPHEADER]     = $headers;
        $curl = curl_init($url);
        curl_setopt_array($curl, $curlOpt);
        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }
}