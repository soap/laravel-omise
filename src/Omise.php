<?php

namespace Soap\LaravelOmise;

use Soap\LaravelOmise\Omise\Account;
use Soap\LaravelOmise\Omise\Charge;
use Soap\LaravelOmise\Omise\Customer;

class Omise
{
    protected $config;

    public function __construct(OmiseConfig $omiseConfig)
    {
        $this->config = $omiseConfig;
    }

    public function account()
    {
        return new Account($this->config);
    }

    public function charge()
    {
        return new Charge($this->config);
    }

    public function customer()
    {
        return new Customer($this->config);
    }
}
