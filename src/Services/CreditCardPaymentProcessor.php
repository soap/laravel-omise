<?php

namespace Soap\LaravelOmise\Services;

/**
 * Charges a card token of Omise.js, or the default card of a customer.
 *
 * Details: `card` (token) and/or `customer`, optional `capture` (false to authorize only).
 * Pass a `return_uri` for cards that require 3-D Secure.
 */
class CreditCardPaymentProcessor extends AbstractPaymentProcessor
{
    public function getPaymentMethod(): string
    {
        return 'card';
    }

    protected function validateDetails(array $details): array
    {
        if (is_array($details['card'] ?? null)) {
            return ['The card must be a token of Omise.js, card data must not reach the server.'];
        }

        if (blank($details['card'] ?? null) && blank($details['customer'] ?? null)) {
            return ['A card token or a customer is required.'];
        }

        return [];
    }

    protected function chargeParams(int $amount, string $currency, array $details): array
    {
        $params = parent::chargeParams($amount, $currency, $details);

        if (isset($details['card'])) {
            $params['card'] = $details['card'];
        }

        if (isset($details['capture'])) {
            // A form encoded boolean would be sent as 1 or 0.
            $params['capture'] = $details['capture'] ? 'true' : 'false';
        }

        return $params;
    }
}
