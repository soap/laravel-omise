<?php

namespace Soap\LaravelOmise\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Soap\LaravelOmise\Omise withKeys(string $publicKey, string $secretKey)
 * @method static bool validConfig()
 * @method static array configErrors()
 * @method static bool liveMode()
 * @method static string getPublicKey()
 * @method static string getSecretKey()
 * @method static \Soap\LaravelOmise\Omise fake(array|callable|null $responses = null)
 * @method static \Soap\LaravelOmise\Omise\Account account()
 * @method static \Soap\LaravelOmise\Omise\Capabilities capabilities()
 * @method static \Soap\LaravelOmise\Omise\Charge charge()
 * @method static \Soap\LaravelOmise\Omise\Customer customer()
 * @method static \Soap\LaravelOmise\Omise\Source source()
 * @method static \Soap\LaravelOmise\Omise\Balance balance()
 *
 * @see \Soap\LaravelOmise\Omise
 */
class Omise extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'omise';
    }
}
