<?php

namespace Soap\LaravelOmise\Commands;

use Illuminate\Console\Command;

class OmisePaymentMethodsCommand extends Command
{
    public $signature = 'omise:payment-methods {--validate=} {--params=}';

    public $description = 'List supported payment methods and their parameters';

    public function handle(): int
    {
        $paymentManager = app(\Soap\LaravelOmise\PaymentManager::class);

        // Validate specific payment method if provided
        if ($validateMethod = $this->option('validate')) {
            return $this->validatePaymentMethod($validateMethod, $paymentManager);
        }

        // Show parameters for specific method if provided
        if ($paramsMethod = $this->option('params')) {
            return $this->showPaymentMethodParams($paramsMethod, $paymentManager);
        }

        // Show all supported payment methods
        $this->showAllPaymentMethods($paymentManager);

        return self::SUCCESS;
    }

    protected function showAllPaymentMethods($paymentManager)
    {
        $this->line('Supported Payment Methods:', 'info');
        $this->line('');

        $methods = $paymentManager->getSupportedMethods();
        $tableData = [];

        foreach ($methods as $method) {
            try {
                $processor = $paymentManager->getProcessor($method);
                $requiredParams = $processor->getRequiredParams();

                $tableData[] = [
                    'Method' => $method,
                    'Required Parameters' => implode(', ', $requiredParams),
                    'Type' => class_basename(get_class($processor)),
                ];
            } catch (\Exception $e) {
                $tableData[] = [
                    'Method' => $method,
                    'Required Parameters' => 'Error: '.$e->getMessage(),
                    'Type' => 'Unknown',
                ];
            }
        }

        $this->table(['Method', 'Required Parameters', 'Type'], $tableData);

        $this->line('');
        $this->line('Usage examples:');
        $this->line('  php artisan omise:payment-methods --params=credit_card');
        $this->line('  php artisan omise:payment-methods --validate=promptpay');
    }

    protected function showPaymentMethodParams($method, $paymentManager)
    {
        if (! $paymentManager->supports($method)) {
            $this->error("Payment method '{$method}' is not supported.");

            return self::FAILURE;
        }

        try {
            $processor = $paymentManager->getProcessor($method);
            $requiredParams = $processor->getRequiredParams();

            $this->line("Payment Method: {$method}", 'info');
            $this->line('Processor: '.class_basename(get_class($processor)));
            $this->line('');

            $this->line('Required Parameters:', 'comment');
            foreach ($requiredParams as $param) {
                $this->line("  - {$param}");
            }

            $this->line('');
            $this->showExampleUsage($method, $requiredParams);

        } catch (\Exception $e) {
            $this->error("Error getting parameters for '{$method}': ".$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function validatePaymentMethod($method, $paymentManager)
    {
        if (! $paymentManager->supports($method)) {
            $this->error("Payment method '{$method}' is not supported.");

            $this->line('');
            $this->line('Supported methods:');
            foreach ($paymentManager->getSupportedMethods() as $supportedMethod) {
                $this->line("  - {$supportedMethod}");
            }

            return self::FAILURE;
        }

        $this->line("✓ Payment method '{$method}' is supported", 'info');

        try {
            $processor = $paymentManager->getProcessor($method);
            $this->line('✓ Processor class exists: '.get_class($processor));
            $this->line('✓ Required parameters: '.implode(', ', $processor->getRequiredParams()));
        } catch (\Exception $e) {
            $this->error('✗ Error creating processor: '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function showExampleUsage($method, $requiredParams)
    {
        $this->line('Example Usage:', 'comment');

        $examples = [
            'credit_card' => [
                'amount' => 100.00,
                'currency' => 'THB',
                'card' => 'tokn_test_xxx',
            ],
            'promptpay' => [
                'amount' => 150.00,
                'currency' => 'THB',
            ],
        ];

        $example = $examples[$method] ?? array_fill_keys($requiredParams, 'value');

        $this->line('');
        $this->line('$params = [');
        foreach ($example as $key => $value) {
            if (is_string($value)) {
                $this->line("    '{$key}' => '{$value}',");
            } else {
                $this->line("    '{$key}' => {$value},");
            }
        }
        $this->line('];');
        $this->line('');
        $this->line("\$result = app('omise')->processPayment('{$method}', \$params);");
    }
}
