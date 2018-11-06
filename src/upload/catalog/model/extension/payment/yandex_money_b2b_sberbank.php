<?php

class ModelExtensionPaymentYandexMoneyB2bSberbank extends Model
{
    private $kassaModel;

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
     * @return \YandexMoneyModule\Model\AbstractPaymentModel|null
     */
    public function getPaymentModel()
    {
        if ($this->getKassaModel()->isEnabled()) {
            return $this->getKassaModel();
        }

        return null;
    }

    public function getMethod($address, $total)
    {
        $result = array();
        $this->load->language('extension/payment/yandex_money');

        $model = $this->getPaymentModel();
        if (is_null($model) || $model->getMinPaymentAmount() > 0 && $model->getMinPaymentAmount() > $total) {
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
            'code'       => 'yandex_money_b2b_sberbank',
            'title'      => $this->language->get('yandex_money_b2b_sberbank'),
            'terms'      => '',
            'sort_order' => $this->config->get('yandex_money_sort_order'),
        );

        return $result;
    }
}
