<?php

namespace Soap\LaravelOmise\Omise;

use Exception;
use Soap\LaravelOmise\Http\OmiseHttpClient;
use Soap\LaravelOmise\OmiseConfig;

class Customer extends BaseObject
{
    private $omiseConfig;

    private static $httpClient;

    public function __construct(OmiseConfig $omiseConfig)
    {
        $this->omiseConfig = $omiseConfig;

        if (! self::$httpClient) {
            self::$httpClient = new OmiseHttpClient($omiseConfig);
            \Soap\LaravelOmise\Http\EnhancedOmiseCustomer::setHttpClient(self::$httpClient);
        }
    }

    public function retrieve($customerId)
    {
        try {
            // Use the already initialized HTTP client
            $this->refresh(\Soap\LaravelOmise\Http\EnhancedOmiseCustomer::retrieve($customerId));
        } catch (Exception $e) {
            return new Error([
                'code' => 'not_found',
                'message' => $e->getMessage(),
            ]);
        }

        return $this;
    }

    public function create($params)
    {
        try {
            $customer = \Soap\LaravelOmise\Http\EnhancedOmiseCustomer::create(
                $params,
                $this->omiseConfig->getPublicKey(),
                $this->omiseConfig->getSecretKey()
            );

            $this->refresh($customer);

        } catch (Exception $e) {
            return new Error([
                'code' => 'bad_request',
                'message' => $e->getMessage(),
            ]);
        }

        return $this;
    }

    /**
     * @param  array  $params
     * @return \Soap\LaravelOmise\Omise\Error|self
     */
    public function update($params)
    {
        try {
            $this->object->update($params);
            $this->refresh($this->object);
        } catch (Exception $e) {
            return new Error([
                'code' => 'bad_request',
                'message' => $e->getMessage(),
            ]);
        }

        return $this;
    }

    /**
     * TODO: Need to refactor a bit
     */
    public function cards($options = [])
    {
        return $this->object->cards($options);
    }
}
