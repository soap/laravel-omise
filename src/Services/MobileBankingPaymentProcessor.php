<?php

namespace Soap\LaravelOmise\Services;

/**
 * Creates a mobile banking charge, the customer is redirected to the app of the bank.
 *
 * Details: `bank` (scb, kbank, bay, bbl, ktb...) and a `return_uri`.
 */
class MobileBankingPaymentProcessor extends AbstractPaymentProcessor
{
    public function getPaymentMethod(): string
    {
        return 'mobile_banking';
    }

    public function isOffline(): bool
    {
        return true;
    }

    public function getSupportedCurrencies(): array
    {
        return ['THB'];
    }

    protected function capabilityName(): string
    {
        return 'mobile_banking_';
    }

    protected function validateDetails(array $details): array
    {
        $problems = [];

        if (blank($details['bank'] ?? null)) {
            $problems[] = 'A bank is required.';
        }

        if (blank($details['return_uri'] ?? null)) {
            $problems[] = 'A return_uri is required.';
        }

        return $problems;
    }

    protected function chargeParams(int $amount, string $currency, array $details): array
    {
        return array_merge(parent::chargeParams($amount, $currency, $details), [
            'source' => ['type' => 'mobile_banking_'.strtolower($details['bank'])],
        ]);
    }
}
