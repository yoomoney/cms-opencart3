<?php

namespace YooMoneyModule\Model;

/**
 * @author ElisDN <mail@elisdn.ru>
 * @link https://elisdn.ru
 * @author YooMoney <cms@yoomoney.ru>
 * @link https://yoomoney.ru
 */
class CBRAgent
{
    protected $list = array();

    /**
     * CBRAgent constructor.
     * @param string|null $date
     */
    public function __construct($date=null)
    {
        $this->load($date);
    }

    /**
     * @param string|null $date
     * @return bool
     */
    public function load($date=null)
    {
        $xml = new \DOMDocument();
        $url = 'https://www.cbr.ru/scripts/XML_daily.asp?date_req=' . ($date ?: date('d.m.Y'));

        if (@$xml->load($url))
        {
            $this->list = array();

            $root = $xml->documentElement;
            $items = $root->getElementsByTagName('Valute');

            foreach ($items as $item) /** @var \DOMElement $item */
            {
                $code = $item->getElementsByTagName('CharCode')->item(0)->nodeValue;
                $curs = $item->getElementsByTagName('Value')->item(0)->nodeValue;
                $this->list[$code] = floatval(str_replace(',', '.', $curs));
            }

            return true;
        }
        else
            return false;
    }

    public function getList()
    {
        return $this->list;
    }

    /**
     * @param $cur
     * @return int|mixed
     */
    public function getCurrency($cur)
    {
        return isset($this->list[$cur]) ? $this->list[$cur] : 0;
    }
}
