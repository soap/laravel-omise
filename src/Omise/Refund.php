<?php

namespace Soap\LaravelOmise\Omise;

use Exception;
use OmiseRefund;
use Soap\LaravelOmise\Omise\BaseObject;
use Soap\LaravelOmise\Omise\Error;

class Refund extends BaseObject
{
    private $omiseConfig;

    /**
     * Injecting dependencies
     */
    public function __construct(OmiseConfig $omiseConfig)
    {
        $this->omiseConfig = $omiseConfig;
    }

    public function refund(array $refundData)
    {
        try {
            $this->refresh(new OmiseRefund($refundData, $this->omiseConfig->getPublicKey(), $this->omiseConfig->getSecretKey()));
        } catch (Exception $e) {
            return new Error([
                'code' => 'not_found',
                'message' => $e->getMessage(),
            ]);
        }

        return $this;
    }

    public function search(string $query)
    {
        try {
            $this->refresh(OmiseRefund::search($query, $this->omiseConfig->getPublicKey(), $this->omiseConfig->getSecretKey()));
        } catch (Exception $e) {
            return new Error([
                'code' => 'not_found',
                'message' => $e->getMessage(),
            ]);
        }

        return $this;
    }
}
