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
            $result = OmiseCharge::retrieve($id, $this->omiseConfig->getPublicKey(), $this->omiseConfig->getSecretKey());

            if (! $result) {
                return new Error([
                    'code' => 'not_found',
                    'message' => 'Charge not found or API returned null',
                ]);
            }

            $this->refresh($result);

            // Validate that the object was properly loaded
            if (! $this->object || ! isset($this->object['id'])) {
                return new Error([
                    'code' => 'invalid_response',
                    'message' => 'Charge object was not properly loaded from API response',
                ]);
            }

        } catch (Exception $e) {
            return new Error([
                'code' => 'api_error',
                'message' => $e->getMessage(),
            ]);
        }

        return $this;
    }

    /**
     * For compatibility purpose
     *
     * @param  string  $id
     * @return \Soap\LaravelOmise\Omise\Error|self
     */
    public function retrieve($id)
    {
        return $this->find($id);
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
    public function capture(array $params)
    {
        try {
            $this->refresh($this->object->capture($params));
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
    public function refund(array $refundData)
    {
        try {
            $refund = $this->object->refund($refundData);
        } catch (Exception $e) {
            return new Error([
                'code' => 'failed_refund',
                'message' => $e->getMessage(),
            ]);
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

    /**
     * Get debug information about the charge object
     */
    public function getDebugInfo(): array
    {
        $objectKeys = null;
        if ($this->object) {
            if (is_array($this->object)) {
                $objectKeys = array_keys($this->object);
            } else {
                $objectKeys = array_keys(get_object_vars($this->object));
            }
        }

        return [
            'object_loaded' => $this->isLoaded(),
            'object_type' => $this->object ? gettype($this->object) : null,
            'has_id' => $this->hasProperty('id'),
            'has_status' => $this->hasProperty('status'),
            'has_paid' => $this->hasProperty('paid'),
            'object_keys' => $objectKeys,
        ];
    }

    /**
     * Validate that charge has all required properties
     */
    public function isValid(): bool
    {
        return $this->validateProperties(['id', 'status', 'paid', 'amount', 'currency']);
    }
}
