<?php

namespace YandexMoneyModule\YandexMarket;

class ArbitraryYmlBuilder extends YmlBuilder
{
    protected $offerType = ' type="vendor.model"';

    protected function generateOffer(Offer $offer)
    {
        if (!$offer->hasModel() || !$offer->hasVendor()) {
            return '';
        }

        return parent::generateOffer($offer);
    }
}
