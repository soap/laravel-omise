<?php

namespace Soap\LaravelOmise\Omise;

use Carbon\Carbon;
use Soap\LaravelOmise\Http\OmiseHttpClient;
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

    private static $httpClient;

    public function __construct(OmiseConfig $omiseConfig)
    {
        $this->omiseConfig = $omiseConfig;

        if (! self::$httpClient) {
            self::$httpClient = new OmiseHttpClient($omiseConfig);
            \Soap\LaravelOmise\Http\EnhancedOmiseBalance::setHttpClient(self::$httpClient);
        }
    }

    public function retrieve($params = [])
    {
        try {
            // Use the already initialized HTTP client
            $this->refresh(\Soap\LaravelOmise\Http\EnhancedOmiseBalance::retrieve($params));
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
}
