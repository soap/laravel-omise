<?php

namespace Soap\LaravelOmise\Omise;

use Exception;
use Soap\LaravelOmise\Http\OmiseHttpClient;
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

    private static $httpClient;

    public function __construct(OmiseConfig $omiseConfig)
    {
        $this->omiseConfig = $omiseConfig;

        if (! self::$httpClient) {
            self::$httpClient = new OmiseHttpClient($omiseConfig);
            \Soap\LaravelOmise\Http\EnhancedOmiseSource::setHttpClient(self::$httpClient);
        }
    }

    public function create($params)
    {
        try {
            $source = \Soap\LaravelOmise\Http\EnhancedOmiseSource::create(
                $params,
                $this->omiseConfig->getPublicKey(),
                $this->omiseConfig->getSecretKey()
            );

            $this->refresh($source);

        } catch (Exception $e) {
            return new Error([
                'code' => 'bad_request',
                'message' => $e->getMessage(),
            ]);
        }

        return $this;
    }

    public function retrieve($sourceId)
    {
        try {
            // Use the already initialized HTTP client
            $this->refresh(\Soap\LaravelOmise\Http\EnhancedOmiseSource::retrieve($sourceId));
        } catch (Exception $e) {
            return new Error([
                'code' => 'not_found',
                'message' => $e->getMessage(),
            ]);
        }

        return $this;
    }
}
