<?php

namespace Soap\LaravelOmise\Services;

/**
 * Creates an installment charge.
 *
 * Details: `bank` (bay, kbank, ktc, scb...), `term` (number of months) and the `card`
 * token when the bank needs one. The banks, their terms and the minimum amount of the
 * account are in its capabilities: `Omise::capabilities()->getInstallmentBackends()`.
 */
class InstallmentPaymentProcessor extends AbstractPaymentProcessor
{
    public function getPaymentMethod(): string
    {
        return 'installment';
    }

    public function getSupportedCurrencies(): array
    {
        return ['THB'];
    }

    public function amountLimits(): ?array
    {
        $limits = $this->omise->capabilities()->retrieve()->limits['installment_amount'] ?? null;

        return is_array($limits) ? ['min' => $limits['min'] ?? null, 'max' => $limits['max'] ?? null] : null;
    }

    protected function capabilityName(): string
    {
        return 'installment_';
    }

    protected function validateDetails(array $details): array
    {
        $problems = [];

        if (blank($details['bank'] ?? null)) {
            $problems[] = 'A bank is required.';
        }

        if ((int) ($details['term'] ?? 0) < 1) {
            $problems[] = 'A term (number of months) is required.';
        }

        if (is_array($details['card'] ?? null)) {
            $problems[] = 'The card must be a token of Omise.js, card data must not reach the server.';
        }

        return $problems;
    }

    protected function chargeParams(int $amount, string $currency, array $details): array
    {
        $params = array_merge(parent::chargeParams($amount, $currency, $details), [
            'source' => [
                'type' => 'installment_'.strtolower($details['bank']),
                'installment_term' => (int) $details['term'],
            ],
        ]);

        if (isset($details['card'])) {
            $params['card'] = $details['card'];
        }

        return $params;
    }
}
