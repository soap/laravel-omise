<?php

namespace Soap\LaravelOmise;

use Soap\LaravelOmise\Commands\OmiseAccountCommand;
use Soap\LaravelOmise\Commands\OmiseBalanceCommand;
use Soap\LaravelOmise\Commands\OmiseCapabilitiesCommand;
use Soap\LaravelOmise\Commands\OmisePaymentMethodsCommand;
use Soap\LaravelOmise\Commands\OmiseRefundCommand;
use Soap\LaravelOmise\Commands\OmiseVerifyCommand;
use Soap\LaravelOmise\Http\OmiseHttpClient;
use Soap\LaravelOmise\Http\OmiseHttpConfigurator;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelOmiseServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-omise')
            ->hasConfigFile(['omise', 'omise-payment'])
            ->hasCommands([
                OmiseBalanceCommand::class,
                OmiseVerifyCommand::class,
                OmiseAccountCommand::class,
                OmiseCapabilitiesCommand::class,
                OmiseRefundCommand::class,
                OmisePaymentMethodsCommand::class,
            ]);
    }

    public function packageRegistered()
    {
        // Register core services first
        $this->registerCoreServices();

        // Setup HTTP configuration
        $this->setupHttpConfiguration();

        // Register payment services
        $this->registerPaymentServices();

        // Register webhook services (TODO: implement if needed)
        // $this->registerWebhookServices();
    }

    public function packageBooted()
    {
        // Configure HTTP client for all Omise objects
        $this->configureHttpClient();

        // Register custom processors
        $this->registerCustomProcessors();

        // Setup webhook integration
        $this->setupWebhookIntegration();
    }

    /**
     * Register core Omise services
     */
    protected function registerCoreServices(): void
    {
        // Register OmiseConfig
        $this->app->singleton(OmiseConfig::class, function ($app) {
            return new OmiseConfig;
        });

        // Register core Omise service
        $this->app->singleton('omise', function ($app) {
            return new Omise($app->make(OmiseConfig::class));
        });
    }

    /**
     * Setup HTTP configuration services
     */
    protected function setupHttpConfiguration(): void
    {
        // Register HTTP Client
        $this->app->singleton(OmiseHttpClient::class, function ($app) {
            return new OmiseHttpClient($app->make(OmiseConfig::class));
        });

        // Register HTTP Configurator
        $this->app->singleton(OmiseHttpConfigurator::class, function ($app) {
            return new OmiseHttpConfigurator($app->make(OmiseConfig::class));
        });

        // Register HTTP client with alias
        $this->app->singleton('omise.http', function ($app) {
            return $app->make(OmiseHttpClient::class);
        });

        // Register HTTP configurator with alias
        $this->app->singleton('omise.http.configurator', function ($app) {
            return $app->make(OmiseHttpConfigurator::class);
        });
    }

    /**
     * Register payment layer services
     */
    protected function registerPaymentServices(): void
    {
        // Only register if payment config exists
        if (! config('omise-payment')) {
            return;
        }

        // Register Payment Processor Factory
        $this->app->singleton(\Soap\LaravelOmise\Contracts\PaymentProcessorFactoryInterface::class, function ($app) {
            return new \Soap\LaravelOmise\Factories\PaymentProcessorFactory($app->make(OmiseConfig::class));
        });

        // Register Payment Manager
        $this->app->singleton(\Soap\LaravelOmise\PaymentManager::class, function ($app) {
            return new \Soap\LaravelOmise\PaymentManager(
                $app->make(\Soap\LaravelOmise\Contracts\PaymentProcessorFactoryInterface::class)
            );
        });

        // Register payment manager with alias
        $this->app->singleton('omise.payment', function ($app) {
            return $app->make(\Soap\LaravelOmise\PaymentManager::class);
        });
    }

    /**
     * Register webhook services
     */
    protected function registerWebhookIntegration(): void
    {
        // TODO: Implement WebhookIntegrationService
        // if (!config('omise-payment.webhooks.enabled', false)) {
        //     return;
        // }

        // $this->app->singleton(\Soap\LaravelOmise\Services\WebhookIntegrationService::class, function ($app) {
        //     return new \Soap\LaravelOmise\Services\WebhookIntegrationService(
        //         $app->make(\Soap\LaravelOmise\PaymentManager::class)
        //     );
        // });

        // $this->app->singleton('omise.webhooks', function ($app) {
        //     return $app->make(\Soap\LaravelOmise\Services\WebhookIntegrationService::class);
        // });
    }

    /**
     * Configure HTTP client for all Omise objects
     */
    protected function configureHttpClient(): void
    {
        try {
            $configurator = $this->app->make(OmiseHttpConfigurator::class);
            $configurator->configure();

            // Log successful configuration
            if (config('omise.logging.enabled')) {
                \Log::info('Omise HTTP client configured successfully', [
                    'timeout' => config('omise.http.timeout'),
                    'connect_timeout' => config('omise.http.connect_timeout'),
                    'verify_ssl' => config('omise.http.verify_ssl'),
                ]);
            }

        } catch (\Exception $e) {
            // Log configuration error but don't fail the application
            \Log::error('Failed to configure Omise HTTP client', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Register custom payment processors
     */
    protected function registerCustomProcessors(): void
    {
        $customProcessors = config('omise-payment.custom_processors', []);

        if (! empty($customProcessors) && $this->app->bound(\Soap\LaravelOmise\PaymentManager::class)) {
            $paymentManager = $this->app->make(\Soap\LaravelOmise\PaymentManager::class);

            foreach ($customProcessors as $method => $processorClass) {
                $paymentManager->extend($method, $processorClass);
            }
        }
    }

    /**
     * Setup webhook integration
     */
    protected function setupWebhookIntegration(): void
    {
        // TODO: Implement WebhookIntegrationService
        // if (!config('omise-payment.webhooks.enabled', false)) {
        //     return;
        // }

        // if ($this->app->bound(\Soap\LaravelOmise\Services\WebhookIntegrationService::class)) {
        //     $webhookService = $this->app->make(\Soap\LaravelOmise\Services\WebhookIntegrationService::class);
        //     $endpoints = $webhookService->registerWebhookEndpoints();

        //     // Store endpoints in config for other packages to use
        //     config(['omise-internal.webhook_endpoints' => $endpoints]);
        // }
    }

    /**
     * Check if HTTP configuration is valid
     */
    protected function validateHttpConfiguration(): bool
    {
        $httpConfig = config('omise.http', []);

        // Check required settings
        if (! isset($httpConfig['timeout']) || $httpConfig['timeout'] <= 0) {
            return false;
        }

        if (! isset($httpConfig['connect_timeout']) || $httpConfig['connect_timeout'] <= 0) {
            return false;
        }

        return true;
    }
}
