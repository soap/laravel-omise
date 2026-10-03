<?php

namespace Soap\LaravelOmise\Factories;

use InvalidArgumentException;
use Soap\LaravelOmise\Contracts\PaymentProcessorFactoryInterface;
use Soap\LaravelOmise\Contracts\PaymentProcessorInterface;
use Soap\LaravelOmise\Omise;
use Soap\LaravelOmise\Services\CreditCardPaymentProcessor;
use Soap\LaravelOmise\Services\InstallmentPaymentProcessor;
use Soap\LaravelOmise\Services\MobileBankingPaymentProcessor;
use Soap\LaravelOmise\Services\PromptPayPaymentProcessor;

class PaymentProcessorFactory implements PaymentProcessorFactoryInterface
{
    /**
     * @var Omise
     */
    protected $omise;

    /**
     * @var array<string, class-string<PaymentProcessorInterface>>
     */
    protected $processors = [
        'card' => CreditCardPaymentProcessor::class,
        'credit_card' => CreditCardPaymentProcessor::class,
        'promptpay' => PromptPayPaymentProcessor::class,
        'mobile_banking' => MobileBankingPaymentProcessor::class,
        'installment' => InstallmentPaymentProcessor::class,
    ];

    public function __construct(Omise $omise)
    {
        $this->omise = $omise;
    }

    public function make(string $paymentMethod): PaymentProcessorInterface
    {
        $processorClass = $this->processors[strtolower($paymentMethod)] ?? null;

        if ($processorClass === null) {
            throw new InvalidArgumentException("Payment method [{$paymentMethod}] is not supported.");
        }

        $processor = new $processorClass($this->omise);

        if (! $processor instanceof PaymentProcessorInterface) {
            throw new InvalidArgumentException("Payment processor [{$processorClass}] must implement PaymentProcessorInterface.");
        }

        return $processor;
    }

    public function register(string $paymentMethod, string $processorClass)
    {
        $this->processors[strtolower($paymentMethod)] = $processorClass;

        return $this;
    }

    public function supports(string $paymentMethod): bool
    {
        return isset($this->processors[strtolower($paymentMethod)]);
    }

    public function getSupportedMethods(): array
    {
        return array_keys($this->processors);
    }

    public function using(Omise $omise)
    {
        $factory = clone $this;
        $factory->omise = $omise;

        return $factory;
    }
}
