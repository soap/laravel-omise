<?php

namespace Soap\LaravelOmise\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Soap\LaravelOmise\LaravelOmiseServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelOmiseServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        // config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_laravel-omise_table.php.stub';
        $migration->up();
        */
    }
}
