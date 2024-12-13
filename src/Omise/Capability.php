<?php

namespace Soap\LaravelOmise\Omise;

use Exception;
use OmiseCapabilities;
use Soap\LaravelOmise\OmiseConfig;

class Customer extends BaseObject
{
    private $omiseConfig;

    /**
     * Injecting dependencies
     */
    public function __construct(OmiseConfig $omiseConfig)
    {
        $this->omiseConfig = $omiseConfig;
    }

    public function retrieve()
    {
        try {
            $this->refresh(OmiseCapabilities::retrieve($this->omiseConfig->getPublicKey(), $this->omiseConfig->getSecretKey()));
        } catch (Exception $e) {
            return new Error([
                'code' => 'not_found',
                'message' => $e->getMessage(),
            ]);
        }

        return $this;
    }
}
