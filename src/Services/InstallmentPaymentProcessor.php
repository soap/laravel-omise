<?php

namespace Soap\LaravelOmise\Services;

/**
 * Installment Payment Processor
 *
 * Handles online installment payments with immediate processing
 */
class InstallmentPaymentProcessor extends AbstractOnlinePaymentProcessor
{
    protected $installmentTerms;

    public function __construct($omiseConfig, int $installmentTerms = 3)
    {
        parent::__construct($omiseConfig);
        $this->installmentTerms = $installmentTerms;
    }

    /**
     * Get the payment method identifier
     */
    public function getPaymentMethod(): string
    {
        return 'installment_'.$this->installmentTerms;
    }

    /**
     * Check if this payment method needs a source
     */
    protected function needsSource(): bool
    {
        return true;
    }

    /**
     * Validate payment details for installment
     */
    public function validatePaymentDetails(array $paymentDetails): bool
    {
        if (! isset($paymentDetails['card'])) {
            return false;
        }

        // Validate installment terms if provided
        if (isset($paymentDetails['installment_terms'])) {
            $terms = (int) $paymentDetails['installment_terms'];

            return $this->isValidInstallmentTerm($terms);
        }

        $card = $paymentDetails['card'];

        // If it's a card token
        if (is_string($card)) {
            return preg_match('/^tokn_[a-zA-Z0-9]+$/', $card);
        }

        // If it's card data
        if (is_array($card)) {
            return $this->validateCardData($card);
        }

        return false;
    }

    /**
     * Validate installment term
     */
    protected function isValidInstallmentTerm(int $terms): bool
    {
        $supportedTerms = $this->getSupportedInstallmentTerms();

        return in_array($terms, $supportedTerms);
    }

    /**
     * Get supported installment terms
     */
    public function getSupportedInstallmentTerms(): array
    {
        return [3, 4, 6, 9, 10, 12, 18, 24, 36];
    }

    /**
     * Validate amount for installment
     */
    protected function validateAmount(float $amount): bool
    {
        if (! parent::validateAmount($amount)) {
            return false;
        }

        $limits = $this->getInstallmentLimits();

        return $amount >= $limits['min_amount'] && $amount <= $limits['max_amount'];
    }

    /**
     * Prepare charge parameters for installment
     */
    protected function prepareChargeParams(float $amount, string $currency, array $paymentDetails): array
    {
        $chargeParams = [
            'amount' => $this->convertToSubunit($amount, $currency),
            'currency' => strtoupper($currency),
        ];

        // Add installment source
        // Source will be created separately in createPaymentSource method

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
        }

        return $chargeParams;
    }

    /**
     * Prepare source parameters for installment
     */
    protected function prepareSourceParams(float $amount, string $currency, array $paymentDetails): array
    {
        $terms = $paymentDetails['installment_terms'] ?? $this->installmentTerms;

        return [
            'type' => 'installment_'.$terms,
            'amount' => $this->convertToSubunit($amount, $currency),
            'currency' => strtoupper($currency),
            'installment_term' => $terms,
            'card' => $paymentDetails['card'],
            'zero_interest_installments' => $paymentDetails['zero_interest'] ?? false,
        ];
    }

    /**
     * Create success response with installment specific data
     *
     * @param  mixed  $charge
     */
    protected function successResponse($charge): array
    {
        $response = parent::successResponse($charge);

        // Add installment specific information
        $response['installment_info'] = [
            'terms' => $this->installmentTerms,
            'monthly_amount' => round($charge->getAmount() / $this->installmentTerms, 2),
            'zero_interest' => $this->isZeroInterestInstallment(),
            'total_amount' => $charge->getAmount(),
            'interest_rate' => $this->getInterestRate(),
        ];

        // Add processing information
        $response['processing_type'] = 'installment';
        $response['supports_early_settlement'] = true;

        return $response;
    }

    /**
     * Check if zero interest installment is enabled
     */
    protected function isZeroInterestInstallment(): bool
    {
        // This should be determined by merchant settings and capabilities
        return config('omise.payment_methods.installment.zero_interest', false);
    }

    /**
     * Get interest rate for installment
     */
    protected function getInterestRate(): float
    {
        if ($this->isZeroInterestInstallment()) {
            return 0.0;
        }

        // Default interest rates by term
        $rates = [
            3 => 0.0,
            4 => 0.65,
            6 => 0.65,
            9 => 0.65,
            10 => 0.65,
            12 => 0.65,
            18 => 0.65,
            24 => 0.65,
            36 => 0.65,
        ];

        return $rates[$this->installmentTerms] ?? 0.65;
    }

    /**
     * Get installment payment schedule
     */
    public function getPaymentSchedule(float $amount, ?int $terms = null): array
    {
        $terms = $terms ?? $this->installmentTerms;
        $monthlyAmount = round($amount / $terms, 2);
        $interestRate = $this->getInterestRate();

        $schedule = [];
        $startDate = now()->addMonth();

        for ($i = 1; $i <= $terms; $i++) {
            $schedule[] = [
                'installment_number' => $i,
                'due_date' => $startDate->copy()->addMonths($i - 1)->format('Y-m-d'),
                'amount' => $monthlyAmount,
                'principal' => $monthlyAmount,
                'interest' => 0, // For zero interest installments
                'total' => $monthlyAmount,
            ];
        }

        return [
            'schedule' => $schedule,
            'total_installments' => $terms,
            'monthly_amount' => $monthlyAmount,
            'total_amount' => $amount,
            'interest_rate' => $interestRate,
            'zero_interest' => $this->isZeroInterestInstallment(),
        ];
    }

    /**
     * Get installment limits
     */
    public function getInstallmentLimits(): array
    {
        return [
            'min_amount' => 500,
            'max_amount' => 500000,
            'currency' => 'THB',
            'supported_terms' => $this->getSupportedInstallmentTerms(),
            'zero_interest_available' => $this->isZeroInterestInstallment(),
        ];
    }

    /**
     * Check if this payment method supports refunds
     */
    public function hasRefundSupport(): bool
    {
        return true;
    }

    /**
     * Get supported currencies for installment
     */
    public function getSupportedCurrencies(): array
    {
        return ['THB']; // Installments usually only support local currency
    }

    /**
     * Validate currency for installment
     */
    protected function validateCurrency(string $currency): bool
    {
        return strtoupper($currency) === 'THB';
    }
}
