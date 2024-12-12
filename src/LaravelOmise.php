<?php

namespace Soap\LaravelOmise;

class LaravelOmise
{
    protected static $url;

    protected static $public_key;

    protected static $secret_key;

    private $canInitialize = false;

    public function __construct()
    {
        $this->init();
    }

    private function init()
    {
        // Initialize only if both keys are present
        if ($this->getPublicKey() && $this->getSecretKey()) {
            $this->canInitialize = true;
        }
    }

    public function canInitialize()
    {
        return $this->canInitialize;
    }

    public function getUrl()
    {
        return config('omise.url');
    }

    public function isSandboxEnabled()
    {
        return config('omise.sandbox_status');
    }

    public function getPublicKey()
    {
        if ($this->isSandboxEnabled()) {
            return config('omise.test_public_key');
        }

        return config('omise.live_public_key');
    }

    public function getSecretKey()
    {
        if ($this->isSandboxEnabled()) {
            return config('omise.test_secret_key');
        }

        return config('omise.live_secret_key');
    }
}
