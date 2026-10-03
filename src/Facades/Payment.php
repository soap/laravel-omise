<?php

namespace Soap\LaravelOmise\Facades;

use Illuminate\Support\Facades\Facade;
use Soap\LaravelOmise\PaymentManager;

/**
 * @method static \Soap\LaravelOmise\PaymentResult|\Soap\LaravelOmise\Omise\Error createPayment(string $paymentMethod, int $amount, string $currency = 'THB', array $details = [])
 * @method static \Soap\LaravelOmise\PaymentResult|\Soap\LaravelOmise\Omise\Error status(string $chargeId)
 * @method static \OmiseRefund|\Soap\LaravelOmise\Omise\Error refundPayment(string $chargeId, ?int $amount = null)
 * @method static \Soap\LaravelOmise\Contracts\PaymentProcessorInterface processor(string $paymentMethod)
 * @method static \Soap\LaravelOmise\PaymentManager extend(string $paymentMethod, string $processorClass)
 * @method static \Soap\LaravelOmise\PaymentManager using(\Soap\LaravelOmise\Omise $omise)
 * @method static bool supports(string $paymentMethod)
 * @method static array getSupportedMethods()
 *
 * @see PaymentManager
 */
class Payment extends Facade
{
    protected static function getFacadeAccessor()
    {
        return PaymentManager::class;
    }
}
