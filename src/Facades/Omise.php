<?php

namespace Soap\LaravelOmise\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Soap\LaravelOmise\LaravelOmise
 */
class Omise extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'omise';
    }
}
