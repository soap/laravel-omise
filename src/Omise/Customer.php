<?php

namespace Soap\LaravelOmise\Omise;

use Exception;
use OmiseCardList;
use OmiseCustomer;
use Soap\LaravelOmise\Http\Transport;
use Soap\LaravelOmise\OmiseConfig;

/**
 * @property-read string $id
 * @property-read bool $livemode
 * @property-read string $location
 * @property-read string $email
 * @property-read string $description
 * @property-read string $default_card
 * @property-read array $cards
 * @property-read array $metadata
 * @property-read string $created_at
 *
 * @see      https://docs.omise.co/customers-api
 */
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

    /**
     * @param  string  $id
     * @return Error|self
     */
    public function find($id)
    {
        try {
            $this->refresh(OmiseCustomer::retrieve($id, $this->omiseConfig->getPublicKey(), $this->omiseConfig->getSecretKey()));
        } catch (Exception $e) {
            return $this->fail([
                'code' => 'not_found',
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }

        return $this;
    }

    /**
     * @param  array  $params
     * @return Error|self
     */
    public function create($params)
    {
        try {
            $this->refresh(OmiseCustomer::create($params, $this->omiseConfig->getPublicKey(), $this->omiseConfig->getSecretKey()));
        } catch (Exception $e) {
            return $this->fail([
                'code' => 'bad_request',
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }

        return $this;
    }

    /**
     * Update the loaded customer, or the customer of the given id without loading it first.
     *
     * @param  array  $params  pass a token as `card` to attach another card
     * @param  string|null  $id
     * @return Error|self
     */
    public function update($params, $id = null)
    {
        try {
            $this->fill($this->request('PATCH', $this->path($id), $params));
        } catch (Exception $e) {
            return $this->fail([
                'code' => 'bad_request',
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }

        return $this;
    }

    /**
     * Delete a card of the loaded customer, or of the customer of the given id.
     *
     * @param  string  $cardId
     * @param  string|null  $customerId
     * @return Error|self
     */
    public function deleteCard($cardId, $customerId = null)
    {
        try {
            $this->request('DELETE', $this->path($customerId)."/cards/{$cardId}");
        } catch (Exception $e) {
            return $this->fail([
                'code' => 'bad_request',
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }

        return $this;
    }

    /**
     * Cards of the loaded customer, pass options (limit, offset, order...) to list them from the API.
     *
     * @param  array  $options
     * @return OmiseCardList
     */
    public function cards($options = [])
    {
        $cards = is_array($options) && ! empty($options)
            ? $this->request('GET', $this->path().'/cards?'.http_build_query($options))
            : $this->cards;

        return new OmiseCardList($cards, $this->id, $this->omiseConfig->getPublicKey(), $this->omiseConfig->getSecretKey());
    }

    /**
     * @param  string|null  $id
     *
     * @throws Exception
     */
    private function path($id = null): string
    {
        $id = $id ?? $this->id;

        if (! $id) {
            throw new Exception('Customer is not loaded, find it first or pass its id.');
        }

        return "customers/{$id}";
    }

    /**
     * @param  array<string, mixed>|null  $params
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    private function request(string $method, string $path, $params = null): array
    {
        return Transport::request($method, $path, $this->omiseConfig->getSecretKey(), $params);
    }

    /**
     * Convert to array representation
     */
    public function toArray(): array
    {
        // Every attribute of the API, these keys are always present.
        return array_merge([
            'id' => $this->id ?? null,
            'object' => $this->getProperty('object', 'customer'),
            'livemode' => $this->livemode ?? false,
            'location' => $this->location ?? null,
            'email' => $this->email ?? null,
            'description' => $this->description ?? null,
            'default_card' => $this->default_card ?? null,
            'metadata' => $this->metadata ?? [],
            'created_at' => $this->created_at ?? null,
        ], $this->attributes());
    }
}
