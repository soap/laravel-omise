<?php

namespace Soap\LaravelOmise\Contracts;

interface PaymentProcessorInterface
{
    /**
     * Create payment with the given parameters
     *
     * @param  float  $amount  Payment amount
     * @param  string  $currency  Currency code (default: THB)
     * @param  array  $paymentDetails  Payment specific details
     * @return array Payment creation result
     */
    public function createPayment(float $amount, string $currency = 'THB', array $paymentDetails = []): array;

    /**
     * Process payment with the given data
     *
     * @param  array  $paymentData  Payment data
     * @return array Payment processing result
     */
    public function processPayment(array $paymentData): array;

    /**
     * Refund payment by charge ID
     *
     * @param  string  $chargeId  Charge ID to refund
     * @param  float  $amount  Amount to refund
     * @return bool Refund success status
     */
    public function refundPayment(string $chargeId, float $amount): bool;

    /**
     * Check if this payment method supports refunds
     */
    public function hasRefundSupport(): bool;

    /**
     * Check if this is an offline payment method
     */
    public function isOffline(): bool;

    /**
     * Get the payment method identifier
     */
    public function getPaymentMethod(): string;

    /**
     * Get supported currencies for this payment method
     */
    public function getSupportedCurrencies(): array;

    /**
     * Validate payment details before processing
     */
    public function validatePaymentDetails(array $paymentDetails): bool;
}
