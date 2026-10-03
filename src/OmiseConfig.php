<?php

namespace Soap\LaravelOmise;

class OmiseConfig
{
    /**
     * Keys used instead of the configured ones, see withKeys().
     *
     * @var array{public: string, secret: string}|null
     */
    private $keys = null;

    /**
     * A configuration for another Omise account, the configured keys are left untouched.
     */
    public function withKeys(string $publicKey, string $secretKey): self
    {
        $config = clone $this;
        $config->keys = ['public' => $publicKey, 'secret' => $secretKey];

        return $config;
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
        // Initialize only if both keys are present
        return $this->getPublicKey() !== '' && $this->getSecretKey() !== '';
    }

    public function getUrl(): string
    {
        return (string) config('omise.url');
    }

    public function isSandboxEnabled()
    {
        if ($this->keys !== null) {
            return str_contains($this->keys['secret'], '_test_');
        }

        return config('omise.sandbox_status');
    }

    public function getPublicKey(): string
    {
        if ($this->keys !== null) {
            return $this->keys['public'];
        }

        if ($this->isSandboxEnabled()) {
            return (string) config('omise.test_public_key');
        }

        return (string) config('omise.live_public_key');
    }

    public function getSecretKey(): string
    {
        if ($this->keys !== null) {
            return $this->keys['secret'];
        }

        if ($this->isSandboxEnabled()) {
            return (string) config('omise.test_secret_key');
        }

        return (string) config('omise.live_secret_key');
    }
}
