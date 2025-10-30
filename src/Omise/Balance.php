<?php

namespace Soap\LaravelOmise\Omise;

use Carbon\Carbon;
use OmiseBalance;
use Soap\LaravelOmise\Omise\Helpers\OmiseMoney;
use Soap\LaravelOmise\OmiseConfig;

/**
 * @property int $total
 * @property int $transferable
 * @property int $reserve
 * @property int $on_hold
 * @property string $currency
 * @property string $object
 * @property string $id
 * @property string $livemode
 * @property string $location
 * @property string $created_at
 */
class Balance extends BaseObject
{
    private $omiseConfig;

    public function __construct(OmiseConfig $omiseConfig)
    {
        $this->omiseConfig = $omiseConfig;
    }

    /**
     * Retrieve balance information
     *
     * @return \Soap\LaravelOmise\Omise\Error|self
     */
    public function retrieve()
    {
        try {
            $this->refresh(OmiseBalance::retrieve($this->omiseConfig->getPublicKey(), $this->omiseConfig->getSecretKey()));
        } catch (\Exception $e) {
            return new Error([
                'code' => 'not_found',
                'message' => $e->getMessage(),
            ]);
        }

        return $this;
    }

    public function getTransferableAmount()
    {
        return OmiseMoney::toCurrencyUnit($this->transferable, $this->currency);
    }

    public function getReservedAmount()
    {
        return OmiseMoney::toCurrencyUnit($this->reserve, $this->currency);
    }

    public function getTotalAmount()
    {
        return OmiseMoney::toCurrencyUnit($this->total, $this->currency);
    }

    public function getOnHoldAmount()
    {
        return OmiseMoney::toCurrencyUnit($this->on_hold, $this->currency);
    }

    public function getCreatedAt()
    {
        return Carbon::parse($this->created_at);
    }

    /**
     * Convert to array representation
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'object' => $this->object,
            'livemode' => $this->livemode,
            'location' => $this->location ?? null,
            'total' => $this->total,
            'available' => $this->available ?? null,
            'currency' => $this->currency,
            'transferable' => $this->transferable,
            'reserve' => $this->reserve,
            'on_hold' => $this->on_hold ?? null,
            'created_at' => $this->created_at ?? null,
        ];
    }
}
