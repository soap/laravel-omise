<?php

namespace Soap\LaravelOmise\Omise;

use Exception;
use Soap\LaravelOmise\OmiseConfig;

/**
 * @property-read object $object
 * @property-read string $id
 * @property-read bool $zero_interest_installments
 * @property-read array $limits
 */
class Capabilities extends BaseObject
{
    private $omiseConfig;

    /**
     * Injecting dependencies
     */
    public function __construct(OmiseConfig $omiseConfig)
    {
        $this->omiseConfig = $omiseConfig;
    }

    public function retrieve()
    {
        try {
            // Use fully qualified class name to avoid PHPStan errors
            $this->refresh(\OmiseCapabilities::retrieve($this->omiseConfig->getPublicKey(), $this->omiseConfig->getSecretKey()));
        } catch (Exception $e) {
            return new Error([
                'code' => 'not_found',
                'message' => $e->getMessage(),
            ]);
        }

        return $this;
    }

    private function shouldCallApi()
    {
        return $this->object == null;
    }

    private function ensureDataExists()
    {
        if ($this->shouldCallApi()) {
            $this->retrieve();
        }
    }

    /**
     * Retrieves details of installment payment backends from capabilities.
     *
     * @return array
     */
    public function getInstallmentBackends($currency = '', $amount = null)
    {
        $this->ensureDataExists();
        $params = [];
        $params[] = $this->object->backendFilter['type']('installment');

        if ($currency) {
            $params[] = $this->object->backendFilter['currency']($currency);
        }
        if (! is_null($amount)) {
            $params[] = $this->object->backendFilter['chargeAmount']($amount);
        }

        return $this->getBackends($params);
    }

    /**
     * Retrieves details of payment backends from capabilities.
     *
     * @return array
     */
    public function getBackends($currency = '')
    {
        $this->ensureDataExists();
        $params = [];
        if ($currency) {
            $params[] = $this->object->backendFilter['currency']($currency);
        }

        return $this->getBackends($params);
    }

    /**
     * Retrieves backend by type
     */
    public function getBackendByType($sourceType)
    {
        $this->ensureDataExists();
        $params = [];
        $params[] = $this->object->backendFilter['type']($sourceType);
        $backend = $this->getBackends($params);

        // Only variables hould be passed
        // https://www.php.net/reset
        return reset($backend);
    }

    /**
     * Retrieves details of fpx bank list from capabilities.
     */
    public function getFPXBanks()
    {
        return $this->getBackendByType('fpx');
    }

    /**
     * Retrieves list of tokenization methods
     *
     * @return array
     */
    public function getTokenizationMethods()
    {
        return $this->tokenization_methods ?? null;
    }

    /**
     * @return bool True if merchant absorbs the interest or else, false.
     */
    public function isZeroInterest()
    {
        return $this->zero_interest_installments;
    }

    /**
     * @return array list of omise backends sourc_type.
     */
    public function getAavailablePaymentMethods()
    {
        $backends = $this->getBackends();
        $backends = json_decode(json_encode($backends), true);
        $token_methods = $this->getTokenizationMethods();

        return array_merge(array_column($backends, '_id'), $token_methods);
    }

    public function getInstallmentMinLimit()
    {
        return $this->limits['installment_amount']['min'];
    }

    public function getChargeAmountMinLimit()
    {
        return $this->limits['charge_amount']['min'];
    }

    public function getChargeAmountMaxLimit()
    {
        return $this->limits['charge_amount']['max'];
    }
}
