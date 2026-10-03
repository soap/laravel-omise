<?php

namespace Soap\LaravelOmise;

use Soap\LaravelOmise\Commands\OmiseAccountCommand;
use Soap\LaravelOmise\Commands\OmiseBalanceCommand;
use Soap\LaravelOmise\Commands\OmiseCapabilitiesCommand;
use Soap\LaravelOmise\Commands\OmiseRefundCommand;
use Soap\LaravelOmise\Commands\OmiseVerifyCommand;
use Soap\LaravelOmise\Contracts\PaymentProcessorFactoryInterface;
use Soap\LaravelOmise\Factories\PaymentProcessorFactory;
use Soap\LaravelOmise\Http\Transport;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelOmiseServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-omise')
            ->hasConfigFile()
            ->hasCommands([
                OmiseBalanceCommand::class,
                OmiseVerifyCommand::class,
                OmiseAccountCommand::class,
                OmiseCapabilitiesCommand::class,
                OmiseRefundCommand::class,
            ]);
    }

    public function packageRegistered()
    {
        $this->app->singleton(Omise::class, function ($app) {
            return new Omise($app->make(OmiseConfig::class));
        });

        $this->app->alias(Omise::class, 'omise');

        $this->app->singleton(PaymentProcessorFactoryInterface::class, function ($app) {
            return new PaymentProcessorFactory($app->make(Omise::class));
        });

        $this->app->singleton(PaymentManager::class, function ($app) {
            return new PaymentManager($app->make(Omise::class), $app->make(PaymentProcessorFactoryInterface::class));
        });
    }

    public function packageBooted()
    {
        // The curl executor of the SDK reads the API version from this constant.
        if (! defined('OMISE_API_VERSION') && filled(config('omise.api_version'))) {
            define('OMISE_API_VERSION', (string) config('omise.api_version'));
        }

        Transport::useDriver(config('omise.http.driver'));
    }
}
