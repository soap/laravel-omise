<?php

namespace Soap\LaravelOmise\Omise;

use Exception;
use OmiseCharge;
use OmiseRefund;
use Soap\LaravelOmise\Events\ChargeCreated;
use Soap\LaravelOmise\Events\RefundCreated;
use Soap\LaravelOmise\Http\Transport;
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
 * @property int $refunded_amount
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
 * @property string|null $authorize_uri
 * @property array|null $source
 * @property string|null $expires_at
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
     * @return Error|self
     */
    public function find($id)
    {
        try {
            $result = OmiseCharge::retrieve($id, $this->omiseConfig->getPublicKey(), $this->omiseConfig->getSecretKey());
        } catch (Exception $e) {
            return $this->fail([
                'code' => 'api_error',
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }

        if (! $result) {
            return $this->fail([
                'code' => 'not_found',
                'message' => 'Charge not found or API returned null',
            ]);
        }

        $this->refresh($result);

        // Validate that the object was properly loaded with required properties
        if (! $this->hasProperty('id')) {
            return $this->fail([
                'code' => 'invalid_response',
                'message' => 'Charge object was not properly loaded from API response',
            ]);
        }

        return $this;
    }

    /**
     * For compatibility purpose
     *
     * @param  string  $id
     * @return Error|self
     */
    public function retrieve($id)
    {
        return $this->find($id);
    }

    /**
     * Create charge object
     *
     * @param  mixed  $params
     * @return Error|self
     */
    public function create($params)
    {
        try {
            $this->refresh(OmiseCharge::create($params, $this->omiseConfig->getPublicKey(), $this->omiseConfig->getSecretKey()));
        } catch (Exception $e) {
            return $this->fail([
                'code' => 'bad_request',
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }

        event(new ChargeCreated($this));

        return $this;
    }

    /**
     * @return Error|self
     */
    public function capture(array $params = [])
    {
        try {
            $this->fill($this->request('capture', null, $params));
        } catch (Exception $e) {
            return $this->fail([
                'code' => 'failed_capture',
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }

        return $this;
    }

    /**
     * @return Error|OmiseRefund
     *
     * @throws Exception
     */
    public function refund(array $refundData)
    {
        try {
            $refund = new OmiseRefund(
                $this->request('refunds', null, $refundData),
                $this->omiseConfig->getPublicKey(),
                $this->omiseConfig->getSecretKey()
            );
        } catch (Exception $e) {
            return $this->fail([
                'code' => 'failed_refund',
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }

        event(new RefundCreated($this, $refund));

        return $refund;
    }

    /**
     * Expire a pending charge, the loaded one unless an id is given.
     *
     * @param  string|null  $id
     * @return Error|self
     */
    public function expire($id = null)
    {
        try {
            $this->fill($this->request('expire', $id));
        } catch (Exception $e) {
            return $this->fail([
                'code' => 'failed_expire',
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }

        return $this;
    }

    /**
     * Reverse an authorized charge that is not captured yet, the loaded one unless an id is given.
     *
     * @param  string|null  $id
     * @return Error|self
     */
    public function reverse($id = null)
    {
        try {
            $this->fill($this->request('reverse', $id));
        } catch (Exception $e) {
            return $this->fail([
                'code' => 'failed_reverse',
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }

        return $this;
    }

    /**
     * Post an action of a charge (capture, refunds, expire, reverse).
     *
     * @param  string|null  $id
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    private function request(string $action, $id = null, array $params = []): array
    {
        $id = $id ?? $this->id;

        if (! $id) {
            throw new Exception('Charge is not loaded, find it first or pass its id.');
        }

        return Transport::request('POST', "charges/{$id}/{$action}", $this->omiseConfig->getSecretKey(), $params);
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
        $refundedAmount = $this->getRawRefundedAmount();

        if ($refundedAmount === 0) {
            return 0;
        }

        return OmiseMoney::toCurrencyUnit($refundedAmount, $this->currency);
    }

    /**
     * The refunded amount in the smallest unit of the currency.
     */
    public function getRawRefundedAmount(): int
    {
        // Older API versions name it `refunded`, and the `refunds` list is only its first page.
        foreach (['refunded_amount', 'refunded'] as $key) {
            if (is_numeric($this->$key)) {
                return (int) $this->$key;
            }
        }

        $refundedAmount = 0;

        foreach ($this->refunds['data'] ?? [] as $refund) {
            $refundedAmount += (int) ($refund['amount'] ?? 0);
        }

        return $refundedAmount;
    }

    public function isFullyRefunded(): bool
    {
        return $this->amount > 0 && $this->getRawRefundedAmount() >= $this->amount;
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
