<?php

namespace Soap\LaravelOmise\Facades;

use Illuminate\Support\Facades\Facade;
use Soap\LaravelOmise\PaymentManager;

/**
 * @method static array createPayment(string $paymentMethod, float $amount, string $currency = 'THB', array $paymentDetails = [])
 * @method static array processPayment(string $paymentMethod, array $paymentData)
 * @method static bool refundPayment(string $paymentMethod, string $chargeId, float $amount)
 * @method static \Soap\LaravelOmise\Contracts\PaymentProcessorInterface getProcessor(string $paymentMethod)
 * @method static \Soap\LaravelOmise\PaymentManager extend(string $paymentMethod, string $processorClass)
 * @method static bool supports(string $paymentMethod)
 * @method static array getSupportedMethods()
 * @method static bool hasRefundSupport(string $paymentMethod)
 * @method static bool isOffline(string $paymentMethod)
 * @method static array getSupportedCurrencies(string $paymentMethod)
 * @method static bool validatePaymentDetails(string $paymentMethod, array $paymentDetails)
 * @method static array getPaymentMethodInfo(string $paymentMethod)
 *
 * @see \Soap\LaravelOmise\PaymentManager
 */
class Payment extends Facade
{
    protected static function getFacadeAccessor()
    {
        return PaymentManager::class;
    }
}
