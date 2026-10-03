<?php

namespace Soap\LaravelOmise\Services;

/**
 * Creates a PromptPay charge, the customer pays by scanning `PaymentResult::qrCodeUrl()`.
 *
 * The charge stays pending until the customer pays: wait for the `charge.complete`
 * webhook, or look it up with `PaymentManager::status()`.
 */
class PromptPayPaymentProcessor extends AbstractPaymentProcessor
{
    public function getPaymentMethod(): string
    {
        return 'promptpay';
    }

    public function isOffline(): bool
    {
        return true;
    }

    public function getSupportedCurrencies(): array
    {
        return ['THB'];
    }

    protected function chargeParams(int $amount, string $currency, array $details): array
    {
        return array_merge(parent::chargeParams($amount, $currency, $details), [
            'source' => ['type' => 'promptpay'],
        ]);
    }
}
