<?php

namespace Soap\LaravelOmise\Services;

/**
 * Abstract class for online payment processors
 *
 * Online payments are processed immediately without user action
 * (e.g., credit card, stored payment methods)
 */
abstract class AbstractOnlinePaymentProcessor extends AbstractPaymentProcessor
{
    /**
     * Check if this is an offline payment method
     */
    public function isOffline(): bool
    {
        return false;
    }

    /**
     * Check if auto-capture is enabled
     */
    protected function isAutoCaptureEnabled(): bool
    {
        return config('omise.payment_methods.'.$this->getPaymentMethod().'.capture', true);
    }

    /**
     * Process 3D Secure authentication if required
     *
     * @param  mixed  $charge
     */
    protected function handle3DSecure($charge): array
    {
        if ($charge->status === 'pending' && isset($charge->authorize_uri)) {
            return [
                'requires_3ds' => true,
                'authorize_uri' => $charge->authorize_uri,
                'next_step' => 'complete_3ds_authentication',
            ];
        }

        return ['requires_3ds' => false];
    }

    /**
     * Create success response for online payment
     *
     * @param  mixed  $charge
     */
    protected function successResponse($charge): array
    {
        $response = parent::successResponse($charge);

        // Add online payment specific data
        $response['is_offline'] = false;
        $response['processed_immediately'] = true;
        $response['auto_capture'] = $this->isAutoCaptureEnabled();

        // Handle 3D Secure if needed
        $threeDSecure = $this->handle3DSecure($charge);
        $response = array_merge($response, $threeDSecure);

        // Add transaction details
        $response['transaction_id'] = $charge->transaction ?? null;
        $response['captured'] = $charge->captured ?? false;
        $response['authorized'] = $charge->authorized ?? false;

        return $response;
    }

    /**
     * Capture authorized payment
     */
    public function capturePayment(string $chargeId, ?float $amount = null): array
    {
        try {
            $charge = $this->charge->find($chargeId);

            if ($charge instanceof \Soap\LaravelOmise\Omise\Error) {
                return $this->errorResponse($charge->getCode(), $charge->getMessage());
            }

            if (! $charge->isAuthorized()) {
                return $this->errorResponse('not_authorized', 'Charge is not authorized for capture');
            }

            $captureParams = [];
            if ($amount !== null) {
                $captureParams['amount'] = $this->convertToSubunit($amount, $charge->currency);
            }

            $capturedCharge = $charge->capture($captureParams);

            if ($capturedCharge instanceof \Soap\LaravelOmise\Omise\Error) {
                return $this->errorResponse($capturedCharge->getCode(), $capturedCharge->getMessage());
            }

            return [
                'success' => true,
                'charge_id' => $capturedCharge->id,
                'status' => $capturedCharge->status,
                'captured' => true,
                'captured_amount' => $capturedCharge->getAmount(),
                'currency' => $capturedCharge->currency,
            ];

        } catch (\Exception $e) {
            return $this->errorResponse('capture_error', $e->getMessage());
        }
    }

    /**
     * Validate credit card specific data
     */
    protected function validateCardData(array $cardData): bool
    {
        if (! isset($cardData['number'], $cardData['expiration_month'], $cardData['expiration_year'])) {
            return false;
        }

        // Validate card number (basic Luhn algorithm)
        $number = preg_replace('/\D/', '', $cardData['number']);
        if (! $this->luhnCheck($number)) {
            return false;
        }

        // Validate expiration
        $month = (int) $cardData['expiration_month'];
        $year = (int) $cardData['expiration_year'];

        if ($month < 1 || $month > 12) {
            return false;
        }

        $currentYear = (int) date('Y');
        $currentMonth = (int) date('m');

        if ($year < $currentYear || ($year == $currentYear && $month < $currentMonth)) {
            return false;
        }

        return true;
    }

    /**
     * Luhn algorithm to validate credit card numbers
     */
    protected function luhnCheck(string $number): bool
    {
        $sum = 0;
        $length = strlen($number);
        $parity = $length % 2;

        for ($i = 0; $i < $length; $i++) {
            $digit = (int) $number[$i];

            if ($i % 2 == $parity) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
        }

        return $sum % 10 == 0;
    }

    /**
     * Get card brand from number
     */
    protected function getCardBrand(string $number): ?string
    {
        $number = preg_replace('/\D/', '', $number);

        $brands = [
            'visa' => '/^4/',
            'mastercard' => '/^5[1-5]|^2[2-7]/',
            'amex' => '/^3[47]/',
            'discover' => '/^6(?:011|5)/',
            'jcb' => '/^35/',
        ];

        foreach ($brands as $brand => $pattern) {
            if (preg_match($pattern, $number)) {
                return $brand;
            }
        }

        return null;
    }
}
