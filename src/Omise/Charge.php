<?php

namespace Soap\LaravelOmise\Omise;

use Exception;
use OmiseCharge;
use OmiseRefund;
use Soap\LaravelOmise\Omise\Helpers\OmiseMoney;
use Soap\LaravelOmise\OmiseConfig;

/**
 * @property object $object
 * @property string $id
 * @property bool $livemode
 * @property string $location
 * @property int $amount
 * @property string $currency
 * @property string $description
 * @property bool $capture
 * @property bool $authorized
 * @property bool $reversed
 * @property bool $captured
 * @property string $transaction
 * @property int $refunded
 * @property array $refunds
 * @property string $failure_code
 * @property string $failure_message
 * @property array $card
 * @property string $customer
 * @property string $ip
 * @property string $dispute
 * @property string $created
 * @property string $paid
 * @property string $status
 * @property array $metadata
 *
 * @method authorizeUri()
 *
 * @see      https://www.omise.co/charges-api
 */
class Charge extends BaseObject
{
    private $omiseConfig;

    public function __construct(OmiseConfig $omiseConfig)
    {
        $this->omiseConfig = $omiseConfig;
    }

    /**
     * @param  string  $id
     * @return \Soap\LaravelOmise\Omise\Error|self
     */
    public function find($id)
    {
        try {
            $this->refresh(OmiseCharge::retrieve($id, $this->omiseConfig->getPublicKey(), $this->omiseConfig->getSecretKey()));
        } catch (Exception $e) {
            return new Error([
                'code' => 'not_found',
                'message' => $e->getMessage(),
            ]);
        }

        return $this;
    }

    /**
     * Create charge object
     *
     * @param  mixed  $params
     * @return \Soap\LaravelOmise\Omise\Error|self
     */
    public function create($params)
    {
        try {
            $this->refresh(OmiseCharge::create($params, $this->omiseConfig->getPublicKey(), $this->omiseConfig->getSecretKey()));
        } catch (Exception $e) {
            return new Error([
                'code' => 'bad_request',
                'message' => $e->getMessage(),
            ]);
        }

        return $this;
    }

    /**
     * @return \Soap\LaravelOmise\Omise\Error|self
     */
    public function capture()
    {
        try {
            $this->refresh($this->object->capture());
        } catch (Exception $e) {
            return new Error([
                'code' => 'failed_capture',
                'message' => $e->getMessage(),
            ]);
        }

        return $this;
    }

    /**
     * @return \Soap\LaravelOmise\Omise\Error|OmiseRefund
     *
     * @throws Exception
     */
    public function refund($refundData)
    {
        try {
            $refund = $this->object->refund($refundData);
        } catch (Exception $e) {
            throw new Exception(__('Failed to refund : '.$e->getMessage()));
        }

        return $refund;
    }

    /**
     * @param  string  $field
     * @return mixed
     */
    public function getMetadata($field)
    {
        return ($this->metadata != null && isset($this->metadata[$field])) ? $this->metadata[$field] : null;
    }

    public function isAuthorized(): bool
    {
        return $this->authorized;
    }

    public function isUnauthorized(): bool
    {
        return ! $this->isAuthorized();
    }

    public function isPaid(): bool
    {
        return $this->paid != null ? $this->paid : $this->captured;
    }

    public function isUnpaid(): bool
    {
        return ! $this->isPaid();
    }

    public function isAwaitCapture(): bool
    {
        return $this->status === 'pending' && $this->isAuthorized() && $this->isUnpaid();
    }

    public function isAwaitPayment(): bool
    {
        return $this->status === 'pending' && $this->isUnauthorized() && $this->isUnpaid();
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'successful' && $this->isPaid();
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function getRawAmount()
    {
        return $this->amount;
    }

    public function getAmount()
    {
        return OmiseMoney::toCurrencyUnit($this->amount, $this->currency);
    }

    public function getRefundedAmount()
    {
        $refundedAmount = 0;

        if (! $this->refunds) {
            return $refundedAmount;
        }

        foreach ($this->refunds['data'] as $refund) {
            $refundedAmount += ($refund['amount'] / 100);
        }

        return $refundedAmount;
    }

    public function isFullyRefunded(): bool
    {
        return (($this->amount / 100) - $this->getRefundedAmount()) === 0;
    }
}
