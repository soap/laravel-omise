<?php

namespace Soap\LaravelOmise\Omise;

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
        return date('Y-m-d H:i:s', $this->created_at);
    }
}
