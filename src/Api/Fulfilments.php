<?php

namespace Booni3\DynamicWms\Api;

class Fulfilments extends ApiClient
{
    public function getFulfilments($page = 1)
    {
        return $this->get("fulfilments?page=$page");
    }
}