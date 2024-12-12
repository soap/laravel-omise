<?php

namespace Soap\LaravelOmise\Omise;

use OmiseAccount;
use Soap\LaravelOmise\OmiseConfig;

class Account extends BaseObject
{
    private $omiseConfig;

    public function __construct(OmiseConfig $omiseConfig)
    {
        $this->omiseConfig = $omiseConfig;
    }

    /**
     * Retrieve account information
     *
     * @return \Soap\LaravelOmise\Omise\Error|self
     */
    public function retrieve()
    {
        try {
            $this->refresh(OmiseAccount::retrieve($this->omiseConfig->getPublicKey(), $this->omiseConfig->getSecretKey()));
        } catch (\Exception $e) {
            return new Error([
                'code' => 'not_found',
                'message' => $e->getMessage(),
            ]);
        }

        return $this;
    }
}
