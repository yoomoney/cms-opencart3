<?php

namespace YandexMoneyModule;

class YandexMarket
{
    private $config;

    private $from_charset = 'windows-1251';
    private $shop = array('name' => '', 'company' => '', 'url' => '', 'platform' => 'ya_opencart');
    private $currencies = array();
    private $categories = array();
    private $offers = array();

    public function __construct(&$config)
    {
        $this->config = $config;
    }

    public function yml($from_charset = 'windows-1251')
    {
        $this->from_charset = trim(strtolower($from_charset));
    }

    public function convert_array_to_tag($arr)
    {
        $s = '';
        foreach ($arr as $tag => $val) {
            if ($tag == 'weight' && (int)$val == 0) {
                continue;
            }
            if ($tag == 'picture') {
                foreach ($val as $v) {
                    $s .= '<'.$tag.'>'.$v.'</'.$tag.'>';
                    $s .= PHP_EOL;
                }
            } elseif ($tag == 'param') {
                foreach ($val as $v) {
                    $s .= '<param name="'.$this->prepare_field($v['name']).'">'.$this->prepare_field($v['value']).'</param>';
                    $s .= PHP_EOL;
                }
            } elseif ($tag == 'delivery-options') {
                foreach ($val as $v) {
                    $s .= '<delivery-options>'.PHP_EOL.
                        '<option cost="'.$v['cost'].'" days="'.$v['days'].'"/>'.PHP_EOL.
                        '</delivery-options>'.PHP_EOL;
                }
            } else {
                $s .= '<'.$tag.'>'.$val.'</'.$tag.'>';
                $s .= PHP_EOL;
            }
        }
        return $s;
    }

    public function convert_array_to_attr($arr, $tagName, $tagValue = '')
    {
        $s = '<'.$tagName.' ';
        foreach ($arr as $attrName => $attrVal) {
            $s .= $attrName . '="' . $attrVal . '" ';
        }
        $s .= ($tagValue != '') ? '>' . $tagValue . '</' . $tagName . '>' : '/>';
        $s .= PHP_EOL;
        return $s;
    }

    public function prepare_field($s)
    {
        $from = array('"', '&', '>', '<', '\'');
        $to = array('&quot;', '&amp;', '&gt;', '&lt;', '&apos;');
        $s = str_replace($from, $to, $s);
        $s=preg_replace('!<[^>]*?>!', ' ', $s);
        // if ($this->from_charset!='windows-1251') $s = iconv($this->from_charset, 'windows-1251', $s);
        $s = preg_replace('#[\x00-\x08\x0B-\x0C\x0E-\x1F]+#is', ' ', $s);
        return trim($s);
    }

    public function set_shop($name, $company, $url)
    {
        $this->shop['name'] = $this->prepare_field($name);
        $this->shop['name'] = mb_substr(mb_convert_encoding($this->shop['name'], "UTF-8"), 0, 20);
        $this->shop['company'] = $this->prepare_field($company);
        $this->shop['url'] = $this->prepare_field($url);
    }

    public function add_currency($id, $rate = 'CBRF', $plus = 0)
    {
        $rate = strtoupper($rate);
        $plus = str_replace(',', '.', $plus);
        if ($rate == 'CBRF' && $plus > 0) {
            $this->currencies[] = array(
                'id' => $this->prepare_field(strtoupper($id)),
                'rate' => 'CBRF',
                'plus' => (float)$plus
            );
        } else {
            $rate = str_replace(',', '.', $rate);
            $this->currencies[] = array(
                'id' => $this->prepare_field(strtoupper($id)),
                'rate' => (float)$rate
            );
        }
        return true;
    }

    function add_category($name, $id, $parent_id = -1)
    {
        if ((int)$id < 1 || trim($name) == '') {
            return false;
        }
        if ((int)$parent_id > 0) {
            $this->categories[] = array(
                'id' => (int)$id,
                'parentId' => (int)$parent_id,
                'name' => $this->prepare_field($name)
            );
        } else {
            $this->categories[] = array(
                'id' => (int)$id,
                'name' => $this->prepare_field($name)
            );
        }
        return true;
    }

    public function add_offer($id, $data, $available = true, $group_id = 0)
    {
        $allowed = array(
            'url', 'price', 'oldprice', 'currencyId', 'categoryId', 'picture', 'store', 'pickup', 'delivery',
            'name', 'vendor', 'vendorCode', 'model', 'description', 'sales_notes','oldprice',
            'delivery-options','downloadable', 'weight', 'dimensions', 'param', 'country_of_origin'
        );
        foreach($data as $k => $v) {
            if (!in_array($k, $allowed)) {
                unset($data[$k]);
            }
            if (!in_array($k, array('picture', 'param', 'rec', 'description', 'delivery-options'))) {
                $data[$k] = strip_tags($this->prepare_field($v));
            }
            if ($k == 'description') {
                $data[$k] = preg_replace('|<[/]?[^>]+?>|', '', trim(html_entity_decode ($v)));
                $data[$k] = preg_replace("/&#?[a-z0-9]+;/i", '', $data[$k]);
                if (strlen($data[$k]) >= 3000) {
                    $iCut = strpos($data[$k], ' ', 2950);
                    $data[$k] = substr($data[$k], 0, $iCut);
                }
            }
        }
        $tmp = $data;
        $data = array();
        foreach($allowed as $key) {
            if (isset($tmp[$key]) && !empty($tmp[$key])) {
                $data[$key] = $tmp[$key];
            }
        }

        $out = array(
            'id' => $id,
            'data' => $data,
            'available' => ($available) ? 'true' : 'false'
        );
        if ($group_id > 0) {
            $out['group_id'] = $group_id;
        }
        if (!$this->config->get('yandex_money_market_prostoy')) {
            $out['type'] = 'vendor.model';
        }
        $this->offers[] = $out;
    }

    private function get_xml_header()
    {
        return '<?xml version="1.0" encoding="utf-8"?>'.
            '<yml_catalog date="'.date('Y-m-d H:i').'">';
    }

    private function get_xml_shop()
    {
        $s = '<shop>' . PHP_EOL;
        $s .= $this->convert_array_to_tag($this->shop);
        $s .= '<currencies>' . PHP_EOL;
        foreach($this->currencies as $currency) {
            $s .= $this->convert_array_to_attr($currency, 'currency');
        }

        $s .= '</currencies>' . PHP_EOL;
        $s .= '<categories>' . PHP_EOL;
        foreach($this->categories as $category) {
            $category_name = $category['name'];
            unset($category['name']);
            $s .= $this->convert_array_to_attr($category, 'category', $category_name);
        }
        $s .= '</categories>' . PHP_EOL;

        $localShippingCost = explode (';', $this->config->get('yandex_money_market_localcoast'));
        $localShippingDays = explode (';', $this->config->get('yandex_money_market_localdays'));
        if (count($localShippingCost) != count ($localShippingDays)) {
            throw new \Exception("'Стоимость доставки в домашнем регионе' и/или 'Срок доставки в домашнем регионе' заполнены с ошибкой");
        }
        $s .= '<delivery-options>'. PHP_EOL;
        foreach ($localShippingCost as $key=>$value) {
            $s .= '<option cost="'.$value.'" days="'.$localShippingDays[$key].'"/>'. PHP_EOL;
        }
        $s .=  '</delivery-options>' . PHP_EOL;

        $s .= '<offers>' . PHP_EOL;
        foreach($this->offers as $offer) {
            $data = $offer['data'];
            unset($offer['data']);
            $s .= $this->convert_array_to_attr($offer, 'offer', $this->convert_array_to_tag($data));
        }
        $s .= '</offers>' . PHP_EOL;
        $s .= '</shop>';
        return $s;
    }

    private function get_xml_footer()
    {
        return '</yml_catalog>';
    }

    public function get_xml()
    {
        $xml = $this->get_xml_header();
        $xml .= $this->get_xml_shop();
        $xml .= $this->get_xml_footer();
        return $xml;
    }
}