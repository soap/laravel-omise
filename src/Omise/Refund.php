<?php

namespace Soap\LaravelOmise\Omise;

use Exception;
use OmiseRefund;
use Soap\LaravelOmise\OmiseConfig;

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

    /**
     * Convert to array representation
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id ?? null,
            'object' => $this->object ?? 'refund',
            'livemode' => $this->livemode ?? false,
            'location' => $this->location ?? null,
            'amount' => $this->amount ?? null,
            'currency' => $this->currency ?? null,
            'charge' => $this->charge ?? null,
            'transaction' => $this->transaction ?? null,
            'status' => $this->status ?? null,
            'metadata' => $this->metadata ?? [],
            'created_at' => $this->created_at ?? null,
        ];
    }
}
