<?php

namespace Soap\LaravelOmise\Contracts;

use Soap\LaravelOmise\Exceptions\OmiseRequestException;
use Soap\LaravelOmise\Omise\Error;
use Soap\LaravelOmise\PaymentResult;

interface PaymentProcessorInterface
{
    /**
     * The name the processor is registered with, e.g. `card` or `promptpay`.
     */
    public function getPaymentMethod(): string;

    /**
     * Create a charge for the payment method.
     *
     * @param  int  $amount  in the smallest unit of the currency (satang for THB), as the Omise API expects
     * @param  array<string, mixed>  $details  description, metadata, customer, return_uri and what the payment method needs
     * @return PaymentResult|Error
     *
     * @throws OmiseRequestException when `omise.throw` is enabled
     */
    public function createPayment(int $amount, string $currency = 'THB', array $details = []);

    /**
     * What prevents the payment from being created, without calling the API.
     *
     * @param  array<string, mixed>  $details
     * @return array<int, string> empty when nothing is wrong
     */
    public function validate(int $amount, string $currency, array $details = []): array;

    /**
     * Whether the customer completes the payment outside the application (QR code, banking app).
     */
    public function isOffline(): bool;

    public function hasRefundSupport(): bool;

    /**
     * The currencies the payment method is limited to, empty when Omise decides.
     *
     * @return array<int, string>
     */
    public function getSupportedCurrencies(): array;
}
