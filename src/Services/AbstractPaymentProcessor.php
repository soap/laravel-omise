<?php

namespace Soap\LaravelOmise\Services;

use Soap\LaravelOmise\Contracts\PaymentProcessorInterface;
use Soap\LaravelOmise\Omise\Charge;
use Soap\LaravelOmise\Omise\Error;
use Soap\LaravelOmise\Omise\Source;
use Soap\LaravelOmise\OmiseConfig;

abstract class AbstractPaymentProcessor implements PaymentProcessorInterface
{
    protected $omiseConfig;

    protected $charge;

    protected $source;

    public function __construct(OmiseConfig $omiseConfig)
    {
        $this->omiseConfig = $omiseConfig;
        $this->charge = new Charge($omiseConfig);
        $this->source = new Source($omiseConfig);
    }

    /**
     * Create payment with the given parameters
     *
     * @param  float  $amount  Payment amount
     * @param  string  $currency  Currency code (default: THB)
     * @param  array  $paymentDetails  Payment specific details
     * @return array Payment creation result
     */
    public function createPayment(float $amount, string $currency = 'THB', array $paymentDetails = []): array
    {
        try {
            // Validate inputs
            if (! $this->validateAmount($amount)) {
                return $this->errorResponse('invalid_amount', 'Invalid payment amount');
            }

            if (! $this->validateCurrency($currency)) {
                return $this->errorResponse('invalid_currency', 'Unsupported currency');
            }

            if (! $this->validatePaymentDetails($paymentDetails)) {
                return $this->errorResponse('invalid_details', 'Invalid payment details');
            }

            // Prepare charge parameters
            $chargeParams = $this->prepareChargeParams($amount, $currency, $paymentDetails);

            // Create source if needed
            if ($this->needsSource()) {
                $sourceResult = $this->createPaymentSource($amount, $currency, $paymentDetails);
                if (isset($sourceResult['error'])) {
                    return $sourceResult;
                }
                $chargeParams['source'] = $sourceResult['source_id'];
            }

            // Create charge
            $charge = $this->charge->create($chargeParams);

            if ($charge instanceof Error) {
                return $this->errorResponse($charge->getCode(), $charge->getMessage());
            }

            return $this->successResponse($charge);

        } catch (\Exception $e) {
            return $this->errorResponse('processing_error', $e->getMessage());
        }
    }

    /**
     * Process payment with the given data
     *
     * @param  array  $paymentData  Payment data
     * @return array Payment processing result
     */
    public function processPayment(array $paymentData): array
    {
        $amount = $paymentData['amount'] ?? 0;
        $currency = $paymentData['currency'] ?? 'THB';
        $paymentDetails = $paymentData['details'] ?? [];

        return $this->createPayment($amount, $currency, $paymentDetails);
    }

    /**
     * Refund payment by charge ID
     *
     * @param  string  $chargeId  Charge ID to refund
     * @param  float  $amount  Amount to refund
     * @return bool Refund success status
     */
    public function refundPayment(string $chargeId, float $amount): bool
    {
        if (! $this->hasRefundSupport()) {
            return false;
        }

        try {
            $charge = $this->charge->find($chargeId);

            if ($charge instanceof Error) {
                return false;
            }

            $refundData = [
                'amount' => $this->convertToSubunit($amount, $charge->currency),
            ];

            $refund = $charge->refund($refundData);

            return ! ($refund instanceof Error);

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get supported currencies for this payment method
     */
    public function getSupportedCurrencies(): array
    {
        return ['THB', 'USD', 'EUR', 'GBP', 'SGD', 'JPY'];
    }

    /**
     * Check if this payment method supports refunds
     */
    public function hasRefundSupport(): bool
    {
        return true;
    }

    /**
     * Check if this is an offline payment method
     */
    public function isOffline(): bool
    {
        return false;
    }

    /**
     * Check if this payment method needs a source
     */
    protected function needsSource(): bool
    {
        return false;
    }

    /**
     * Prepare charge parameters specific to payment method
     */
    abstract protected function prepareChargeParams(float $amount, string $currency, array $paymentDetails): array;

    /**
     * Get the payment method identifier
     */
    abstract public function getPaymentMethod(): string;

    /**
     * Create payment source if needed
     */
    protected function createPaymentSource(float $amount, string $currency, array $paymentDetails): array
    {
        if (! $this->needsSource()) {
            return $this->errorResponse('source_not_needed', 'This payment method does not need a source');
        }

        $sourceParams = $this->prepareSourceParams($amount, $currency, $paymentDetails);
        $source = $this->source->create($sourceParams);

        if ($source instanceof Error) {
            return $this->errorResponse($source->getCode(), $source->getMessage());
        }

        return ['source_id' => $source->id];
    }

    /**
     * Prepare source parameters
     */
    protected function prepareSourceParams(float $amount, string $currency, array $paymentDetails): array
    {
        return [];
    }

    /**
     * Validate amount
     */
    protected function validateAmount(float $amount): bool
    {
        return $amount > 0;
    }

    /**
     * Validate currency
     */
    protected function validateCurrency(string $currency): bool
    {
        return in_array(strtoupper($currency), $this->getSupportedCurrencies());
    }

    /**
     * Convert amount to subunit
     */
    protected function convertToSubunit(float $amount, string $currency): int
    {
        return \Soap\LaravelOmise\Omise\Helpers\OmiseMoney::toSubunit($amount, $currency);
    }

    /**
     * Convert amount from subunit
     */
    protected function convertFromSubunit(int $amount, string $currency): float
    {
        return \Soap\LaravelOmise\Omise\Helpers\OmiseMoney::toCurrencyUnit($amount, $currency);
    }

    /**
     * Create success response
     *
     * @param  mixed  $charge
     */
    protected function successResponse($charge): array
    {
        return [
            'success' => true,
            'charge_id' => $charge->id,
            'status' => $charge->status,
            'amount' => $charge->getAmount(),
            'currency' => $charge->currency,
            'payment_method' => $this->getPaymentMethod(),
            'authorize_uri' => method_exists($charge->object, 'authorizeUri') ? $charge->object->authorizeUri() : null,
            'charge' => $charge,
        ];
    }

    /**
     * Create error response
     */
    protected function errorResponse(string $code, string $message): array
    {
        return [
            'success' => false,
            'error' => true,
            'error_code' => $code,
            'error_message' => $message,
            'payment_method' => $this->getPaymentMethod(),
        ];
    }
}
