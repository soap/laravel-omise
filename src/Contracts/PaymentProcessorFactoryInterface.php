<?php

namespace Soap\LaravelOmise\Contracts;

interface PaymentProcessorFactoryInterface
{
    /**
     * Make payment processor instance
     */
    public function make(string $paymentMethod): PaymentProcessorInterface;

    /**
     * Register a custom payment processor
     */
    public function register(string $paymentMethod, string $processorClass): self;

    /**
     * Check if payment method is supported
     */
    public function supports(string $paymentMethod): bool;

    /**
     * Get all supported payment methods
     */
    public function getSupportedMethods(): array;
}
