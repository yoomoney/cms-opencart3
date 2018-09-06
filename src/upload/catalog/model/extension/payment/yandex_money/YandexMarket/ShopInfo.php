<?php

namespace YandexMoneyModule\YandexMarket;

class ShopInfo
{
    /**
     * Короткое название магазина, должно содержать не более 20 символов.
     *
     * В названии нельзя использовать слова, не имеющие отношения к наименованию магазина, например «лучший»,
     * «дешевый», указывать номер телефона и т. п.
     * Название магазина должно совпадать с фактическим названием магазина, которое публикуется на сайте. При
     * несоблюдении данного требования наименование может быть изменено Яндекс.Маркетом самостоятельно без
     * уведомления магазина.
     *
     * @var string Короткое название магазина
     *
     * @required
     */
    private $name;

    /**
     * Полное наименование компании, владеющей магазином.
     *
     * Не публикуется, используется для внутренней идентификации.
     *
     * @var string Наименование компании, владеющей магазином
     *
     * @required
     */
    private $company;

    /**
     * URL главной страницы магазина.
     *
     * Максимальная длина — 50 символов. Допускаются кириллические ссылки.
     * Элемент обязателен при размещении по модели «Переход на сайт».
     *
     * @var string URL главной страницы магазина
     */
    private $url;

    /**
     * @var string Система управления контентом, на основе которой работает магазин (CMS).
     */
    private $platform;

    /**
     * @var string Версия CMS.
     */
    private $version;

    /**
     * Наименование агентства, которое оказывает техническую поддержку магазину и отвечает за работоспособность сайта.
     *
     * @var string Наименование агентства
     */
    private $agency;

    /**
     * @var string Контактный адрес разработчиков CMS или агентства, осуществляющего техподдержку.
     */
    private $email;

    /**
     * @var Currency[] Список курсов валют магазина.
     *
     * @required
     */
    private $currencies = array();

    /**
     * @var array Список категорий магазина.
     *
     * @required
     */
    private $categories = array();

    /**
     * Стоимость и сроки курьерской доставки по своему региону.
     *
     * Обязательный элемент, если все данные по доставке передаются в прайс-листе.
     *
     * @var array Стоимость и сроки курьерской доставки по своему региону
     */
    private $deliveryOptions;

    /**
     * @var array Элемент управляет участием товарных предложений в программе «Заказ на Маркете».
     */
    private $cpa;

    /**
     * Список предложений магазина.
     *
     * Каждое предложение описывается в отдельном элементе offer. Здесь не приводится список всех элементов, входящих
     * в offer, так как он зависит от типа предложения. Для большинства категорий товаров подходят следующие типы
     * описаний:
     * <ul>
     * <li>{@link https://yandex.ru/support/partnermarket/offers.html Упрощенный тип описания}</li>
     * <li>{@link https://yandex.ru/support/partnermarket/export/vendor-model.html Произвольный тип описания}</li>
     * </ul>
     *
     * Для некоторых категорий товаров нужно использовать собственные типы описаний:
     * <ul>
     * <li>{@link https://yandex.ru/support/partnermarket/export/medicine.html Лекарства}</li>
     * <li>{@link https://yandex.ru/support/partnermarket/export/books.html Книги}</li>
     * <li>{@link https://yandex.ru/support/partnermarket/export/audiobooks.html Аудиокниги}</li>
     * <li>{@link https://yandex.ru/support/partnermarket/export/music-video.html Музыкальная и видеопродукция}</li>
     * <li>{@link https://yandex.ru/support/partnermarket/export/event-tickets.html Билеты на мероприятия}</li>
     * <li>{@link https://yandex.ru/support/partnermarket/export/tours.html Туры}</li>
     * </ul>
     *
     * @var array Список предложений магазина
     *
     * @required
     */
    private $offers = array();

    /**
     * @var CategoryTreeBuilder
     */
    private $treeBuilder;

    public function __construct()
    {
        $this->treeBuilder = new CategoryTreeBuilder();
    }

    /**
     * Возвращает короткое название магазина
     *
     * @return string Короткое название магазина
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Устанавливает короткое название магазина
     *
     * @param string $value Короткое название магазина
     * @return ShopInfo
     */
    public function setName($value)
    {
        $this->name = mb_substr(trim($value), 0, 20, 'utf-8');
        return $this;
    }

    /**
     * Возвращает полное наименование компании, владеющей магазином.
     *
     * @return string Наименование компании
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * Устанавливает полное наименование компании, владеющей магазином.
     *
     * @param string $value Наименование компании
     * @return ShopInfo
     */
    public function setCompany($value)
    {
        $this->company = mb_substr(trim($value), 0, 128, 'utf-8');
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $value
     * @return ShopInfo
     */
    public function setUrl($value)
    {
        $this->url = mb_substr(trim($value), 0, 50, 'utf-8');
        return $this;
    }

    /**
     * @return bool
     */
    public function hasUrl()
    {
        return !empty($this->url);
    }

    /**
     * @return string
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * @param string $value
     * @return ShopInfo
     */
    public function setPlatform($value)
    {
        $this->platform = mb_substr(trim($value), 0, 128, 'utf-8');
        return $this;
    }

    /**
     * @return bool
     */
    public function hasPlatform()
    {
        return !empty($this->platform);
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param string $value
     * @return ShopInfo
     */
    public function setVersion($value)
    {
        $this->version = mb_substr(trim($value), 0, 32, 'utf-8');
        return $this;
    }

    public function hasVersion()
    {
        return !empty($this->version);
    }

    /**
     * @return string
     */
    public function getAgency()
    {
        return $this->agency;
    }

    /**
     * @param string $value
     * @return ShopInfo
     */
    public function setAgency($value)
    {
        $this->agency = mb_substr(trim($value), 0, 128, 'utf-8');
        return $this;
    }

    /**
     * @return bool
     */
    public function hasAgency()
    {
        return !empty($this->agency);
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $value
     * @return ShopInfo
     */
    public function setEmail($value)
    {
        $this->email = mb_substr(trim($value), 0, 256, 'utf-8');
        return $this;
    }

    /**
     * @return bool
     */
    public function hasEmail()
    {
        return !empty($this->email);
    }

    /**
     * @return Currency[]
     */
    public function getCurrencies()
    {
        return $this->currencies;
    }

    /**
     * @return string
     */
    public function getDefaultCurrencyId()
    {
        $first = null;
        foreach ($this->currencies as $currency) {
            if ($first === null) {
                $first = $currency->getId();
            }
            if ($currency->getRate() == 1) {
                return $currency->getId();
            }
        }
        return $first;
    }

    /**
     * @param Currency $currency
     * @return ShopInfo
     */
    public function addCurrency(Currency $currency)
    {
        $this->currencies[] = $currency;
        return $this;
    }

    /**
     * @return ProductCategory[]
     * @throws \Exception
     */
    public function getCategories()
    {
        $this->treeBuilder->build();
        return $this->categories;
    }

    /**
     * @param int $id
     * @param int $parentId
     * @param string $name
     * @return ProductCategory
     */
    public function addCategory($id, $parentId, $name)
    {
        $category = $this->treeBuilder->addCategory($id, $parentId, $name);
        $this->categories[$category->getId()] = $category;
        return $category;
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
     * @return array
     */
    public function getCpa()
    {
        return $this->cpa;
    }

    /**
     * @param array $cpa
     */
    public function setCpa($cpa)
    {
        $this->cpa = $cpa;
    }

    /**
     * @return Offer[]
     */
    public function getOffers()
    {
        return $this->offers;
    }

    /**
     * @param int $productId
     * @param int $categoryId
     * @return Offer|null
     */
    public function createOffer($productId, $categoryId)
    {
        if (!array_key_exists($categoryId, $this->categories)) {
            return null;
        }
        $category = $this->categories[$categoryId];
        return new Offer($this, $productId, $category);
    }

    /**
     * @param Offer $offer
     */
    public function addOffer(Offer $offer)
    {
        $this->offers[$offer->getId()] = $offer;
    }
}
