<?php

namespace Soap\LaravelOmise\Tests\Factories;

class OmiseResponseFactory
{
    public static function successfulCharge(array $overrides = []): array
    {
        return array_merge([
            'object' => 'charge',
            'id' => 'chrg_test_'.uniqid(),
            'livemode' => false,
            'amount' => 100000,
            'currency' => 'thb',
            'description' => 'Test charge',
            'status' => 'successful',
            'authorized' => true,
            'captured' => true,
            'paid' => true,
            'reversed' => false,
            'refunded' => 0,
            'refunds' => ['data' => []],
            'card' => [
                'object' => 'card',
                'id' => 'card_test_123',
                'last_digits' => '4242',
                'brand' => 'Visa',
                'financing' => '',
                'bank' => '',
            ],
            'created' => '2025-01-01T00:00:00Z',
            'metadata' => [],
        ], $overrides);
    }

    public static function failedCharge(array $overrides = []): array
    {
        return array_merge([
            'object' => 'charge',
            'id' => 'chrg_test_'.uniqid(),
            'livemode' => false,
            'amount' => 100000,
            'currency' => 'thb',
            'status' => 'failed',
            'authorized' => false,
            'captured' => false,
            'paid' => false,
            'failure_code' => 'insufficient_fund',
            'failure_message' => 'Your account cannot be charged.',
            'created' => '2025-01-01T00:00:00Z',
        ], $overrides);
    }

    public static function pendingCharge(array $overrides = []): array
    {
        return array_merge([
            'object' => 'charge',
            'id' => 'chrg_test_'.uniqid(),
            'livemode' => false,
            'amount' => 100000,
            'currency' => 'thb',
            'status' => 'pending',
            'authorized' => true,
            'captured' => false,
            'paid' => false,
            'created' => '2025-01-01T00:00:00Z',
        ], $overrides);
    }

    public static function refund(array $overrides = []): array
    {
        return array_merge([
            'object' => 'refund',
            'id' => 'rfnd_test_'.uniqid(),
            'livemode' => false,
            'amount' => 50000,
            'currency' => 'thb',
            'charge' => 'chrg_test_123456',
            'transaction' => 'trxn_test_123456',
            'created' => '2025-01-01T00:00:00Z',
            'metadata' => [],
        ], $overrides);
    }

    public static function apiError(string $code = 'bad_request', string $message = 'Invalid request'): array
    {
        return [
            'object' => 'error',
            'code' => $code,
            'message' => $message,
        ];
    }
}
