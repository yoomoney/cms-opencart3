<?php

namespace YandexMoneyModule\YandexMarket;

/**
 * Class Offer
 *
 * @property-read int $categoryId
 *
 */
class Offer extends MarketObject
{
    /**
     * @var ShopInfo
     */
    private $shop;

    /**
     * @var string Идентификатор товарного предложения. Может содержать только цифры и латинские буквы.
     * Максимальная длина — 20 символов.
     */
    private $id;

    /**
     * В элементе available указывается статус товара — «готов к отправке» или «на заказ». От статуса зависит показ
     * условий доставки и самовывоза на Яндекс.Маркете.
     *
     * <note>Внимание.</note>
     * Элемент available используется в дополнение к данным, настроенным в личном кабинете:
     * <ul>
     * <li>когда условия локальной курьерской доставки настроены в личном кабинете;</li>
     * <li>в дополнение к условиям курьерской доставки в другие регионы;</li>
     * <li>в дополнение к самовывозу.</li>
     * </ul>
     *
     * Элемент delivery-options, local_delivery_days.
     *
     * Возможные значения:
     * <ul>
     * <li>true — товар готов к отправке, будет доставлен курьером или в пункт выдачи в указанные сроки.
     *            На Яндекс.Маркете показываются сроки, настроенные в личном кабинете.</li>
     * <li>false — товар на заказ, точный срок доставки курьером или в пункт выдачи неизвестен.
     *             Срок будет согласован с покупателем персонально (максимальный срок — два месяца).
     *             На Яндекс.Маркете сроки не показываются, показывается надпись «на заказ».</li>
     * </ul>
     *
     * Элемент является необязательным. Если элемент не указан, используется значение по умолчанию — true.
     *
     * @var bool Статус товара — «готов к отправке» или «на заказ»
     */
    private $available;
    private $clickRate;
    private $otherClickRate;
    private $redirectFee;

    /**
     * @var string URL страницы товара на сайте магазина
     */
    private $url;

    /**
     * @var double Цена, по которой данный товар можно приобрести
     */
    private $price;

    /**
     * @var double Старая цена товара
     */
    private $oldPrice;

    /**
     * @var string Код валюты
     */
    private $currencyId;

    /**
     * @var ProductCategory Категория которой принадлежит товар
     */
    private $category;

    /**
     * @var string[] URL изображений товара
     */
    private $pictures;

    /**
     * @var bool Возможность курьерской доставки соответствующего товара
     */
    private $delivery;
    private $deliveryOptions;
    private $pickup;
    private $store;
    private $outlets;
    private $description;
    private $salesNotes;
    private $minQuantity;
    private $stepQuantity;
    private $manufacturerWarranty;
    private $countryOfOrigin;
    private $adult;
    private $age;
    private $barcode;
    private $cpa;
    private $name;
    private $vendor;
    private $vendorCode;
    private $model;

    /**
     * @var ParameterList[]
     */
    private $parameters;
    private $expiry;
    private $weight;
    private $dimensions;
    private $downloadable;
    private $groupId;
    private $rec;
    private $vat;
    private $customTags;
    private $customTagsJoin;

    public function __construct(ShopInfo $shop, $offerId, ProductCategory $category)
    {
        $this->shop       = $shop;
        $this->id         = $offerId;
        $this->category   = $category;
        $this->available  = true;
        $this->parameters = array();
        $this->outlets    = array();
        $this->pictures   = array();
        $this->customTags = array();
    }

    public function __clone()
    {
        $parameters = array();
        foreach ($this->parameters as $name => $parameter) {
            $parameters[$name] = clone $parameter;
        }
        $this->parameters = $parameters;
    }

    /**
     * Возвращает идентификатор товарного предложения
     *
     * Может содержать только цифры и латинские буквы. Максимальная длина — 20 символов.
     *
     * @return string Идентификатор товарного предложения
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * Устанавливает идентификатор товарного предложения
     *
     * @param string $value Идентификатор товарного предложения
     * @return Offer Инстанс текущего объекта
     */
    public function setId($value)
    {
        $this->id = $value;
        return $this;
    }

    /**
     * Устанавливает название товарного предложения
     *
     * @param string $value Название товарного предложения
     * @return Offer Инстанс текущего объекта
     */
    public function setName($value)
    {
        $this->name = $value;
        return $this;
    }

    /**
     * Возвращает название товарного предложения
     *
     * @return string Название товарного предложения
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function hasName()
    {
        return $this->name !== null;
    }

    /**
     * Устанавливает статус товара — «готов к отправке» или «на заказ»
     *
     * @param bool $value True если товар готов к отправке, false ели доступен только под заказ
     * @return Offer Инстанс текущего объекта
     */
    public function setAvailable($value)
    {
        if ($value !== null) {
            $this->available = $value ? true : false;
        } else {
            $this->available = null;
        }
        return $this;
    }

    /**
     * Проверяет статус заказа
     *
     * @return bool True если товар готов к отправке, false ели доступен только под заказ
     */
    public function isAvailable()
    {
        return $this->available;
    }

    public function setClickRate($value)
    {
        $this->clickRate = (int)round($value * 100);
        return $this;
    }

    public function getClickRate()
    {
        return $this->clickRate / 100.0;
    }

    public function getCbid()
    {
        return $this->clickRate;
    }

    public function setOtherClickRate($value)
    {
        $this->otherClickRate = (int)round($value * 100);
        return $this;
    }

    public function getOtherClickRate()
    {
        return $this->otherClickRate / 100.0;
    }

    public function getBid()
    {
        return $this->otherClickRate;
    }

    public function setRedirectFee($value)
    {
        $this->redirectFee = (int)round($value * 10000);
        return $this;
    }

    public function getRedirectFee()
    {
        return $this->redirectFee / 10000.0;
    }

    public function getFee()
    {
        return $this->redirectFee;
    }

    /**
     * Устанавливает URL страницы товара на сайте магазина
     *
     * Максимальная длина ссылки — 512 символов. Допускаются кириллические ссылки.
     * Если магазин размещается по обеим моделям сразу:
     * <ul>
     * <li>передавайте url, чтобы был доступен переход с предложения на сайт;</li>
     * <li>не передавайте url, чтобы переход с предложения на сайт был недоступен.</li>
     * </ul>
     *
     * @param string $value URL страницы товара на сайте магазина
     * @return Offer Инстанс текущего объекта
     */
    public function setUrl($value)
    {
        $this->url = mb_substr($value, 0, 512, 'utf-8');
        return $this;
    }

    /**
     * Возвращает URL страницы товара на сайте магазина
     *
     * @return string URL страницы товара на сайте магазина
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Проверяет был ли установлен URL товара
     * @return bool True если URL товара установлен, false если нет
     */
    public function hasUrl()
    {
        return !empty($this->url);
    }

    /**
     * Цена, по которой данный товар можно приобрести
     *
     * Цена товарного предложения округляется, формат, в котором она отображается, зависит от настроек пользователя.
     * Для следующих категорий, при условии, что прайс-лист передается в формате YML, допускается указывать начальную
     * цену «от» с помощью атрибута from="true":
     * <ul>
     * <li>«Банкетки и скамьи»; «Ванные комнаты»; «Гостиные»;</li>
     * <li>«Детские»; «Детские комоды»; «Диваны»; «Кабинеты»;</li>
     * <li>«Колыбели и люльки»; «Комоды»; «Компьютерные столы»;</li>
     * <li>«Кресла»; «Кровати»; «Кухонные гарнитуры»;</li>
     * <li>«Кухонные уголки и обеденные группы»;</li>
     * <li>«Манежи»; «Парты и стулья»; «Полки»; «Прихожие»;</li>
     * <li>«Пуфики»; «Спальни»; «Стеллажи»; «Столы и столики»;</li>
     * <li>«Стулья, табуретки»; «Тумбы»; «Шкафы».</li>
     * </ul>
     * <note>Пример: &lt;price from="true"&gt;2000&lt;/price&gt;</note>
     *
     * @param double $value Цена товара
     * @return Offer Инстанс текущего объекта
     */
    public function setPrice($value)
    {
        $this->price = round($value, 2);
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function incPrice($value)
    {
        $this->price += round($value, 2);
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function decPrice($value)
    {
        $this->price -= round($value, 2);
        return $this;
    }

    /**
     * Возвращает цену товара
     *
     * @return double Цена товара
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Старая цена на товар, которая обязательно должна быть выше новой цены (price).
     *
     * Параметр oldprice необходим для автоматического расчета скидки на товар.
     * <note>Примечание. Скидка обновляется на Маркете каждые 40–80 минут.</note>
     *
     * @param double $value Старая цена товара
     * @return Offer Инстанс текущего объекта
     */
    public function setOldPrice($value)
    {
        $this->oldPrice = round($value, 2);
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function incOldPrice($value)
    {
        $this->oldPrice += round($value, 2);
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function decOldPrice($value)
    {
        $this->oldPrice -= round($value, 2);
        return $this;
    }

    /**
     * Возвращает старую цену товара
     *
     * @return double Старая цена товара
     */
    public function getOldPrice()
    {
        return $this->oldPrice;
    }

    /**
     * Проверяет была ли установлена старая цена товара
     *
     * @return bool True если старая цена была установлена, false если нет
     */
    public function hasOldPrice()
    {
        return !empty($this->oldPrice);
    }

    /**
     * Устанавливает код валюты, в которой указана цена товара
     *
     * Для корректного отображения цены в национальной валюте необходимо использовать идентификатор с соответствующим
     * значением цены (например, UAH с ценой в гривнах).
     *
     * @param string $value Код валюты
     * @return Offer Инстанс текущего объекта
     */
    public function setCurrencyId($value)
    {
        $this->currencyId = $value;
        return $this;
    }

    /**
     * Возвращает код валюты, если он не был указан, то возвращается код валюты всего прайс листа
     *
     * @return string Код валюты
     */
    public function getCurrencyId()
    {
        if (empty($this->currencyId)) {
            return $this->shop->getDefaultCurrencyId();
        }
        return $this->currencyId;
    }

    /**
     * Возвращает инстанс категории товара
     *
     * @return ProductCategory Категория товара
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Возвращает идентификатор категории товара, присвоенный магазином (целое число не более 18 знаков).
     *
     * Товарное предложение может принадлежать только одной категории.
     *
     * @return int Идентификатор категории товара
     */
    public function getCategoryId()
    {
        return $this->category->getId();
    }

    /**
     * Устанавливает URL-ссылку на картинку товара
     * @param string $value URL-ссылка на картинку товара
     * @return Offer Инстанс текущего объекта
     */
    public function addPicture($value)
    {
        $value            = str_replace(array('&amp;', ' '), array('&', '%20'), $value);
        $this->pictures[] = mb_substr(trim($value), 0, 512, 'utf-8');
        return $this;
    }

    /**
     * Возвращает URL-ссылку на картинку товара
     *
     * @return string[] URL-ссылка на картинку товара
     */
    public function getPictures()
    {
        return $this->pictures;
    }

    /**
     * Проверяет наличие ссылки на изображение товара
     *
     * @return bool True если картинка была установлена, false если нет
     */
    public function hasPictures()
    {
        return !empty($this->pictures);
    }

    /**
     * Устанавливает возможность курьерской доставки соответствующего товара
     *
     * Возможные значения:
     * <ul>
     * <li>true — товар может быть доставлен курьером.</li>
     * <li>false — товар не может быть доставлен курьером (только самовывоз);</li>
     * </ul>
     * <note>Внимание. Элемент delivery должен обязательно иметь значение false, если товар запрещено продавать
     * дистанционно (ювелирные изделия, лекарственные средства).</note>
     *
     * Если элемент не указан, то принимается значение по умолчанию
     *
     * @param bool $value True если товар может быть доставлен курьером, false если нет
     * @return Offer Инстанс текущего объекта
     */
    public function setDelivery($value)
    {
        $this->delivery = $value ? true : false;
        return $this;
    }

    /**
     * Возвращает возможность доставки товара курьером
     *
     * @return bool True если товар может быть доставлен курьером, false если нет
     */
    public function getDelivery()
    {
        return $this->delivery;
    }

    /**
     * Проверяет была ли установлена возможность доставки курьером
     * @return bool True если значение было установлено, false если нет
     */
    public function hasDelivery()
    {
        return $this->delivery !== null;
    }

    /**
     * @param int $cost
     * @param int|string $days
     * @param null|int $orderBefore
     * @return DeliveryOption
     */
    public function addDeliveryOption($cost, $days = 1, $orderBefore = null)
    {
        $option = new DeliveryOption($cost, $days);
        if (!empty($orderBefore)) {
            $option->setOrderBefore($orderBefore);
        }
        $this->deliveryOptions[] = $option;
        return $option;
    }

    /**
     * @return DeliveryOption[]
     */
    public function getDeliveryOptions()
    {
        return $this->deliveryOptions;
    }

    /**
     * @return bool
     */
    public function hasDeliveryOptions()
    {
        return !empty($this->deliveryOptions);
    }

    /**
     * @return bool
     */
    public function getPickup()
    {
        return $this->pickup;
    }

    /**
     * @param bool|null $value
     * @return Offer
     */
    public function setPickup($value)
    {
        if ($value !== null) {
            $this->pickup = $value ? true : false;
        } else {
            $this->pickup = null;
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function hasPickup()
    {
        return $this->pickup !== null;
    }

    /**
     * @return bool
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * @param bool|null $value
     * @return Offer
     */
    public function setStore($value)
    {
        if ($value !== null) {
            $this->store = $value ? true : false;
        } else {
            $this->store = null;
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function hasStore()
    {
        return $this->store !== null;
    }

    /**
     * @return integer[]
     */
    public function getOutlets()
    {
        return $this->outlets;
    }

    /**
     * @param string $outletId
     * @param int $inStock
     * @return Offer
     */
    public function addOutlets($outletId, $inStock)
    {
        $this->outlets[$outletId] = $inStock;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasOutlets()
    {
        return !empty($this->outlets);
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $value
     * @return Offer
     */
    public function setDescription($value)
    {
        $this->description = mb_substr(trim($value), 0, 3000, 'utf-8');
        return $this;
    }

    /**
     * @return bool
     */
    public function hasDescription()
    {
        return !empty($this->description);
    }

    /**
     * @return string
     */
    public function getSalesNotes()
    {
        return $this->salesNotes;
    }

    /**
     * @param string $value
     * @return Offer
     */
    public function setSalesNotes($value)
    {
        $this->salesNotes = mb_substr(trim($value), 0, 50, 'utf-8');
        return $this;
    }

    /**
     * @return bool
     */
    public function hasSalesNotes()
    {
        return !empty($this->salesNotes);
    }

    /**
     * @return int
     */
    public function getMinQuantity()
    {
        return $this->minQuantity;
    }

    /**
     * @param int $value
     * @return Offer
     */
    public function setMinQuantity($value)
    {
        $this->minQuantity = $value;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasMinQuantity()
    {
        return $this->minQuantity !== null && $this->minQuantity > 0;
    }

    /**
     * @return int
     */
    public function getStepQuantity()
    {
        return $this->stepQuantity;
    }

    /**
     * @param mixed $value
     * @return Offer
     */
    public function setStepQuantity($value)
    {
        $this->stepQuantity = $value;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasStepQuantity()
    {
        return $this->stepQuantity !== null && $this->stepQuantity > 0;
    }

    /**
     * @return bool
     */
    public function getManufacturerWarranty()
    {
        return $this->manufacturerWarranty;
    }

    /**
     * @param bool|null $value
     * @return Offer
     */
    public function setManufacturerWarranty($value)
    {
        if ($value !== null) {
            $this->manufacturerWarranty = $value ? true : false;
        } else {
            $this->manufacturerWarranty = null;
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function hasManufacturerWarranty()
    {
        return $this->manufacturerWarranty !== null;
    }

    /**
     * @return string
     */
    public function getCountryOfOrigin()
    {
        return $this->countryOfOrigin;
    }

    /**
     * @param string $value
     * @return Offer
     */
    public function setCountryOfOrigin($value)
    {
        $this->countryOfOrigin = trim($value);
        return $this;
    }

    /**
     * @return bool
     */
    public function hasCountryOfOrigin()
    {
        return !empty($this->countryOfOrigin);
    }

    /**
     * @return bool
     */
    public function getAdult()
    {
        return $this->adult;
    }

    /**
     * @param bool|null $value
     * @return Offer
     */
    public function setAdult($value)
    {
        if ($value !== null) {
            $this->adult = $value ? true : false;
        } else {
            $this->adult = null;
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function hasAdult()
    {
        return $this->adult !== null;
    }

    /**
     * @return mixed
     */
    public function getAge()
    {
        return $this->age;
    }

    /**
     * @param mixed $age
     */
    public function setAge($age)
    {
        $this->age = $age;
    }

    /**
     * @return mixed
     */
    public function getBarcode()
    {
        return $this->barcode;
    }

    /**
     * @param mixed $barcode
     */
    public function setBarcode($barcode)
    {
        $this->barcode = $barcode;
    }

    /**
     * @return mixed
     */
    public function getCpa()
    {
        return $this->cpa;
    }

    /**
     * @param mixed $cpa
     */
    public function setCpa($cpa)
    {
        $this->cpa = $cpa;
    }

    /**
     * @return ParameterList[]
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param string $name
     * @param string $value
     * @param string|null $unit
     * @return Offer
     */
    public function addParameter($name, $value, $unit = null)
    {
        if (!array_key_exists($name, $this->parameters)) {
            $this->parameters[$name] = new ParameterList($name, $value, $unit);
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function hasParameters()
    {
        return !empty($this->parameters);
    }

    /**
     * @return mixed
     */
    public function getExpiry()
    {
        return $this->expiry;
    }

    /**
     * @param mixed $expiry
     */
    public function setExpiry($expiry)
    {
        $this->expiry = $expiry;
    }

    /**
     * Возвращает вес товара
     * @return double Вес товара в килограммах
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Устанавливает вес товара
     * @param double $weight Вес товара
     * @param string $unit Еденица измерения веса
     * @return Offer Инстанс текущего объекта
     */
    public function setWeight($weight, $unit = 'kg')
    {
        $unit       = strtolower($unit);
        $multiplier = 1.0;
        if ($unit !== 'kg' && $unit !== 'кг') {
            $multipliers = array(
                'g'   => 0.001,
                'г'   => 0.001,
                't'   => 1000.0,
                'т'   => 1000.0,
                'ct'  => 0.0002,
                'кар' => 0.0002,
                'lb'  => 0.45359237,
                'фт'  => 0.45359237,
                'oz'  => 0.028349523,
                'унц' => 0.028349523,
            );
            if (array_key_exists($unit, $multipliers)) {
                $multiplier = $multipliers[$unit];
            }
        }
        $this->weight = round($weight * $multiplier, 3);
        return $this;
    }

    /**
     * Проверяет был ли установлен вес товара
     * @return bool True если вес товара был установлен false если нет
     */
    public function hasWeight()
    {
        return !empty($this->weight);
    }

    /**
     * Возвращает размеры товара в виде строки
     * @return string Размеры товара в формате w/h/d
     */
    public function getDimensions()
    {
        return implode('/', $this->dimensions);
    }

    /**
     * Устанавливает размеры
     * @param double $length Длина
     * @param double $width Ширина
     * @param double $height Высота
     * @param string $unit Еденица измерения размеров
     * @return Offer Инстанс текущего объекта
     */
    public function setDimensions($length, $width, $height, $unit = 'cm')
    {
        $unit       = strtolower($unit);
        $multiplier = 1.0;
        if ($unit !== 'cm' && $unit !== 'см') {
            $multipliers = array(
                'mm'   => 0.1,
                'мм'   => 0.1,
                'm'    => 100.0,
                'м'    => 100.0,
                'in'   => 2.54,
                'д'    => 2.54,
                'ft'   => 30.48,
                'фут'  => 30.48,
                'yard' => 91.44,
                'ярд'  => 91.44,
            );
            if (array_key_exists($unit, $multipliers)) {
                $multiplier = $multipliers[$unit];
            }
        }
        $this->dimensions = array(
            round($length * $multiplier, 3),
            round($width * $multiplier, 3),
            round($height * $multiplier, 3),
        );
        return $this;
    }

    /**
     * @return bool
     */
    public function hasDimensions()
    {
        return !empty($this->dimensions);
    }

    /**
     * @return bool
     */
    public function getDownloadable()
    {
        return $this->downloadable;
    }

    /**
     * @param bool $value
     * @return Offer
     */
    public function setDownloadable($value)
    {
        $this->downloadable = $value ? true : false;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * @param mixed $groupId
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;
    }

    /**
     * @return mixed
     */
    public function getRec()
    {
        return $this->rec;
    }

    /**
     * @param mixed $rec
     */
    public function setRec($rec)
    {
        $this->rec = $rec;
    }

    /**
     * @return string
     */
    public function getVendor()
    {
        return $this->vendor;
    }

    /**
     * @param $value
     * @return Offer
     */
    public function setVendor($value)
    {
        $this->vendor = $value;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasVendor()
    {
        return !empty($this->vendor);
    }

    /**
     * @return string
     */
    public function getVendorCode()
    {
        return $this->vendorCode;
    }

    /**
     * @param $value
     * @return Offer
     */
    public function setVendorCode($value)
    {
        $this->vendorCode = $value;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasVendorCode()
    {
        return !empty($this->vendorCode);
    }

    /**
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param $value
     * @return Offer
     */
    public function setModel($value)
    {
        $this->model = $value;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasModel()
    {
        return !empty($this->model);
    }

    /**
     * @return string
     */
    public function getVat()
    {
        return $this->vat;
    }

    /**
     * @param string $vat
     */
    public function setVat($vat)
    {
        $this->vat = $vat;
    }

    /**
     * @return bool
     */
    public function hasVat()
    {
        return !is_null($this->vat);
    }

    /**
     * @return string[][]
     */
    public function getCustomTags()
    {
        if ($this->customTagsJoin) {
            $customTags = array();
            foreach ($this->customTags as $tag => $values) {
                $customTags[$tag] = array(join(' ', $values));
            }
            return $customTags;
        }

        return $this->customTags;
    }

    /**
     * @param string $tag
     * @param string $value
     * @param bool $join
     * @return Offer
     */
    public function addCustomTag($tag, $value, $join)
    {
        if (!isset($this->customTags[$tag]) || !in_array($value, $this->customTags[$tag])) {
            $this->customTags[$tag][] = $value;
        }
        $this->customTagsJoin = $this->customTagsJoin || $join;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasCustomTags()
    {
        return !empty($this->customTags);
    }


}
