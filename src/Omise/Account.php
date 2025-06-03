<?php

namespace Soap\LaravelOmise\Omise;

use OmiseAccount;
use Soap\LaravelOmise\OmiseConfig;

/**
 * @property-read string $id
 * @property-read string $team
 * @property-read string $email
 * @property-read bool $livemode
 * @property-read string $location
 * @property-read string $webhook_uri
 * @property-read string $country
 * @property-read string $api_version
 * @property-read string $currency
 * @property-read array $supported_currencies
 * @property-read bool $auto_activate_recipients
 * @property-read bool $chain_enabled
 * @property-read bool $chaining_allowed
 * @property-read string $created_at
 */
class Account extends BaseObject
{
    private $omiseConfig;

    public function __construct(OmiseConfig $omiseConfig)
    {
        $this->omiseConfig = $omiseConfig;
    }

    /**
     * Retrieve account information
     *
     * @return \Soap\LaravelOmise\Omise\Error|self
     */
    public function retrieve()
    {
        try {
            $this->refresh(OmiseAccount::retrieve($this->omiseConfig->getPublicKey(), $this->omiseConfig->getSecretKey()));
        } catch (\Exception $e) {
            return new Error([
                'code' => 'not_found',
                'message' => $e->getMessage(),
            ]);
        }

        return $this;
    }

    /**
     * Update webhook URI for the account
     *
     * @param string $uri
     * @return \Soap\LaravelOmise\Omise\Error|self
     */
    public function updateWebhookUri($uri)
    {
        try {
            $omiseAccount = OmiseAccount::retrieve($this->omiseConfig->getPublicKey(), $this->omiseConfig->getSecretKey());
            $omiseAccount->update([
                'webhook_uri' => $uri
            ]);
            $this->refresh($omiseAccount);
        } catch (\Exception $e) {
            return new Error([
                'code' => 'bad_request', 
                'message' => $e->getMessage(),
            ]);
        }

        return $this;
    }


    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'team' => $this->team,
            'email' => $this->email,
            'livemode' => $this->livemode,
            'location' => $this->location,
            'webhook_uri' => $this->webhook_uri,
            'country' => $this->country,
            'api_version' => $this->api_version,
            'currency' => $this->currency,
            'supported_currencies' => $this->supported_currencies,
            'auto_activate_recipients' => $this->auto_activate_recipients ?? false,
            'zero_interest_installments' => $this->zero_interest_installments ?? false,
            'metadata_export_keys' => $this->metadata_export_keys ?? [],
            'chain_return_uri' => $this->chain_return_uri ?? null,
            'chain_enabled' => $this->chain_enabled ?? false,
            'chaining_allowed' => $this->chaining_allowed ?? false,
            'last_updated_api_version' => $this->last_updated_api_version ?? null,
            'transfer_config' => $this->transfer_config ?? [],
            'created_at' => $this->created_at,
        ];
    }
}
