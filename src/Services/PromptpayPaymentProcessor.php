<?php

namespace Soap\LaravelOmise\Services;

/**
 * PromptPay Payment Processor
 *
 * Handles offline PromptPay payments via QR code scanning
 */
class PromptPayPaymentProcessor extends AbstractOfflinePaymentProcessor
{
    /**
     * Get the payment method identifier
     */
    public function getPaymentMethod(): string
    {
        return 'promptpay';
    }

    /**
     * Check if this payment method needs a source
     */
    protected function needsSource(): bool
    {
        return true;
    }

    /**
     * Validate payment details for PromptPay
     */
    public function validatePaymentDetails(array $paymentDetails): bool
    {
        // PromptPay doesn't require specific payment details
        // Optional validation for return_uri format
        if (isset($paymentDetails['return_uri'])) {
            return filter_var($paymentDetails['return_uri'], FILTER_VALIDATE_URL) !== false;
        }

        return true;
    }

    /**
     * Validate amount for PromptPay
     */
    protected function validateAmount(float $amount): bool
    {
        $minAmount = config('omise.payment_methods.promptpay.min_amount', 20);
        $maxAmount = config('omise.payment_methods.promptpay.max_amount', 1000000);

        return $amount >= $minAmount && $amount <= $maxAmount;
    }

    /**
     * Validate currency for PromptPay
     */
    protected function validateCurrency(string $currency): bool
    {
        // PromptPay only supports THB
        return strtoupper($currency) === 'THB';
    }

    /**
     * Get payment expiration time for PromptPay
     */
    protected function getPaymentExpirationMinutes(): int
    {
        return config('omise.payment_methods.promptpay.expiration_minutes', 15);
    }

    /**
     * Prepare charge parameters for PromptPay
     */
    protected function prepareChargeParams(float $amount, string $currency, array $paymentDetails): array
    {
        $chargeParams = [
            'amount' => $this->convertToSubunit($amount, $currency),
            'currency' => strtoupper($currency),
        ];

        // Add optional parameters
        if (isset($paymentDetails['description'])) {
            $chargeParams['description'] = $paymentDetails['description'];
        }

        if (isset($paymentDetails['metadata'])) {
            $chargeParams['metadata'] = $paymentDetails['metadata'];
        }

        if (isset($paymentDetails['customer'])) {
            $chargeParams['customer'] = $paymentDetails['customer'];
        }

        if (isset($paymentDetails['return_uri'])) {
            $chargeParams['return_uri'] = $paymentDetails['return_uri'];
        } else {
            // Use default return URI from config
            $defaultReturnUri = config('omise.defaults.return_uri');
            if ($defaultReturnUri) {
                $chargeParams['return_uri'] = $defaultReturnUri;
            }
        }

        return $chargeParams;
    }

    /**
     * Prepare source parameters for PromptPay
     */
    protected function prepareSourceParams(float $amount, string $currency, array $paymentDetails): array
    {
        return [
            'type' => 'promptpay',
            'amount' => $this->convertToSubunit($amount, $currency),
            'currency' => strtoupper($currency),
        ];
    }

    /**
     * Get payment instructions for PromptPay
     */
    protected function getPaymentInstructions(array $paymentResult): array
    {
        $instructions = [
            'payment_type' => 'qr_code',
            'instructions' => [
                'step_1' => 'Open your mobile banking app',
                'step_2' => 'Select PromptPay or QR payment option',
                'step_3' => 'Scan the QR code shown below',
                'step_4' => 'Confirm the payment amount and complete the transaction',
                'step_5' => 'Wait for payment confirmation',
            ],
            'tips' => [
                'Make sure you have sufficient balance in your account',
                'The QR code will expire in '.$this->getPaymentExpirationMinutes().' minutes',
                'Do not refresh or close this page until payment is confirmed',
            ],
        ];

        // Add QR code URL if available
        if (isset($paymentResult['authorize_uri'])) {
            $instructions['qr_code_url'] = $paymentResult['authorize_uri'];
            $instructions['qr_display_mode'] = 'popup'; // or 'inline', 'modal'
        }

        return $instructions;
    }

    /**
     * Check if payment requires manual confirmation
     */
    protected function requiresManualConfirmation(): bool
    {
        return true; // PromptPay needs user to scan and confirm
    }

    /**
     * Get polling configuration for payment status
     */
    public function getPollingConfig(): array
    {
        return [
            'enabled' => true,
            'interval_seconds' => 3,
            'max_attempts' => 300, // 15 minutes with 3-second intervals
            'timeout_action' => 'expire_payment',
        ];
    }

    /**
     * Handle payment expiration
     */
    public function handlePaymentExpiration(string $chargeId): array
    {
        // PromptPay payments auto-expire, but we can check status
        $status = $this->checkPaymentStatus($chargeId);

        if ($status['status'] === 'pending') {
            return [
                'expired' => true,
                'message' => 'PromptPay payment has expired. Please create a new payment.',
                'next_action' => 'create_new_payment',
            ];
        }

        return $status;
    }

    /**
     * Check if this payment method supports refunds
     */
    public function hasRefundSupport(): bool
    {
        return true;
    }

    /**
     * Get supported currencies for PromptPay
     */
    public function getSupportedCurrencies(): array
    {
        return ['THB']; // PromptPay only supports THB
    }

    /**
     * Get PromptPay specific limits
     */
    public function getPaymentLimits(): array
    {
        return [
            'min_amount' => 20,
            'max_amount' => 1000000,
            'currency' => 'THB',
            'expiration_minutes' => 15,
            'daily_limit' => 2000000,
            'per_transaction_limit' => 1000000,
        ];
    }

    /**
     * Generate payment reference for tracking
     */
    protected function generatePaymentReference(array $paymentDetails): string
    {
        $prefix = 'PP';
        $timestamp = time();
        $random = mt_rand(1000, 9999);

        return $prefix.$timestamp.$random;
    }
}
