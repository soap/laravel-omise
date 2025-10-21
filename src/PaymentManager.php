<?php

namespace Soap\LaravelOmise;

use Soap\LaravelOmise\Contracts\PaymentProcessorFactoryInterface;
use Soap\LaravelOmise\Contracts\PaymentProcessorInterface;

class PaymentManager
{
    protected $factory;

    public function __construct(PaymentProcessorFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Create payment using specified payment method
     */
    public function createPayment(string $paymentMethod, float $amount, string $currency = 'THB', array $paymentDetails = []): array
    {
        try {
            $processor = $this->factory->make($paymentMethod);

            return $processor->createPayment($amount, $currency, $paymentDetails);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => true,
                'error_code' => 'payment_processor_error',
                'error_message' => $e->getMessage(),
                'payment_method' => $paymentMethod,
            ];
        }
    }

    /**
     * Process payment using specified payment method
     */
    public function processPayment(string $paymentMethod, array $paymentData): array
    {
        try {
            $processor = $this->factory->make($paymentMethod);

            return $processor->processPayment($paymentData);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => true,
                'error_code' => 'payment_processor_error',
                'error_message' => $e->getMessage(),
                'payment_method' => $paymentMethod,
            ];
        }
    }

    /**
     * Refund payment
     */
    public function refundPayment(string $paymentMethod, string $chargeId, float $amount): bool
    {
        try {
            $processor = $this->factory->make($paymentMethod);

            return $processor->refundPayment($chargeId, $amount);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get payment processor instance
     */
    public function getProcessor(string $paymentMethod): PaymentProcessorInterface
    {
        return $this->factory->make($paymentMethod);
    }

    /**
     * Register a custom payment processor
     */
    public function extend(string $paymentMethod, string $processorClass): self
    {
        $this->factory->register($paymentMethod, $processorClass);

        return $this;
    }

    /**
     * Check if payment method is supported
     */
    public function supports(string $paymentMethod): bool
    {
        return $this->factory->supports($paymentMethod);
    }

    /**
     * Get all supported payment methods
     */
    public function getSupportedMethods(): array
    {
        return $this->factory->getSupportedMethods();
    }

    /**
     * Check if payment method supports refunds
     */
    public function hasRefundSupport(string $paymentMethod): bool
    {
        try {
            $processor = $this->factory->make($paymentMethod);

            return $processor->hasRefundSupport();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if payment method is offline
     */
    public function isOffline(string $paymentMethod): bool
    {
        try {
            $processor = $this->factory->make($paymentMethod);

            return $processor->isOffline();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get supported currencies for payment method
     */
    public function getSupportedCurrencies(string $paymentMethod): array
    {
        try {
            $processor = $this->factory->make($paymentMethod);

            return $processor->getSupportedCurrencies();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Validate payment details for specific method
     */
    public function validatePaymentDetails(string $paymentMethod, array $paymentDetails): bool
    {
        try {
            $processor = $this->factory->make($paymentMethod);

            return $processor->validatePaymentDetails($paymentDetails);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get payment method information
     */
    public function getPaymentMethodInfo(string $paymentMethod): array
    {
        try {
            $processor = $this->factory->make($paymentMethod);

            return [
                'method' => $paymentMethod,
                'supports_refund' => $processor->hasRefundSupport(),
                'is_offline' => $processor->isOffline(),
                'supported_currencies' => $processor->getSupportedCurrencies(),
                'processor_class' => get_class($processor),
            ];
        } catch (\Exception $e) {
            return [
                'method' => $paymentMethod,
                'error' => $e->getMessage(),
            ];
        }
    }
}
