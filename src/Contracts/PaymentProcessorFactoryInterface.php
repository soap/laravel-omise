<?php

namespace Soap\LaravelOmise\Contracts;

use InvalidArgumentException;
use Soap\LaravelOmise\Omise;

interface PaymentProcessorFactoryInterface
{
    /**
     * @throws InvalidArgumentException when the payment method is not registered
     */
    public function make(string $paymentMethod): PaymentProcessorInterface;

    /**
     * Register a payment processor, or replace the one of a payment method.
     *
     * @param  class-string<PaymentProcessorInterface>  $processorClass
     * @return $this
     */
    public function register(string $paymentMethod, string $processorClass);

    public function supports(string $paymentMethod): bool;

    /**
     * @return array<int, string>
     */
    public function getSupportedMethods(): array;

    /**
     * A factory with the same processors that sends requests with the given Omise instance.
     *
     * @return static
     */
    public function using(Omise $omise);
}
