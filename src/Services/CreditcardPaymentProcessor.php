<?php

namespace Soap\LaravelOmise\Services;

/**
 * Credit Card Payment Processor
 *
 * Handles online credit card payments with immediate processing
 */
class CreditCardPaymentProcessor extends AbstractOnlinePaymentProcessor
{
    /**
     * Get the payment method identifier
     */
    public function getPaymentMethod(): string
    {
        return 'credit_card';
    }

    /**
     * Validate payment details for credit card
     */
    public function validatePaymentDetails(array $paymentDetails): bool
    {
        if (! isset($paymentDetails['card'])) {
            return false;
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
     * Prepare charge parameters for credit card
     */
    protected function prepareChargeParams(float $amount, string $currency, array $paymentDetails): array
    {
        $chargeParams = [
            'amount' => $this->convertToSubunit($amount, $currency),
            'currency' => strtoupper($currency),
            'card' => $paymentDetails['card'],
        ];

        // Add capture setting
        $chargeParams['capture'] = $paymentDetails['capture'] ?? $this->isAutoCaptureEnabled();

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
     * Create success response with credit card specific data
     *
     * @param  mixed  $charge
     */
    protected function successResponse($charge): array
    {
        $response = parent::successResponse($charge);

        // Add credit card specific information
        if (isset($charge->card)) {
            $response['card_info'] = [
                'brand' => $charge->card['brand'] ?? null,
                'last_digits' => $charge->card['last_digits'] ?? null,
                'expiration_month' => $charge->card['expiration_month'] ?? null,
                'expiration_year' => $charge->card['expiration_year'] ?? null,
                'financing' => $charge->card['financing'] ?? null,
            ];
        }

        // Add processing information
        $response['processing_type'] = 'immediate';
        $response['supports_partial_capture'] = true;
        $response['supports_void'] = ! $charge->captured;

        return $response;
    }

    /**
     * Void an authorized but not captured charge
     */
    public function voidPayment(string $chargeId): array
    {
        try {
            $charge = $this->charge->find($chargeId);

            if ($charge instanceof \Soap\LaravelOmise\Omise\Error) {
                return $this->errorResponse($charge->getCode(), $charge->getMessage());
            }

            if ($charge->captured) {
                return $this->errorResponse('already_captured', 'Cannot void a captured charge');
            }

            if (! $charge->isAuthorized()) {
                return $this->errorResponse('not_authorized', 'Charge is not authorized');
            }

            // Void by reversing the charge
            $voidedCharge = $charge->reverse();

            if ($voidedCharge instanceof \Soap\LaravelOmise\Omise\Error) {
                return $this->errorResponse($voidedCharge->getCode(), $voidedCharge->getMessage());
            }

            return [
                'success' => true,
                'charge_id' => $voidedCharge->id,
                'status' => $voidedCharge->status,
                'voided' => true,
                'reversed' => $voidedCharge->reversed,
            ];

        } catch (\Exception $e) {
            return $this->errorResponse('void_error', $e->getMessage());
        }
    }

    /**
     * Check if this payment method supports refunds
     */
    public function hasRefundSupport(): bool
    {
        return true;
    }

    /**
     * Get supported currencies for credit card
     */
    public function getSupportedCurrencies(): array
    {
        return ['THB', 'USD', 'EUR', 'GBP', 'SGD', 'JPY', 'AUD', 'CAD', 'CHF', 'CNY', 'DKK', 'HKD', 'MYR'];
    }

    /**
     * Get minimum and maximum amounts
     */
    public function getAmountLimits(string $currency = 'THB'): array
    {
        $limits = [
            'THB' => ['min' => 20, 'max' => 200000],
            'USD' => ['min' => 1, 'max' => 5000],
            'EUR' => ['min' => 1, 'max' => 5000],
            'GBP' => ['min' => 1, 'max' => 5000],
            'SGD' => ['min' => 1, 'max' => 5000],
            'JPY' => ['min' => 100, 'max' => 500000],
        ];

        return $limits[strtoupper($currency)] ?? ['min' => 1, 'max' => 999999];
    }

    /**
     * Validate amount for credit card
     */
    protected function validateAmount(float $amount, string $currency = 'THB'): bool
    {
        if (! parent::validateAmount($amount)) {
            return false;
        }
        // Check if currency is supported
        $limits = $this->getAmountLimits($currency);

        return $amount >= $limits['min'] && $amount <= $limits['max'];
    }
}
