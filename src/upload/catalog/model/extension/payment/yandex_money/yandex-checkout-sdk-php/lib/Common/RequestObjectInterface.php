<?php

namespace YandexCheckout\Common;

interface RequestObjectInterface
{
    public function toJson();
    public function toArray();
}