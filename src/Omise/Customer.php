<?php

namespace Soap\LaravelOmise\Omise;

use Exception;
use OmiseCustomer;
use Soap\LaravelOmise\OmiseConfig;

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
     * @return \Soap\LaravelOmise\Omise\Error|self
     */
    public function find($id)
    {
        try {
            $this->refresh(OmiseCustomer::retrieve($id, $this->omiseConfig->getPublicKey(), $this->omiseConfig->getSecretKey()));
        } catch (Exception $e) {
            return new Error([
                'code' => 'not_found',
                'message' => $e->getMessage(),
            ]);
        }

        return $this;
    }

    /**
     * @param  array  $params
     * @return \Soap\LaravelOmise\Omise\Error|self
     */
    public function create($params)
    {
        try {
            $this->refresh(OmiseCustomer::create($params, $this->omiseConfig->getPublicKey(), $this->omiseConfig->getSecretKey()));
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
