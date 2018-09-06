<?php

namespace YandexMoneyModule\YandexMarket;

class YandexMarket
{
    /**
     * @var ShopInfo
     */
    private $shopInfo;

    /**
     * YandexMarket constructor.
     */
    public function __construct()
    {
        $this->shopInfo = new ShopInfo();
    }

    /**
     * @return ShopInfo
     */
    public function getShop()
    {
        return $this->shopInfo;
    }

    /**
     * @param string $name
     * @param string $company
     * @param string $url
     * @return self
     */
    public function setShop($name, $company, $url)
    {
        $this->shopInfo->setName($name)
            ->setCompany($company)
            ->setUrl($url);
        return $this;
    }

    /**
     * @param string $id
     * @param string $rate
     * @param int|float|null $plus
     * @return self
     */
    public function addCurrency($id, $rate = 'CBRF', $plus = null)
    {
        $rate = strtoupper($rate);
        $plus = str_replace(',', '.', $plus);
        $this->shopInfo->addCurrency(new Currency($id, $rate, $plus));
        return $this;
    }

    /**
     * @param string $name
     * @param int $id
     * @param int $parent_id
     * @return self
     */
    function addCategory($name, $id, $parent_id = -1)
    {
        if ((int)$id >= 1 && trim($name) != '') {
            $this->shopInfo->addCategory($id, $parent_id, $name);
        }
        return $this;
    }

    /**
 * @param $id
 * @param $categoryId
 * @return Offer
 */
    public function createOffer($id, $categoryId)
    {
        $offer = $this->shopInfo->createOffer($id, $categoryId);
        return $offer;
    }

    /**
     * @param Offer $offer
     * @return void
     */
    public function addOffer(Offer $offer)
    {
        $this->shopInfo->addOffer($offer);
    }

    /**
     * @param bool $isSimpleYml
     * @return string
     * @throws \Exception
     */
    public function getXml($isSimpleYml = false)
    {
        if ($isSimpleYml) {
            $builder = new SimpleYmlBuilder();
        } else {
            $builder = new ArbitraryYmlBuilder();
        }
        return $builder->generate($this->shopInfo);
    }
}