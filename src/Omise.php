<?php

namespace Soap\LaravelOmise;

use Soap\LaravelOmise\Omise\Account;
use Soap\LaravelOmise\Omise\Capabilities;
use Soap\LaravelOmise\Omise\Charge;
use Soap\LaravelOmise\Omise\Customer;
use Soap\LaravelOmise\Omise\Source;

class Omise
{
    protected $config;

    public function __construct(OmiseConfig $omiseConfig)
    {
        $this->config = $omiseConfig;
    }

    public function validConfig()
    {
        return $this->config->canInitialize();
    }

    public function liveMode()
    {
        return ! $this->config->isSandboxEnabled();
    }

    public function getPublicKey()
    {
        return $this->config->getPublicKey();
    }

    public function getSecretKey()
    {
        return $this->config->getSecretKey();
    }

    public function account()
    {
        return new Account($this->config);
    }

    public function capabilities()
    {
        return new Capabilities($this->config);
    }
    
    public function charge()
    {
        return new Charge($this->config);
    }

    public function customer()
    {
        return new Customer($this->config);
    }

    public function source()
    {
        return new Source($this->config);
    }
}
