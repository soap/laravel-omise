<?php

namespace Soap\LaravelOmise\Services;

/**
 * Abstract class for offline payment processors
 *
 * Offline payments require user action outside the application
 * (e.g., scanning QR code, bank transfer, cash payment)
 */
abstract class AbstractOfflinePaymentProcessor extends AbstractPaymentProcessor
{
    /**
     * Check if this is an offline payment method
     */
    public function isOffline(): bool
    {
        return true;
    }

    /**
     * Get payment instructions for the user
     */
    abstract protected function getPaymentInstructions(array $paymentResult): array;

    /**
     * Get payment expiration time (minutes)
     */
    protected function getPaymentExpirationMinutes(): int
    {
        return 15; // Default 15 minutes
    }

    /**
     * Check if payment requires manual confirmation
     */
    protected function requiresManualConfirmation(): bool
    {
        return true;
    }

    /**
     * Create success response with offline payment instructions
     *
     * @param  mixed  $charge
     */
    protected function successResponse($charge): array
    {
        $response = parent::successResponse($charge);

        // Add offline payment specific data
        $response['is_offline'] = true;
        $response['requires_user_action'] = true;
        $response['expires_in_minutes'] = $this->getPaymentExpirationMinutes();
        $response['requires_manual_confirmation'] = $this->requiresManualConfirmation();

        // Add payment instructions
        $instructions = $this->getPaymentInstructions($response);
        $response = array_merge($response, $instructions);

        return $response;
    }

    /**
     * Get the next step for user after payment creation
     */
    protected function getNextStep(): string
    {
        return 'await_user_action';
    }

    /**
     * Check payment status (for offline payments that need polling)
     */
    public function checkPaymentStatus(string $chargeId): array
    {
        try {
            $charge = $this->charge->find($chargeId);

            if ($charge instanceof \Soap\LaravelOmise\Omise\Error) {
                return $this->errorResponse($charge->getCode(), $charge->getMessage());
            }

            return [
                'charge_id' => $charge->id,
                'status' => $charge->status,
                'paid' => $charge->isPaid(),
                'successful' => $charge->isSuccessful(),
                'failed' => $charge->isFailed(),
                'amount' => $charge->getAmount(),
                'currency' => $charge->currency,
                'payment_method' => $this->getPaymentMethod(),
            ];

        } catch (\Exception $e) {
            return $this->errorResponse('status_check_error', $e->getMessage());
        }
    }
}
