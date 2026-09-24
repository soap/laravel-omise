<?php

namespace Soap\LaravelOmise\Facades;

use Illuminate\Support\Facades\Facade;
use Soap\LaravelOmise\LaravelOmise;

/**
 * @see LaravelOmise
 */
class Omise extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'omise';
    }
}
