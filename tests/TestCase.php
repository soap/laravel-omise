<?php

namespace Soap\LaravelOmise\Tests;

use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected $loadEnvironmentVariables = true;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            'Soap\LaravelOmise\LaravelOmiseServiceProvider',
        ];
    }

    protected function defineEnvironment($app)
    {
        // ตรวจสอบว่า .env โหลดหรือไม่
        if (! env('OMISE_SANDBOX_STATUS')) {
            // ถ้าไม่โหลด ให้โหลดเอง
            if (file_exists(__DIR__.'/../../.env')) {
                $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__.'/../..');
                $dotenv->load();
            }
        }
    }
}
