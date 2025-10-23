<?php

namespace Soap\LaravelOmise;

class OmiseConfig
{
    private $canInitialize = false;

    public function __construct()
    {
        $this->init();
    }

    private function init(): void
    {
        // Initialize only if both keys are present
        if ($this->getPublicKey() && $this->getSecretKey()) {
            $this->canInitialize = true;
        }
    }

    public function validate(): array
    {
        $errors = [];

        if (empty($this->getPublicKey())) {
            $errors[] = 'Public key is missing';
        }

        if (empty($this->getSecretKey())) {
            $errors[] = 'Secret key is missing';
        }

        if (empty($this->getUrl())) {
            $errors[] = 'API URL is missing';
        }

        return $errors;
    }

    public function isValid(): bool
    {
        return empty($this->validate());
    }

    public function canInitialize()
    {
        return $this->canInitialize;
    }

    public function getUrl(): string
    {
        return config('omise.url');
    }

    public function isSandboxEnabled()
    {
        return config('omise.sandbox_status');
    }

    public function getPublicKey(): string
    {
        if ($this->isSandboxEnabled()) {
            return config('omise.test_public_key');
        }

        return config('omise.live_public_key');
    }

    public function getSecretKey(): string
    {
        if ($this->isSandboxEnabled()) {
            return config('omise.test_secret_key');
        }

        return config('omise.live_secret_key');
    }
}
