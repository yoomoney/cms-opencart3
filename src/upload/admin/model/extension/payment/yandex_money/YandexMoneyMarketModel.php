<?php

class YandexMoneyMarketModel
{
    const VAT_18 = 'VAT_18';
    const VAT_10 = 'VAT_10';
    const VAT_18_118 = 'VAT_18_118';
    const VAT_10_110 = 'VAT_10_110';
    const VAT_0 = 'VAT_0';
    const NO_VAT = 'NO_VAT';

    const CURRENCY_RUB = 'RUB';
    const CURRENCY_USD = 'USD';
    const CURRENCY_EUR = 'EUR';
    const CURRENCY_UAH = 'UAH';
    const CURRENCY_BYN = 'BYN';
    const CURRENCY_KZT = 'KZT';

    const RATE_MAIN_CURRENCY = '1';
    const RATE_CBRF = 'CBRF';
    const RATE_NBU = 'NBU';
    const RATE_NBK = 'NBK';
    const RATE_CB = 'CB';
    const RATE___CMS = '__cms';

    const MAX_CATEGORY_NUMBER = 3;

    private $config;

    private $db;

    private $language;

    private $stock_status;

    private $option;

    private $fields = array(
        'yandex_money_market_shopname',
        'yandex_money_market_full_shopname',
        'yandex_money_market_features',
        'yandex_money_market_dimensions',
        'yandex_money_market_category_list',
        'yandex_money_market_category_all',
        'yandex_money_market_simple',
        'yandex_money_market_vat_enabled',
        'yandex_money_market_name_template',
    );

    private $productFields = array(
        'name',
        'description',
        'model',
        'sku',
        'upc',
        'ean',
        'jan',
        'isbn',
        'mpn',
        'location',
        'shipping',
        'manufacturer',
        'date_available',
        'quantity',
        'minimum',
        'price',
        'points',
        'weight',
        'length',
        'width',
        'height',
        'image',
        'status'
    );

    private $currencyIds = array(
        self::CURRENCY_RUB,
        self::CURRENCY_USD,
        self::CURRENCY_EUR,
        self::CURRENCY_UAH,
        self::CURRENCY_BYN,
        self::CURRENCY_KZT
    );

    private $currencyRates = array(self::RATE_CBRF, self::RATE_NBU, self::RATE_NBK, self::RATE_CB, self::RATE___CMS);

    private $vatIds = array(self::VAT_18, self::VAT_10, self::VAT_18_118, self::VAT_10_110, self::VAT_0, self::NO_VAT);

    private $taxClasses = array();

    private $defaultCurrency = '';

    private $tax_class = array();

    private $vatRates = array();

    private $catalog_category = array();

    private $categories = array();

    /**
     * @param $config
     * @param $db
     * @param $language
     * @param $stock_status
     * @param $option
     * @param $tax_class
     * @param $catalog_category
     */
    public function __construct($config, $db, $language, $stock_status, $option, $tax_class, $catalog_category)
    {
        $this->db               = $db;
        $this->config           = $config;
        $this->language         = $language;
        $this->stock_status     = $stock_status;
        $this->defaultCurrency  = $this->config->get('config_currency');
        $this->option           = $option;
        $this->tax_class        = $tax_class;
        $this->catalog_category = $catalog_category;

        $this->taxClasses = $this->tax_class->getTaxClasses();
        $this->vatRates   = (array)$this->config->get('yandex_money_market_vat');
    }

    /**
     * @return string[]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @return string[]
     */
    public function getCurrencyIds()
    {
        return $this->currencyIds;
    }

    /**
     * @return array
     */
    public function getTaxClasses()
    {
        return $this->taxClasses;
    }

    /**
     * @param $taxClassId
     * @return array
     */
    public function getTaxClass($taxClassId)
    {
        if (isset($this->taxClasses[$taxClassId])) {
            return $this->taxClasses[$taxClassId];
        }

        return array();
    }

    /**
     * @return array
     */
    public function getVatList()
    {
        $result = array();
        foreach ($this->vatIds as $vatId) {
            $result[$vatId] = $this->language->get('market_vat_rate_'.$vatId.'_label');
        }

        return $result;
    }

    /**
     * @param $taxClassId
     * @return string
     */
    public function getVatRateId($taxClassId)
    {
        if (isset($this->vatRates[$taxClassId])) {
            return $this->vatRates[$taxClassId];
        }

        return '';
    }

    /**
     * @return array
     */
    public function checkConfig()
    {
        $errors = array();
        if (empty($this->config->get('yandex_money_market_shopname'))) {
            $errors[] = 'market_error_message_empty_shop_name';
        }

        return $errors;
    }


    /**
     * @return array
     */
    public function getCategories()
    {
        if (empty($this->categories)) {
            $categories = array();
            foreach ($this->catalog_category->getCategories(array('sort' => 'name')) as $category) {
                $names                                = explode('&nbsp;&nbsp;&gt;&nbsp;&nbsp;', $category['name']);
                $category['name']                     = end($names);
                $categories[$category['parent_id']][] = $category;
            }
            $this->categories = $categories;
        }

        return $this->categories;
    }

    /**
     * @param array $checkedList
     * @param string $inputName
     * @return string
     */
    public function treeCat(array $checkedList, $inputName = ' name="yandex_money_market_category_list[]"')
    {
        $html = $this->treeFolder($this->getCategories(), 0, $checkedList, $inputName);

        return $html;
    }

    /**
     * @param array $categories
     * @param string $id
     * @param array $checkedList
     * @param string $inputAttr
     * @return string
     */
    private function treeFolder($categories, $id, $checkedList, $inputAttr)
    {
        if (!isset($categories[$id])) {
            return '';
        }
        $html = '';
        foreach ($categories[$id] as $category) {
            $checked = in_array($category['category_id'], $checkedList) ? ' checked' : '';
            $html    .= '<li>
            <span>
                <label>
                    <input type="checkbox" '.$inputAttr.' value="'.$category['category_id'].'" '.$checked.'>
                    '.$category['name'].'
                </label>
            </span>';
            if (isset($categories[$category['category_id']])) {
                $html .= '<ul class="yandex_money_market_category_tree_branch">'
                    .$this->treeFolder($categories, $category['category_id'], $checkedList, $inputAttr)
                    .'</ul>';
            }
            $html .= '</li>';
        }

        return $html;
    }

    /**
     * @param array $cmsCurrencies
     * @return string
     */
    public function htmlCurrencyList($cmsCurrencies)
    {
        $html = '';

        $cmsCurrencyIds = array_keys($cmsCurrencies);

        foreach ($this->currencyIds as $currencyId) {
            $html .= $this->htmlCurrency($currencyId, $cmsCurrencyIds);
        }

        return $html;
    }

    private function htmlCurrency($id, $cmsCurrencyIds)
    {
        if (!in_array($id, $cmsCurrencyIds)) {
            return '
<label class="form-check-label yandex-money-market-font-weight-normal yandex_money_market_currency yandex-money-market-currency-disabled">
    <input type="checkbox" disabled="disabled">
    '.$id.'
</label><br>';
        }

        $enabled = $this->getConfig("currency_enabled", $id);
        $rate    = $this->getConfig("currency_rate", $id);
        $plus    = (float)$this->getConfig("currency_plus", $id, 0);
        if ($id === $this->defaultCurrency) {
            $rate = self::RATE_MAIN_CURRENCY;
            $plus = '';
        }

        $htmlView = $this->htmlCurrencyView($rate, $plus);
        $htmlEdit = $this->htmlCurrencyEdit($id, $rate, $plus);

        $checked = $enabled === 'on' ? 'checked="checked"' : '';

        $jsEditableClass = $rate !== self::RATE_MAIN_CURRENCY ? 'yandex-money-market-js-editable' : '';
        $saveRate = $rate !== self::RATE_MAIN_CURRENCY ? $rate : '';

        $html = <<<HTML
            <label class="form-check-label yandex-money-market-font-weight-normal yandex_money_market_currency yandex-money-market-width-100-percent {$jsEditableClass}">
                <input type="checkbox" name="yandex_money_market_currency_enabled[{$id}]" value="on"
                    class="form-check-input" {$checked} />
                <span id="yandex_money_market_currency_byn_text">{$id}</span>
                <input type="hidden" name="yandex_money_market_currency_rate[{$id}]" value="{$saveRate}"
                    class="yandex_money_market_currency_input_rate">
                <input type="hidden" name="yandex_money_market_currency_plus[{$id}]" value="{$plus}"                
                    class="yandex_money_market_currency_input_plus">
                {$htmlView}              
                <span class="yandex_money_market_currency_edit_on_button yandex-money-market-edit-on-button fa fa-pencil-square-o"></span>
                {$htmlEdit}
            </label><br>
HTML;
        return $html;
    }

    private function htmlCurrencyView($rate, $plus)
    {
        $rateText = $this->language->get('market_currencies_rate_'.$rate);
        if ($rateText === 'market_currencies_rate_') {
            $rateText = '';
        }
        $plusText = $this->language->get('market_currencies_plus');
        $hidden   = $rate === self::RATE_MAIN_CURRENCY ? ' style="display:none;"' : '';

        $html = <<< HTML
            <span class="yandex_money_market_js_editable_view">
                <span class="yandex_money_market_currency_view_rate">
                    {$rateText}
                </span>
                <span class="yandex_money_market_currency_view_plus" {$hidden}>
                    ({$plusText} 
                    <span class="yandex_money_market_currency_view_plus_value">{$plus}</span>%)
                </span>
            </span>
HTML;
        return $html;
    }

    private function htmlCurrencyEdit($id, $rate, $plus)
    {
        $select     = $this->htmlCurrencySelect($id, $rate);
        $okText     = $this->language->get('ok');
        $cancelText = $this->language->get('cancel');
        $plusText   = $this->language->get('market_currencies_plus');

        $html = <<<HTML
        <div  class="yandex-money-market-js-editable-edit yandex-money-market-currency-edit yandex-money-market-width-100-percent">
            <form class="yandex-money-market-width-100-percent">
                <div class="form-group">
                    <label class="col-sm-3 control-label yandex-money-market-font-weight-normal"></label>
                    <div class="col-sm-9">
                        {$select}
                    </div>
                </div>        
                <div class="form-group">
                    <label class="col-sm-3 control-label yandex-money-market-font-weight-normal yandex-money-market-first-letter-uppercase">{$plusText}</label>
                    <div class="col-sm-9">
                        <input type="text" value="{$plus}" size="3" maxlength="3"
                            class="form-control yandex_money_market_currency_edit_plus">
                    </span>
                    </div>
                </div>        
                <div class="form-group">
                    <label class="col-sm-3"></label>
                    <div class="col-sm-9">
                        <input type="button" class="btn btn-default edit_finish" value="{$okText}"/>
                        <input type="reset" class="btn btn-default edit_finish" value="{$cancelText}"/>
                    </div>
                </div>  
            </form>     
        </div> 
HTML;
        return $html;
    }

    private function htmlCurrencySelect($id, $rate)
    {
        $html = <<<HTML
            <select name="yandex_money_market_currency_rate[{$id}]" class="form-control yandex_money_market_currency_rate">
HTML;
        foreach ($this->currencyRates as $rateKey) {
            $html .= $this->htmlCurrencyOption($rateKey, $rate);
        }
        $html .= '</select>';

        return $html;
    }

    private function htmlCurrencyOption($rateKey, $rate)
    {
        $selected = $rate === $rateKey ? 'selected="selected"' : '';
        $rateText = $this->language->get('market_currencies_rate_'.$rateKey);

        return <<<HTML
            <option value="{$rateKey}" {$selected}>{$rateText}</option>
HTML;
    }

    /**
     * @return string
     */
    public function htmlDeliveryList()
    {
        $html = '';

        for ($index = 1; $index <= 5; $index++) {
            $html .= $this->htmlDelivery($index);
        }

        return $html;
    }

    private function htmlDelivery($index)
    {
        $enabled     = $this->getConfig("delivery_enabled", $index);
        $cost        = $this->getConfig("delivery_cost", $index);
        $daysFrom    = $this->getConfig("delivery_days_from", $index);
        $daysTo      = $this->getConfig("delivery_days_to", $index);
        $orderBefore = $this->getConfig("delivery_order_before", $index);

        $htmlView = $this->htmlDeliveryView($cost, $daysFrom, $daysTo, $orderBefore);
        $htmlEdit = $this->htmlDeliveryEdit($cost, $daysFrom, $daysTo, $orderBefore);

        $checked = $enabled === 'on' ? 'checked="checked"' : '';

        $html = <<<HTML
            <div class="yandex_money_market_delivery yandex-money-market-js-editable">
                <label class="form-check-label yandex-money-market-font-weight-normal">
                    <input type="checkbox" name="yandex_money_market_delivery_enabled[{$index}]" value="on"
                        class="form-check-input" {$checked} />
                    <input type="hidden" name="yandex_money_market_delivery_cost[{$index}]" value="{$cost}" class="delivery_cost">
                    <input type="hidden" name="yandex_money_market_delivery_days_from[{$index}]" value="{$daysFrom}" class="delivery_days_from">
                    <input type="hidden" name="yandex_money_market_delivery_days_to[{$index}]" value="{$daysTo}" class="delivery_days_to">
                    <input type="hidden" name="yandex_money_market_delivery_order_before[{$index}]" value="{$orderBefore}" class="delivery_order_before">
                    {$htmlView}
                    <span class="yandex_money_market_delivery_edit_on_button yandex-money-market-edit-on-button fa fa-pencil-square-o"></span>
                </label><br>              
                {$htmlEdit}
            </div>
HTML;
        return $html;
    }

    private function htmlDeliveryView($cost, $daysFrom, $daysTo, $orderBefore)
    {
        $daysText         = $this->language->get('market_delivery_text');
        $daysLabelText    = $this->language->get('market_delivery_days_measurement_unit');
        $orderBeforeText  = $this->language->get('market_delivery_order_before');
        $defaultValueText = $this->language->get('market_delivery_default_value');

        $costValue        = (int)$cost;
        $daysValue        = empty($daysTo) || $daysFrom === $daysTo ? (int)$daysFrom : $daysFrom.'-'.$daysTo;
        $orderBeforeValue = $orderBefore ?: $useDefaultValue = $defaultValueText;

        $html = <<< HTML
            <span class="yandex_money_market_js_editable_view">
                <span class="delivery_cost">{$costValue}</span>
                {$this->defaultCurrency} 
                {$daysText} 
                <span class="delivery_days">{$daysValue}</span>
                {$daysLabelText}
                {$orderBeforeText} 
                <span class="delivery_order_before">{$orderBeforeValue}</span>
            </span>
HTML;
        return $html;
    }

    /**
     * @param string $cost
     * @param string $daysFrom
     * @param string $daysTo
     * @param int $orderBefore
     * @return string
     */
    private function htmlDeliveryEdit($cost, $daysFrom, $daysTo, $orderBefore)
    {
        $costText        = $this->language->get('market_delivery_cost');
        $daysText        = $this->language->get('market_delivery_days');
        $daysFromText    = $this->language->get('market_delivery_days_from');
        $daysToText      = $this->language->get('market_delivery_days_to');
        $orderBeforeText = $this->language->get('market_delivery_days_order_before');
        $daysLabelText   = $this->language->get('market_delivery_days_measurement_unit');
        $okText          = $this->language->get('ok');
        $cancelText      = $this->language->get('cancel');

        $orderBeforeSelect = $this->htmlDeliveryOrderBeforeSelect($orderBefore);

        $html = <<<HTML
        <div  class="yandex_money_market_delivery_edit yandex-money-market-js-editable-edit">
            <form>
                <div class="form-group">
                    <label class="col-sm-3 control-label yandex-money-market-font-weight-normal">{$costText} ({$this->defaultCurrency})</label>
                    <div class="col-sm-9">
                        <input type="number" class="form-control delivery_cost" value="{$cost}"/>
                    </div>
                </div>        
                <div class="form-group">
                    <label class="col-sm-3 control-label yandex-money-market-font-weight-normal">{$daysText}</label>
                    <div class="col-sm-9">
                        {$daysFromText}
                        <input type="number" class="form-control yandex-money-market-short-width delivery_days_from" 
                            value="{$daysFrom}" min="0" max="31" size="3"/>
                        {$daysToText}
                        <input type="number" class="form-control yandex-money-market-short-width delivery_days_to" 
                            value="{$daysTo}" min="0" max="31" size="3" />
                        {$daysLabelText}   
                    </div>
                </div>        
                <div class="form-group">
                    <label class="col-sm-3 control-label yandex-money-market-font-weight-normal">{$orderBeforeText}</label>
                    <div class="col-sm-9">
                        {$orderBeforeSelect}
                    </div>
                </div>  
                <div class="form-group">
                    <label class="col-sm-3"></label>
                    <div class="col-sm-9">
                        <input type="button" class="btn btn-default edit_finish" value="{$okText}"/>
                        <input type="reset" class="btn btn-default edit_finish" value="{$cancelText}"/>
                    </div>
                </div>  
            </form>     
        </div> 
HTML;
        return $html;
    }

    /**
     * @param int $selectedTime
     * @return string
     */
    private function htmlDeliveryOrderBeforeSelect($selectedTime)
    {
        $useDefaultValue = $this->language->get('market_delivery_use_default');

        $html = '<select class="form-control delivery_order_before">';
        for ($time = 0; $time <= 24; $time++) {
            $html .= $this->htmlDeliveryOrderBeforeOption($time, $selectedTime, $useDefaultValue);
        }
        $html .= '</select>';

        return $html;
    }

    /**
     * @param int $time
     * @param int $selectedTime
     * @param string $useDefaultValue
     * @return string
     */
    private function htmlDeliveryOrderBeforeOption($time, $selectedTime, $useDefaultValue)
    {
        $selected = $time === (int)$selectedTime ? 'selected="selected"' : '';
        $timeText = $time === 0 ? $useDefaultValue : $time.':00';

        return <<<HTML
            <option value="{$time}" {$selected}>{$timeText}</option>
HTML;
    }

    /**
     * @return string
     */
    public function htmlAvailableList()
    {
        $nonZeroCountText = $this->language->get('market_available_non_zero_count_goods');
        $ifZeroCountText  = $this->language->get('market_available_if_zero_count_goods');

        $html     = '';
        $statuses = $this->stock_status->getStockStatuses();
        $html     .= $this->htmlAvailable('non-zero-quantity', $nonZeroCountText);
        $html     .= '<div class="form-group"><div class="col-sm-12">'.$ifZeroCountText;
        foreach ($statuses as $status) {
            $html .= $this->htmlAvailable($status['stock_status_id'], $status['name']);
        }
        $html .= '</div></div>';

        return $html;
    }

    /**
     * @param $statusId
     * @param $statusName
     * @return string
     */
    public function htmlAvailable($statusId, $statusName)
    {
        $enabled   = $this->getConfig("available_enabled", $statusId);
        $available = $this->getConfig("available_available", $statusId);
        $delivery  = $this->getConfig("available_delivery", $statusId);
        $pickup    = $this->getConfig("available_pickup", $statusId);
        $store     = $this->getConfig("available_store", $statusId);

        $enabledCheckbox = $this->htmlAvailableCheckbox($statusId, 'enabled', $enabled, '');

        $htmlView = $this->htmlAvailableView($statusId, $statusName);
        $htmlEdit = $this->htmlAvailableEdit($statusId, $statusName, $available, $delivery, $pickup, $store);

        $html = <<<HTML
            <div class="yandex-money-market-js-editable yandex_money_market_available">
                {$enabledCheckbox}
                <input type="hidden" name="yandex_money_market_available_available[{$statusId}]" value="{$available}"
                    class="yandex_money_market_available_input_available">
                <input type="hidden" name="yandex_money_market_available_delivery[{$statusId}]" value="{$delivery}"
                    class="yandex_money_market_available_input_delivery">
                <input type="hidden" name="yandex_money_market_available_pickup[{$statusId}]" value="{$pickup}"
                    class="yandex_money_market_available_input_pickup">
                <input type="hidden" name="yandex_money_market_available_store[{$statusId}]" value="{$store}"
                    class="yandex_money_market_available_input_store">
                {$htmlView}
                {$htmlEdit}            
                <span class="yandex-money-market-edit-on-button yandex_money_market_available_edit_on_button fa fa-pencil-square-o"></span>
            </div>
HTML;
        return $html;
    }


    /**
     * @param $statusId
     * @param $statusName
     * @return string
     */
    private function htmlAvailableView($statusId, $statusName)
    {
        $availableProductStatusText = $statusId === 'non-zero-quantity'
            ? ''
            : $this->language->get('market_available_view_order_label');
        $availableDontUploadText    = $this->language->get('market_available_view_dont_upload');
        $availableWillUploadText    = $this->language->get('market_available_view_will_upload');
        $availableReadyText         = $this->language->get('market_available_view_ready');
        $availableToOrderText       = $this->language->get('market_available_view_to_order');
        $availableWithAvailableText = $this->language->get('market_available_view_with_available');
        $availableDeliveryText      = $this->language->get('market_available_view_delivery');
        $availablePickupText        = $this->language->get('market_available_view_pickup');
        $availableStoreText         = $this->language->get('market_available_view_store');

        $html = <<< HTML
            <span class="yandex_money_market_js_editable_view">
                {$availableProductStatusText}
                <span class="yandex-money-market-available-status">{$statusName}</span>
                <span class="available_dont_upload">{$availableDontUploadText}</span>
                <span class="available_will_upload">
                    {$availableWillUploadText}
                    <span class="yandex-money-market-available-with-ready">{$availableReadyText}</span>
                    <span class="yandex-money-market-available-with-to-order">{$availableToOrderText}</span>
                    <span class="available_list">
                        {$availableWithAvailableText}
                        <span class="yandex-money-market-available-options-list available_delivery">{$availableDeliveryText}</span>
                        <span class="yandex-money-market-available-options-list available_pickup">{$availablePickupText}</span>
                        <span class="yandex-money-market-available-options-list available_store last">{$availableStoreText}</span>
                    </span>
                </span>
            </span>
HTML;
        return $html;
    }

    /**
     * @param string $statusId
     * @param $statusName
     * @param string $available
     * @param string $delivery
     * @param string $pickup
     * @param string $store
     * @return string
     */
    private function htmlAvailableEdit($statusId, $statusName, $available, $delivery, $pickup, $store)
    {
        $cancelText = $this->language->get('cancel');
        $okText     = $this->language->get('ok');

        $deliveryLabel = $this->language->get('market_available_delivery');
        $pickupLabel   = $this->language->get('market_available_pickup');
        $storeLabel    = $this->language->get('market_available_store');

        $availableSelect  = $this->htmlAvailableSelect($statusId, $available);
        $deliveryCheckbox = $this->htmlAvailableCheckbox($statusId, 'delivery', $delivery,
            $this->language->get('market_available_delivery_description'));
        $pickupCheckbox   = $this->htmlAvailableCheckbox($statusId, 'pickup', $pickup,
            $this->language->get('market_available_pickup_description'));
        $storeCheckbox    = $this->htmlAvailableCheckbox($statusId, 'store', $store,
            $this->language->get('market_available_store_description'));

        $html = <<<HTML
        <div class="yandex_money_market_available_edit yandex-money-market-js-editable-edit">
            {$statusName}
            <form class="yandex-money-market-width-100-percent">
                <div class="form-group">
                    <label class="col-sm-3 control-label yandex-money-market-font-weight-normal"></label>
                    <div class="col-sm-9">
                        {$availableSelect}   
                    </div>
                </div>        
                <div class="form-group">
                    <label class="col-sm-3 control-label yandex-money-market-font-weight-normal yandex-money-market-first-letter-uppercase">{$deliveryLabel}</label>
                    <div class="col-sm-9">
                        {$deliveryCheckbox}
                    </div>
                </div>  
                <div class="form-group">
                    <label class="col-sm-3 control-label yandex-money-market-font-weight-normal yandex-money-market-first-letter-uppercase">{$pickupLabel}</label>
                    <div class="col-sm-9">
                        {$pickupCheckbox}
                    </div>
                </div>  
                <div class="form-group">
                    <label class="col-sm-3 control-label yandex-money-market-font-weight-normal yandex-money-market-first-letter-uppercase">{$storeLabel}</label>
                    <div class="col-sm-9">
                        {$storeCheckbox}
                    </div>
                </div>  
                <div class="form-group">
                    <label class="col-sm-3"></label>
                    <div class="col-sm-9">
                        <input type="button" class="btn btn-default edit_finish" value="{$okText}"/>
                        <input type="reset" class="btn btn-default edit_finish" value="{$cancelText}"/>
                    </div>
                </div>  
            </form>     
        </div> 
HTML;
        return $html;
    }

    /**
     * @param string $statusId
     * @param string $available
     * @return string
     */
    private function htmlAvailableSelect($statusId, $available)
    {
        $options = array(
            'none'  => $this->language->get('market_available_dont_unload'),
            'true'  => $this->language->get('market_available_ready'),
            'false' => $this->language->get('market_available_to_order'),
        );

        $html = '<select class="form-control" name="yandex_money_market_available_'.$statusId.'_available">';
        foreach ($options as $value => $text) {
            $html .= $this->htmlAvailableOption($value, $text, $available);
        }
        $html .= '</select>';

        return $html;
    }

    /**
     * @param string $value
     * @param string $text
     * @param string $available
     * @return string
     */
    private function htmlAvailableOption($value, $text, $available)
    {
        $selected = $value === $available ? 'selected="selected"' : '';

        return <<<HTML
            <option value="{$value}" {$selected}>{$text}</option>
HTML;
    }

    /**
     * @param $statusId
     * @param $field
     * @param string $value
     * @param string $text
     * @return string
     */
    private function htmlAvailableCheckbox($statusId, $field, $value, $text)
    {
        $checked = $value === 'on' ? 'checked="checked"' : '';

        return <<<HTML
            <label class="yandex-money-market-font-weight-normal yandex-money-market-with-padding-top">
                <input type="checkbox" value="on" class="form-check-input available_{$field}" {$checked}
                    name="yandex_money_market_available_{$field}[{$statusId}]" /> 
                {$text}
            </label>              
HTML;
    }

    /**
     * @return string
     */
    public function htmlOptionList()
    {
        return $this->htmlOptionItem('color')
            .$this->htmlOptionItem('size');
    }

    /**
     * @param $option
     * @return string
     */
    public function htmlOptionItem($option)
    {
        $text    = $this->language->get("market_option_{$option}_label");
        $enabled = $this->config->get("yandex_money_market_option_{$option}_enabled");
        $checked = $enabled === 'on' ? 'checked="checked"' : '';
        $select  = $this->htmlOptionSelect($option);

        $text_name    = $this->language->get("market_option_name_{$option}_label");
        $enabled_name = $this->config->get("yandex_money_market_option_name_{$option}_enabled");
        $checked_name = $enabled_name === 'on' ? 'checked="checked"' : '';

        $placeholder_prefix = $this->language->get("market_option_prefix_{$option}_label");
        $text_prefix        = $this->config->get("yandex_money_market_option_prefix_{$option}_text");

        $html = <<<HTML
            <div class="form-group">
                <label class="col-sm-4 control-label text-left yandex-money-market-font-weight-normal yandex-money-market-with-padding-top">
                    <input type="checkbox" name="yandex_money_market_option_{$option}_enabled" value="on"
                        class="form-check-input" {$checked} />
                    {$text}
                </label>
                <div class="col-sm-8">
                    {$select}
                </div>
                <div class="col-sm-12">
                    <div class="row">
                        <label class="col-sm-4 control-label yandex-money-market-font-weight-normal yandex-money-market-with-padding-top">
                            <input type="checkbox" name="yandex_money_market_option_name_{$option}_enabled" value="on"
                                class="form-check-input" {$checked_name} />
                            {$text_name}
                        </label>
                        <div class="col-sm-8">
                            <input class="form-control" type="text" name="yandex_money_market_option_prefix_{$option}_text" id="yandex_money_market_option_prefix_{$option}_text" value="{$text_prefix}" placeholder="{$placeholder_prefix}">
                        </div>
                    </div>
                </div>
            </div>
HTML;

        return $html;
    }

    /**
     * @param string $option
     * @return string
     */
    private function htmlOptionSelect($option)
    {
        $optionId = (int)$this->config->get("yandex_money_market_option_{$option}_option_id");
        $options  = array();
        foreach ($this->option->getOptions() as $oneOption) {
            $options[$oneOption['option_id']] = $oneOption['name'];
        }

        $html = '<select class="form-control" 
            name="yandex_money_market_option_'.$option.'_option_id">';
        foreach ($options as $value => $text) {
            $selected = $value === $optionId ? 'selected="selected"' : '';
            $html     .= "<option value={$value} {$selected}>{$text}</option>";
        }
        $html .= '</select>';

        return $html;
    }

    /**
     * @return string
     */
    public function htmlAdditionalConditionList()
    {
        $typeValues = (array)$this->config->get("yandex_money_market_additional_condition_type_value");
        $maxIndex   = empty($typeValues) ? 0 : max(array_keys($typeValues));

        $html = '<div class="col-sm-8 yandex_money_market_additional_condition_list">';
        for ($index = 1; $index <= $maxIndex; $index++) {
            $html .= $this->htmlAdditionalConditionItem($index);
        }
        $html .= $this->htmlAdditionalConditionItem('');

        $html .= <<<HTML
            </div>
            <div class="col-sm-8 col-sm-offset-4">
                <a onclick="return false;" data-index="{$index}" class="yandex_money_market_additional_condition_more">
                   {$this->language->get("market_additional_condition_more")}
               </a>
            </div>
HTML;

        return $html;
    }

    private function htmlAdditionalConditionItem($index)
    {
        if ($index === '') {
            $enabled     = '';
            $name        = '';
            $tag         = '';
            $typeValue   = 'static';
            $staticValue = '';
            $dataValue   = '';
            $addTemplate = 'yandex-money-market-additional-condition-template';
            $fieldName   = 'data-name';
            $forAllCat   = 'on';
            $join        = '';
            $checkedList = array();
        } else {
            $enabled     = $this->getConfig("additional_condition_enabled", $index);
            $name        = $this->getConfig("additional_condition_name", $index);
            $tag         = $this->getConfig("additional_condition_tag", $index);
            $typeValue   = $this->getConfig("additional_condition_type_value", $index);
            $staticValue = $this->getConfig("additional_condition_static_value", $index);
            $dataValue   = $this->getConfig("additional_condition_data_value", $index);
            $forAllCat   = $this->getConfig("additional_condition_for_all_cat", $index);
            $join        = $this->getConfig("additional_condition_join", $index);
            $checkedList = (array)$this->getConfig("additional_condition_categories", $index);
            $addTemplate = '';
            $fieldName   = 'name';
        }

        if (empty($typeValue)) {
            return '';
        }

        $categoriesInput = array();
        foreach ($this->getCategories() as $categoryGroup) {
            foreach ($categoryGroup as $category) {
                $checked           = in_array($category['category_id'], $checkedList) ? 'checked="checked" ' : '';
                $categoriesInput[] = '<input type="checkbox" '.$checked
                    .$fieldName.'="yandex_money_market_additional_condition_categories['.$index.'][]" 
                    value="'.$category['category_id'].'" class="additional_condition_categories yandex-money-market-hidden-element">';
            }
        }
        $htmlCategoriesInput = implode('', $categoriesInput);

        $htmlView = $this->htmlAdditionalConditionItemView($name, $tag, $typeValue, $staticValue, $dataValue,
            $forAllCat, $checkedList);
        $htmlEdit = $this->htmlAdditionalConditionItemEdit($name, $tag, $typeValue, $staticValue, $dataValue,
            $forAllCat, $checkedList, $join);

        $checked = $enabled === 'on' ? 'checked="checked"' : '';

        $html = <<<HTML
            <div class="yandex-money-market-js-editable yandex_money_market_additional_condition {$addTemplate}">
                <label class="form-check-label yandex-money-market-font-weight-normal">
                    <input type="checkbox" {$fieldName}="yandex_money_market_additional_condition_enabled[{$index}]" value="on" class="form-check-input" {$checked} />
                    <input type="hidden" {$fieldName}="yandex_money_market_additional_condition_name[{$index}]" value="{$name}" class="additional_condition_name">
                    <input type="hidden" {$fieldName}="yandex_money_market_additional_condition_tag[{$index}]" value="{$tag}" class="additional_condition_tag">
                    <input type="hidden" {$fieldName}="yandex_money_market_additional_condition_type_value[{$index}]" value="{$typeValue}" class="additional_condition_type_value">
                    <input type="hidden" {$fieldName}="yandex_money_market_additional_condition_static_value[{$index}]" value="{$staticValue}" class="additional_condition_static_value">
                    <input type="hidden" {$fieldName}="yandex_money_market_additional_condition_data_value[{$index}]" value="{$dataValue}" class="additional_condition_data_value">
                    <input type="hidden" {$fieldName}="yandex_money_market_additional_condition_for_all_cat[{$index}]" value="{$forAllCat}" class="additional_condition_for_all_cat">
                    <input type="hidden" {$fieldName}="yandex_money_market_additional_condition_join[{$index}]" value="{$join}" class="additional_condition_join">
                    {$htmlCategoriesInput}
                    {$htmlView}
                    <span class="yandex-money-market-edit-on-button fa fa-pencil-square-o
                        yandex_money_market_additional_condition_edit_on_button"></span>
                </label><br>              
                {$htmlEdit}
            </div>
HTML;
        return $html;
    }

    /**
     * @param string $name
     * @param string $tag
     * @param string $typeValue
     * @param string $staticValue
     * @param string $dataValue
     * @param string $forAllCat
     * @param array $checkedList
     * @return string
     */
    private function htmlAdditionalConditionItemView(
        $name,
        $tag,
        $typeValue,
        $staticValue,
        $dataValue,
        $forAllCat,
        array $checkedList
    ) {
        $makeTag           = $this->language->get('market_additional_condition_make_tag_label');
        $withValue         = $this->language->get('market_additional_condition_with_value_label');
        $forCategory       = $this->language->get('market_additional_condition_for_category_label');
        $forAllCategories  = $this->language->get('market_additional_condition_for_all_category_label');
        $forMoreCategories = $this->language->get('market_additional_condition_for_more_category_label');

        $value = $typeValue === 'static'
            ? $staticValue
            : $this->language->get('entry_'.$dataValue);

        if ($forAllCat === 'on') {
            $categoryList = $forAllCategories;
        } else {
            $categories = array();
            foreach ($this->getCategories() as $categoryGroup) {
                foreach ($categoryGroup as $category) {
                    if (in_array($category['category_id'], $checkedList)) {
                        $categories[] = $category['name'];
                    }
                }
            }
            $count = count($categories);
            if ($count <= self::MAX_CATEGORY_NUMBER) {
                $categoryList = implode(', ', $categories);
            } else {
                $categoryList = implode(', ', array_slice($categories, 0, self::MAX_CATEGORY_NUMBER));
                $categoryList .= ' '.sprintf($forMoreCategories, $count - self::MAX_CATEGORY_NUMBER);
            }
        }

        $html = <<< HTML
            <span class="yandex_money_market_js_editable_view">
                <span class="additional_condition_name">{$name}</span>
                {$makeTag}
                &lt;<span class="additional_condition_tag">{$tag}</span>&gt;
                {$withValue}
                <em><span class="additional_condition_value">{$value}</span></em>
                {$forCategory}
                <span class="additional_condition_category_list">{$categoryList}</span>
            </span>
HTML;
        return $html;
    }

    /**
     * @param $name
     * @param $tag
     * @param $typeValue
     * @param $staticValue
     * @param $dataValue
     * @param $forAllCat
     * @param array $checkedList
     * @param string $join
     * @return string
     */
    private function htmlAdditionalConditionItemEdit(
        $name,
        $tag,
        $typeValue,
        $staticValue,
        $dataValue,
        $forAllCat,
        array $checkedList,
        $join
    ) {
        $nameText               = $this->language->get('market_additional_condition_name_label');
        $tagText                = $this->language->get('market_additional_condition_tag_label');
        $staticValueText        = $this->language->get('market_additional_condition_static_value_label');
        $dataValueText          = $this->language->get('market_additional_condition_data_value_label');
        $categoriesText         = $this->language->get('market_additional_condition_for_categories_label');
        $allCategoriesText      = $this->language->get('market_categories_all');
        $selectedCategoriesText = $this->language->get('market_categories_selected');
        $joinLabel              = $this->language->get('market_additional_condition_join_label');
        $joinText               = $this->language->get('market_additional_condition_join_text');
        $dontJoinText           = $this->language->get('market_additional_condition_dont_join_text');
        $okText                 = $this->language->get('ok');
        $cancelText             = $this->language->get('cancel');
        $deleteText             = $this->language->get('delete');
        $hideAllText            = $this->language->get('market_sv_all');
        $showAllText            = $this->language->get('market_rv_all');
        $checkAllText           = $this->language->get('market_ch_all');
        $uncheckAllText         = $this->language->get('market_unch_all');

        $dataValueSelect = $this->htmlAdditionalConditionProductDataSelect($dataValue);

        if ($typeValue === 'static') {
            $staticValueChecked = ' checked="checked"';
            $dataValueChecked   = '';
        } else {
            $staticValueChecked = '';
            $dataValueChecked   = ' checked="checked"';
        }

        if ($forAllCat === 'on') {
            $allCategoriesChecked      = ' checked="checked"';
            $selectedCategoriesChecked = '';
            $classCategoryTree         = ' yandex-money-market-hidden-element';
        } else {
            $allCategoriesChecked      = '';
            $selectedCategoriesChecked = ' checked="checked"';
            $classCategoryTree         = '';
        }

        if ($join === 'on') {
            $joinChecked     = ' checked="checked"';
            $dontJoinChecked = '';
        } else {
            $joinChecked     = '';
            $dontJoinChecked = ' checked="checked"';
        }

        $html = <<<HTML
        <div  class="yandex-money-market-js-editable-edit yandex_money_market_additional_condition_edit">
            <form>
                <div class="form-group">
                    <label class="col-sm-4 yandex-money-market-font-weight-normal">{$nameText}</label>
                    <div class="col-sm-8">
                        <input class="form-control additional_condition_name" value="{$name}" />
                    </div>
                </div>        
                <div class="form-group">
                    <label class="col-sm-4 yandex-money-market-font-weight-normal">{$tagText}</label>
                    <div class="col-sm-8">
                        <input class="form-control additional_condition_tag" value="{$tag}"/>
                    </div>
                </div>        

                <div class="form-group">
                    <label class="col-sm-4 yandex-money-market-font-weight-normal yandex-money-market-first-letter-uppercase">
                        <input type="radio" name="additional_condition_type_value" value="static" {$staticValueChecked}/>
                        {$staticValueText}
                    </label>
                    <div class="col-sm-8">
                        <input class="form-control additional_condition_static_value" value="{$staticValue}"/>
                    </div>
                </div>        
                <div class="form-group">
                    <label class="col-sm-4 yandex-money-market-font-weight-normal yandex-money-market-first-letter-uppercase">
                        <input type="radio" name="additional_condition_type_value" value="data" {$dataValueChecked}/>
                        {$dataValueText}
                    </label>
                    <span class="col-sm-8">
                        {$dataValueSelect}
                    </span>
                </div>
                
                <div class="form-group">
                    <label class="col-sm-4 yandex-money-market-font-weight-normal">{$joinLabel}</label>
                    <div class="col-sm-8">
                        <div>
                            <label class="radio-inline">
                                <input type="radio" {$joinChecked} name="additional_condition_join" value="on"/>
                                    {$joinText}
                            </label>
                        </div>
                        <div>
                            <label class="radio-inline">
                                <input type="radio" {$dontJoinChecked} name="additional_condition_join" value=""/>
                                {$dontJoinText}
                            </label>
                        </div>
                    </div>
                </div>        

                
                <div class="form-group">
                    <label class="col-sm-4 yandex-money-market-font-weight-normal">{$categoriesText}</label>
                    <div class="col-sm-8">
                        <div>
                            <label class="radio-inline">
                                <input type="radio" {$allCategoriesChecked} name="additional_condition_for_all_cat" value="on" class="yandex_money_market_category_tree_switcher"/>
                                    {$allCategoriesText}
                            </label>
                        </div>
                        <div>
                            <label class="radio-inline">
                                <input type="radio" {$selectedCategoriesChecked} name="additional_condition_for_all_cat" value="" class="yandex_money_market_category_tree_switcher"/>
                                {$selectedCategoriesText}
                            </label>
                        </div>
                    </div>
                </div>        

                <div class="panel panel-default form-group yandex-money-market-category-tree {$classCategoryTree}">
                    <div class="tree-panel-heading tree-panel-heading-controls clearfix">
                        <div class="tree-actions pull-right">
                            <a onclick="return false;" class="btn btn-default catTreeHideCatAll">
                                <i class="fa fa-minus-square-o"></i> {$hideAllText}
                            </a>
                            <a onclick="return false;" class="btn btn-default catTreeShowCatAll">
                                <i class="fa fa-plus-square-o "></i> {$showAllText}
                            </a>
                            <a onclick="return false;" class="btn btn-default catTreeCheckCatAll">
                                <i class="fa fa-check-square-o"></i> {$checkAllText}
                            </a>
                            <a onclick="return false;" class="btn btn-default catTreeUncheckCatAll">
                                <i class="fa fa-square-o "></i> {$uncheckAllText}
                            </a>
                        </div>
                    </div>
                    <ul class="tree categoryTree">
                        {$this->treeCat($checkedList, ' class="additional_condition_categories"')}
                    </ul>
                </div>
                
                <div class="form-group">
                    <label class="col-sm-4"></label>
                    <div class="col-sm-8">
                        <input type="button" class="btn btn-default edit_finish" value="{$okText}"/>
                        <input type="reset" class="btn btn-default edit_finish" value="{$cancelText}"/>
                        <input type="reset" class="btn btn-default edit_delete" value="{$deleteText}"/>
                    </div>
                </div>  
            </form>     
        </div>
HTML;
        return $html;
    }

    /**
     * @param int $selectedField
     * @return string
     */
    private function htmlAdditionalConditionProductDataSelect($selectedField)
    {
        $html = '<select class="form-control additional_condition_data_value">';
        foreach ($this->productFields as $productField) {
            $selected = $productField === $selectedField ? 'selected="selected"' : '';
            $text     = $this->language->get('entry_'.$productField);
            $html     .= <<<HTML
            <option value="{$productField}" {$selected}>{$text}</option>
HTML;
        }
        $html .= '</select>';

        return $html;
    }

    /**
     * @param array $array
     * @param string $key
     * @param null $default
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
     * @return null
     */
    private function getConfig($key, $index = null, $default = null)
    {
        return $index === null
            ? $this->config->get("yandex_money_market_".$key)
            : $this->array_get(
                $this->config->get("yandex_money_market_".$key),
                $index,
                $default
            );
    }

}