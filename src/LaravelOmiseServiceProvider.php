<?php

namespace Soap\LaravelOmise;

use Soap\LaravelOmise\Commands\OmiseAccountCommand;
use Soap\LaravelOmise\Commands\OmiseVerifyCommand;
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
                OmiseVerifyCommand::class,
                OmiseAccountCommand::class,
            ]);
    }

    public function packageRegistered()
    {
        $this->app->singleton('omise', function ($app) {
            return new Omise($app->make(OmiseConfig::class));
        });
    }
}
