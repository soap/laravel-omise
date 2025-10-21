<?php

namespace Soap\LaravelOmise\Factories;

use InvalidArgumentException;
use Soap\LaravelOmise\Contracts\PaymentProcessorFactoryInterface;
use Soap\LaravelOmise\Contracts\PaymentProcessorInterface;
use Soap\LaravelOmise\OmiseConfig;
use Soap\LaravelOmise\Services\CreditCardPaymentProcessor;
use Soap\LaravelOmise\Services\PromptPayPaymentProcessor;

class PaymentProcessorFactory implements PaymentProcessorFactoryInterface
{
    protected $omiseConfig;

    protected $processors = [];

    public function __construct(OmiseConfig $omiseConfig)
    {
        $this->omiseConfig = $omiseConfig;
        $this->registerDefaultProcessors();
    }

    /**
     * Register default payment processors
     */
    protected function registerDefaultProcessors(): void
    {
        $this->processors = [
            'credit_card' => CreditCardPaymentProcessor::class,
            'card' => CreditCardPaymentProcessor::class, // Alias
            'promptpay' => PromptPayPaymentProcessor::class,
        ];
    }

    /**
     * Make payment processor instance
     *
     * @throws InvalidArgumentException
     */
    public function make(string $paymentMethod): PaymentProcessorInterface
    {
        $paymentMethod = strtolower($paymentMethod);

        if (! isset($this->processors[$paymentMethod])) {
            throw new InvalidArgumentException("Payment processor for '{$paymentMethod}' is not supported.");
        }

        $processorClass = $this->processors[$paymentMethod];

        if (! class_exists($processorClass)) {
            throw new InvalidArgumentException("Payment processor class '{$processorClass}' does not exist.");
        }

        $processor = new $processorClass($this->omiseConfig);

        if (! $processor instanceof PaymentProcessorInterface) {
            throw new InvalidArgumentException('Payment processor must implement PaymentProcessorInterface.');
        }

        return $processor;
    }

    /**
     * Register a custom payment processor
     */
    public function register(string $paymentMethod, string $processorClass): self
    {
        $this->processors[strtolower($paymentMethod)] = $processorClass;

        return $this;
    }

    /**
     * Check if payment method is supported
     */
    public function supports(string $paymentMethod): bool
    {
        return isset($this->processors[strtolower($paymentMethod)]);
    }

    /**
     * Get all supported payment methods
     */
    public function getSupportedMethods(): array
    {
        return array_keys($this->processors);
    }

    /**
     * Get all registered processors
     */
    public function getProcessors(): array
    {
        return $this->processors;
    }
}
