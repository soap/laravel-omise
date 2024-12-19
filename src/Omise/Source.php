<?php

namespace Soap\LaravelOmise\Omise;

use Exception;
use OmiseSource;
use Soap\LaravelOmise\OmiseConfig;

/**
 * @property object $object
 * @property string $id
 * @property bool $zero_interest_installments
 * @property array $installment_terms
 * @property array $payment_methods
 * @property array $references
 * @property array $flow
 * @property array $amount
 * @property array $currency
 * @property array $barcode
 */
class Source extends BaseObject
{
    private $omiseConfig;

    /**
     * Injecting dependencies
     */
    public function __construct(OmiseConfig $omiseConfig)
    {
        $this->omiseConfig = $omiseConfig;
    }

    public function create($params)
    {
        try {
            $this->refresh(OmiseSource::create($params, $this->omiseConfig->getPublicKey(), $this->omiseConfig->getSecretKey()));
        } catch (Exception $e) {
            return new Error([
                'code' => 'bad_request',
                'message' => $e->getMessage(),
            ]);
        }

        return $this;
    }

    public function retrieve($id)
    {
        try {
            $this->refresh(OmiseSource::retrieve($id, $this->omiseConfig->getPublicKey(), $this->omiseConfig->getSecretKey()));
        } catch (Exception $e) {
            return new Error([
                'code' => 'not_found',
                'message' => $e->getMessage(),
            ]);
        }

        return $this;
    }
}
