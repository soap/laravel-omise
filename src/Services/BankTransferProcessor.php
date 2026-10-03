<?php

namespace Soap\LaravelOmise\Services;

/**
 * Internet Banking Payment Processor
 *
 * Handles offline internet banking payments via bank redirects
 */
class InternetBankingPaymentProcessor extends AbstractOfflinePaymentProcessor
{
    protected $bankCode;

    public function __construct($omiseConfig, string $bankCode = 'scb')
    {
        parent::__construct($omiseConfig);
        $this->bankCode = $bankCode;
    }

    /**
     * Get the payment method identifier
     */
    public function getPaymentMethod(): string
    {
        return 'internet_banking_'.$this->bankCode;
    }

    /**
     * Check if this payment method needs a source
     */
    protected function needsSource(): bool
    {
        return true;
    }

    /**
     * Validate payment details for internet banking
     */
    public function validatePaymentDetails(array $paymentDetails): bool
    {
        // Validate bank code if provided
        if (isset($paymentDetails['bank_code'])) {
            return $this->isValidBankCode($paymentDetails['bank_code']);
        }

        // Validate return URI
        if (isset($paymentDetails['return_uri'])) {
            return filter_var($paymentDetails['return_uri'], FILTER_VALIDATE_URL) !== false;
        }

        return true;
    }

    /**
     * Validate bank code
     */
    protected function isValidBankCode(string $bankCode): bool
    {
        $supportedBanks = $this->getSupportedBanks();

        return array_key_exists($bankCode, $supportedBanks);
    }

    /**
     * Get supported banks
     */
    public function getSupportedBanks(): array
    {
        return [
            'scb' => 'Siam Commercial Bank',
            'bbl' => 'Bangkok Bank',
            'ktb' => 'Krung Thai Bank',
            'kbank' => 'Kasikorn Bank',
            'bay' => 'Bank of Ayudhya (Krungsri)',
            'gsb' => 'Government Savings Bank',
            'ttb' => 'TMBThanachart Bank',
            'uob' => 'United Overseas Bank',
        ];
    }

    /**
     * Get payment expiration time for internet banking
     */
    protected function getPaymentExpirationMinutes(): int
    {
        return 30; // Internet banking usually has longer expiration
    }

    /**
     * Prepare charge parameters for internet banking
     */
    protected function prepareChargeParams(float $amount, string $currency, array $paymentDetails): array
    {
        $chargeParams = [
            'amount' => $this->convertToSubunit($amount, $currency),
            'currency' => strtoupper($currency),
        ];

        // Add required return URI
        if (isset($paymentDetails['return_uri'])) {
            $chargeParams['return_uri'] = $paymentDetails['return_uri'];
        } else {
            $defaultReturnUri = config('omise.defaults.return_uri');
            if ($defaultReturnUri) {
                $chargeParams['return_uri'] = $defaultReturnUri;
            }
        }

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

        return $chargeParams;
    }

    /**
     * Prepare source parameters for internet banking
     */
    protected function prepareSourceParams(float $amount, string $currency, array $paymentDetails): array
    {
        // Use provided bank code or default
        $bankCode = $paymentDetails['bank_code'] ?? $this->bankCode;

        return [
            'type' => 'internet_banking_'.$bankCode,
            'amount' => $this->convertToSubunit($amount, $currency),
            'currency' => strtoupper($currency),
        ];
    }

    /**
     * Get payment instructions for internet banking
     */
    protected function getPaymentInstructions(array $paymentResult): array
    {
        $bankName = $this->getSupportedBanks()[$this->bankCode] ?? 'Selected Bank';

        $instructions = [
            'payment_type' => 'bank_redirect',
            'bank_name' => $bankName,
            'bank_code' => $this->bankCode,
            'instructions' => [
                'step_1' => 'Click the "Pay with '.$bankName.'" button below',
                'step_2' => 'You will be redirected to '.$bankName.' secure login page',
                'step_3' => 'Login with your internet banking credentials',
                'step_4' => 'Review and confirm the payment details',
                'step_5' => 'Complete the payment authorization',
                'step_6' => 'You will be redirected back to our website',
            ],
            'tips' => [
                'Make sure you have internet banking enabled for your account',
                'Keep your banking credentials secure and do not share them',
                'The payment session will expire in '.$this->getPaymentExpirationMinutes().' minutes',
                'Do not close the browser window during the payment process',
            ],
        ];

        // Add redirect URL if available
        if (isset($paymentResult['authorize_uri'])) {
            $instructions['redirect_url'] = $paymentResult['authorize_uri'];
            $instructions['redirect_method'] = 'GET';
        }

        return $instructions;
    }

    /**
     * Get polling configuration for payment status
     */
    public function getPollingConfig(): array
    {
        return [
            'enabled' => true,
            'interval_seconds' => 5,
            'max_attempts' => 360, // 30 minutes with 5-second intervals
            'timeout_action' => 'expire_payment',
        ];
    }

    /**
     * Validate currency for internet banking
     */
    protected function validateCurrency(string $currency): bool
    {
        // Most Thai banks only support THB
        return strtoupper($currency) === 'THB';
    }

    /**
     * Get supported currencies for internet banking
     */
    public function getSupportedCurrencies(): array
    {
        return ['THB'];
    }

    /**
     * Check if this payment method supports refunds
     */
    public function hasRefundSupport(): bool
    {
        return true;
    }

    /**
     * Get internet banking specific limits
     */
    public function getPaymentLimits(): array
    {
        return [
            'min_amount' => 10,
            'max_amount' => 2000000,
            'currency' => 'THB',
            'expiration_minutes' => 30,
            'daily_limit' => 5000000,
            'per_transaction_limit' => 2000000,
        ];
    }
}
