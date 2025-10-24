<?php

namespace Soap\LaravelOmise\Commands;

use Illuminate\Console\Command;
use Soap\LaravelOmise\Omise\Error;

class OmiseCapabilitiesCommand extends Command
{
    public $signature = 'omise:capabilities 
                         {--format=table : Output format (table, json)}
                         {--currency= : Filter by currency}
                         {--type= : Filter by payment type (installment, fpx, etc.)}';

    public $description = 'Retrieve and display capabilities information from Omise API';

    public function handle(): int
    {
        $this->info('🔍 Fetching Omise capabilities...');

        $capabilities = app('omise')->capabilities()->retrieve();

        if ($capabilities instanceof Error) {
            $this->error('❌ Failed to retrieve capabilities:');
            $this->error($capabilities->getMessage());

            return self::FAILURE;
        }

        $format = $this->option('format');
        $currency = $this->option('currency');
        $type = $this->option('type');

        if ($format === 'json') {
            return $this->displayJson($capabilities, $currency, $type);
        }

        return $this->displayTable($capabilities, $currency, $type);
    }

    private function displayTable($capabilities, $currency = null, $type = null): int
    {
        $this->info('✅ Successfully retrieved capabilities');
        $this->newLine();

        // Account Information
        $this->displayAccountInfo($capabilities);

        // Quick Summary
        $this->displayQuickSummary($capabilities);

        // Limits Information
        $this->displayLimits($capabilities);

        // Payment Methods
        $this->displayPaymentMethods($capabilities, $currency, $type);

        // Zero Interest Information
        $this->displayZeroInterestInfo($capabilities);

        return self::SUCCESS;
    }

    private function displayJson($capabilities, $currency = null, $type = null): int
    {
        $data = [
            'account_info' => [
                'id' => $capabilities->id ?? 'N/A',
                'zero_interest_installments' => $capabilities->zero_interest_installments ?? false,
            ],
            'limits' => $capabilities->limits ?? [],
            'payment_methods' => $this->getFilteredPaymentMethods($capabilities, $currency, $type),
            'tokenization_methods' => $capabilities->getTokenizationMethods(),
        ];

        $this->line(json_encode($data, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }

    private function displayAccountInfo($capabilities): void
    {
        $this->info('📋 Account Information:');
        $headers = ['Property', 'Value'];
        $rows = [
            ['Object Type', $capabilities->object['object'] ?? 'capability'],
            ['Country', $capabilities->getCountry() ?? 'N/A'],
            ['Zero Interest Installments', $capabilities->zero_interest_installments ? '✅ Enabled' : '❌ Disabled'],
            ['Supported Banks', count($capabilities->getSupportedBanks())],
        ];

        $this->table($headers, $rows);
        $this->newLine();
    }

    private function displayQuickSummary($capabilities): void
    {
        $this->info('📊 Quick Summary:');

        $paymentMethods = $capabilities->getAllPaymentMethods();
        $installmentMethods = $capabilities->getInstallmentBackends();
        $currencies = $capabilities->getSupportedCurrencies();
        $tokenMethods = $capabilities->getTokenizationMethods() ?? [];

        $headers = ['Category', 'Count', 'Details'];
        $rows = [
            ['Payment Methods', count($paymentMethods), 'Total available payment options'],
            ['Installment Options', count($installmentMethods), 'Banks offering installment payments'],
            ['Supported Currencies', count($currencies), implode(', ', array_slice($currencies, 0, 5)).(count($currencies) > 5 ? '...' : '')],
            ['Digital Wallets', count($tokenMethods), implode(', ', $tokenMethods)],
        ];

        $this->table($headers, $rows);
        $this->newLine();
    }

    private function displayLimits($capabilities): void
    {
        if (! $capabilities->limits) {
            return;
        }

        $this->info('💰 Payment Limits:');
        $headers = ['Type', 'Minimum', 'Maximum'];
        $rows = [];

        foreach ($capabilities->limits as $type => $limit) {
            $min = isset($limit['min']) ? number_format($limit['min'] / 100, 2).' THB' : 'N/A';
            $max = isset($limit['max']) ? number_format($limit['max'] / 100, 2).' THB' : 'N/A';
            $rows[] = [ucfirst(str_replace('_', ' ', $type)), $min, $max];
        }

        $this->table($headers, $rows);
        $this->newLine();
    }

    private function displayPaymentMethods($capabilities, $currency = null, $type = null): void
    {
        $this->info('💳 Available Payment Methods:');

        try {
            $methods = $this->getFilteredPaymentMethods($capabilities, $currency, $type);

            if (empty($methods)) {
                $this->warn('No payment methods found for the specified filters.');

                return;
            }

            $headers = ['Payment Method', 'Type', 'Currencies', 'Features'];
            $rows = [];

            foreach ($methods as $method) {
                $currencies = is_array($method['currencies']) ? implode(', ', $method['currencies']) : 'N/A';

                $features = [];
                if ($method['installment_terms']) {
                    $features[] = 'Installments: '.implode(', ', $method['installment_terms']).' months';
                }
                if ($method['card_brands']) {
                    $features[] = 'Cards: '.implode(', ', $method['card_brands']);
                }

                $rows[] = [
                    $method['name'],
                    $method['type'],
                    $currencies,
                    ! empty($features) ? implode('; ', $features) : 'Standard payment',
                ];
            }

            $this->table($headers, $rows);
            $this->line('📊 Total methods found: '.count($methods));
        } catch (\Exception $e) {
            $this->warn('Unable to retrieve payment methods: '.$e->getMessage());
        }

        $this->newLine();
    }

    private function displayZeroInterestInfo($capabilities): void
    {
        if (! $capabilities->zero_interest_installments) {
            return;
        }

        $this->info('🎁 Zero Interest Installments:');
        $this->line('✅ Your account supports zero interest installment payments');

        try {
            $installmentMethods = $capabilities->getInstallmentBackends();
            if (! empty($installmentMethods)) {
                $this->line('Available installment options:');
                foreach ($installmentMethods as $method) {
                    $terms = $method['installment_terms'] ?? [];
                    $this->line('  • '.($method['name'] ?? $method['_id']).
                              ' (Terms: '.implode(', ', $terms).' months)');
                }
            }
        } catch (\Exception $e) {
            $this->warn('Unable to retrieve installment details: '.$e->getMessage());
        }
    }

    private function getFilteredPaymentMethods($capabilities, $currency = null, $type = null): array
    {
        try {
            // Get payment methods from actual API response
            $paymentMethods = $capabilities->getBackends($currency);
            $methods = [];

            foreach ($paymentMethods as $method) {
                // Apply type filter
                if ($type && stripos($method['name'], $type) === false) {
                    continue;
                }

                $methods[] = [
                    'name' => $this->getPaymentMethodName($method['name']),
                    'id' => $method['name'],
                    'type' => $this->getPaymentMethodType($method['name']),
                    'currencies' => $method['currencies'] ?? [],
                    'enabled' => true,
                    'installment_terms' => $method['installment_terms'],
                    'card_brands' => $method['card_brands'],
                ];
            }

            // Add tokenization methods
            $tokenMethods = $capabilities->getTokenizationMethods() ?? [];
            foreach ($tokenMethods as $tokenMethod) {
                if ($type && stripos($tokenMethod, $type) === false) {
                    continue;
                }

                $methods[] = [
                    'name' => $this->getPaymentMethodName($tokenMethod),
                    'id' => $tokenMethod,
                    'type' => 'Digital Wallet',
                    'currencies' => $currency ? [$currency] : ['THB'],
                    'enabled' => true,
                    'installment_terms' => null,
                    'card_brands' => null,
                ];
            }

            return $methods;
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getPaymentMethodName($methodId): string
    {
        $names = [
            'card' => 'Credit/Debit Card',
            'alipay' => 'Alipay',
            'alipay_cn' => 'Alipay China',
            'alipay_hk' => 'Alipay Hong Kong',
            'alipayplus_mpm' => 'Alipay+ Multi-Party Merchant',
            'alipayplus_upm' => 'Alipay+ Unified Payment',
            'atome' => 'Atome Buy Now Pay Later',
            'atome_qr' => 'Atome QR',
            'barcode_alipay' => 'Alipay Barcode',
            'bill_payment_tesco_lotus' => 'Tesco Lotus Bill Payment',
            'dana' => 'Dana Wallet',
            'direct_debit_bay' => 'Bank of Ayudhya Direct Debit',
            'direct_debit_kbank' => 'Kasikorn Bank Direct Debit',
            'direct_debit_ktb' => 'Krungthai Bank Direct Debit',
            'direct_debit_scb' => 'SCB Direct Debit',
            'gcash' => 'GCash',
            'grabpay' => 'GrabPay',
            'installment_bay' => 'Bank of Ayudhya Installment',
            'installment_bbl' => 'Bangkok Bank Installment',
            'installment_first_choice' => 'First Choice Installment',
            'installment_kbank' => 'Kasikorn Bank Installment',
            'installment_ktc' => 'KTC Installment',
            'installment_paynext_extra_jumpapp' => 'PayNext Extra JumpApp',
            'installment_paynext_extra_qr' => 'PayNext Extra QR',
            'installment_scb' => 'SCB Installment',
            'installment_ttb' => 'TMBThanachart Bank Installment',
            'installment_uob' => 'UOB Installment',
            'kakaopay' => 'KakaoPay',
            'mobile_banking_bay' => 'Bank of Ayudhya Mobile Banking',
            'mobile_banking_bbl' => 'Bangkok Bank Mobile Banking',
            'mobile_banking_kbank' => 'Kasikorn Bank Mobile Banking',
            'mobile_banking_ktb' => 'Krungthai Bank Mobile Banking',
            'mobile_banking_scb' => 'SCB Mobile Banking',
            'promptpay' => 'PromptPay',
            'rabbit_linepay' => 'Rabbit LINE Pay',
            'shopeepay' => 'ShopeePay',
            'shopeepay_jumpapp' => 'ShopeePay JumpApp',
            'touch_n_go' => 'Touch n Go',
            'truemoney' => 'TrueMoney',
            'truemoney_jumpapp' => 'TrueMoney JumpApp',
            'truemoney_qr' => 'TrueMoney QR',
            'wechat_pay' => 'WeChat Pay',
            'wechat_pay_mpm' => 'WeChat Pay Multi-Party Merchant',
            'wechat_pay_upm' => 'WeChat Pay Unified Payment',
            'googlepay' => 'Google Pay',
            'applepay' => 'Apple Pay',
        ];

        return $names[$methodId] ?? ucfirst(str_replace('_', ' ', $methodId));
    }

    private function getPaymentMethodType($methodId): string
    {
        if (strpos($methodId, 'card') !== false) {
            return 'Card Payment';
        }
        if (strpos($methodId, 'mobile_banking') !== false) {
            return 'Mobile Banking';
        }
        if (strpos($methodId, 'direct_debit') !== false) {
            return 'Direct Debit';
        }
        if (strpos($methodId, 'installment') !== false) {
            return 'Installment Payment';
        }
        if (strpos($methodId, 'bill_payment') !== false) {
            return 'Bill Payment';
        }
        if (strpos($methodId, 'barcode') !== false) {
            return 'Barcode Payment';
        }
        if (strpos($methodId, 'qr') !== false) {
            return 'QR Payment';
        }
        if (in_array($methodId, ['promptpay', 'paynow'])) {
            return 'QR Payment';
        }
        if (in_array($methodId, ['alipay', 'alipay_cn', 'alipay_hk', 'alipayplus_mpm', 'alipayplus_upm'])) {
            return 'Alipay Wallet';
        }
        if (in_array($methodId, ['wechat_pay', 'wechat_pay_mpm', 'wechat_pay_upm'])) {
            return 'WeChat Pay';
        }
        if (in_array($methodId, ['truemoney', 'truemoney_jumpapp', 'truemoney_qr'])) {
            return 'TrueMoney Wallet';
        }
        if (in_array($methodId, ['shopeepay', 'shopeepay_jumpapp'])) {
            return 'ShopeePay Wallet';
        }
        if (in_array($methodId, ['googlepay', 'applepay'])) {
            return 'Digital Wallet';
        }
        if (in_array($methodId, ['grabpay', 'rabbit_linepay', 'kakaopay', 'touch_n_go', 'dana', 'gcash'])) {
            return 'Digital Wallet';
        }
        if (in_array($methodId, ['atome', 'atome_qr'])) {
            return 'Buy Now Pay Later';
        }

        return 'Digital Payment';
    }
}
