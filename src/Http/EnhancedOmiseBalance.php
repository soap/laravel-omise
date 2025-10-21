<?php

namespace Soap\LaravelOmise\Http;

use OmiseBalance;

class EnhancedOmiseBalance extends OmiseBalance
{
    protected static $httpClient;

    public static function setHttpClient(OmiseHttpClient $httpClient): void
    {
        self::$httpClient = $httpClient;
    }

    protected static function request($method, $url, $key, $data = null)
    {
        if (self::$httpClient) {
            return EnhancedOmiseApi::requestWithCustomClient($method, $url, $key, $data);
        }

        return parent::request($method, $url, $key, $data);
    }
}
