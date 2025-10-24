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
            $this->refresh(\OmiseCapability::retrieve($this->omiseConfig->getPublicKey(), $this->omiseConfig->getSecretKey()));
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

        if (! $this->object || ! isset($this->object['payment_methods'])) {
            return [];
        }

        $paymentMethods = $this->object['payment_methods'];

        // Filter for installment methods
        $installmentMethods = array_filter($paymentMethods, function ($method) {
            return strpos($method['name'], 'installment_') === 0 &&
                   ! empty($method['installment_terms']);
        });

        // Apply currency filter
        if ($currency) {
            $installmentMethods = array_filter($installmentMethods, function ($method) use ($currency) {
                return in_array(strtoupper($currency), $method['currencies'] ?? []);
            });
        }

        // Apply amount filter based on limits
        if (! is_null($amount) && isset($this->object['limits']['installment_amount']['min'])) {
            $minAmount = $this->object['limits']['installment_amount']['min'];
            if ($amount < $minAmount) {
                return []; // Amount too low for installments
            }
        }

        return array_values($installmentMethods);
    }

    /**
     * Retrieves details of payment backends from capabilities.
     *
     * @return array
     */
    public function getBackends($currency = '')
    {
        $this->ensureDataExists();

        if (! $this->object || ! isset($this->object['payment_methods'])) {
            return [];
        }

        $paymentMethods = $this->object['payment_methods'];

        if ($currency) {
            return array_filter($paymentMethods, function ($method) use ($currency) {
                return in_array(strtoupper($currency), $method['currencies'] ?? []);
            });
        }

        return $paymentMethods;
    }

    /**
     * Get all payment backends (alias for getBackends)
     *
     * @return array
     */
    public function getAllPaymentMethods()
    {
        return $this->getBackends();
    }

    /**
     * Retrieves backend by type
     */
    public function getBackendByType($sourceType)
    {
        $this->ensureDataExists();

        if (! $this->object || ! isset($this->object['payment_methods'])) {
            return null;
        }

        $paymentMethods = $this->object['payment_methods'];

        // Find method by exact name match
        foreach ($paymentMethods as $method) {
            if ($method['name'] === $sourceType) {
                return $method;
            }
        }

        // Find method by partial name match (for types like 'installment', 'mobile_banking', etc.)
        foreach ($paymentMethods as $method) {
            if (strpos($method['name'], $sourceType) !== false) {
                return $method;
            }
        }

        return null;
    }

    /**
     * Get payment methods by category/type
     */
    public function getPaymentMethodsByType($type)
    {
        $this->ensureDataExists();

        if (! $this->object || ! isset($this->object['payment_methods'])) {
            return [];
        }

        $paymentMethods = $this->object['payment_methods'];

        return array_filter($paymentMethods, function ($method) use ($type) {
            return strpos($method['name'], $type) !== false;
        });
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
     * @return array list of omise backends source_type.
     */
    public function getAvailablePaymentMethods()
    {
        try {
            $this->ensureDataExists();

            if (! $this->object || ! isset($this->object['payment_methods'])) {
                return $this->getTokenizationMethods() ?? [];
            }

            $paymentMethods = $this->object['payment_methods'];
            $methodNames = array_column($paymentMethods, 'name');
            $tokenMethods = $this->getTokenizationMethods() ?? [];

            return array_merge($methodNames, $tokenMethods);
        } catch (\Exception $e) {
            return $this->getTokenizationMethods() ?? [];
        }
    }

    /**
     * @deprecated Use getAvailablePaymentMethods() instead
     *
     * @return array list of omise backends source_type.
     */
    public function getAavailablePaymentMethods()
    {
        return $this->getAvailablePaymentMethods();
    }

    /**
     * Get all capabilities data as array
     */
    public function toArray(): array
    {
        $this->ensureDataExists();

        return [
            'object' => $this->object['object'] ?? 'capability',
            'location' => $this->object['location'] ?? null,
            'country' => $this->object['country'] ?? null,
            'banks' => $this->object['banks'] ?? [],
            'limits' => $this->object['limits'] ?? [],
            'payment_methods' => $this->object['payment_methods'] ?? [],
            'tokenization_methods' => $this->object['tokenization_methods'] ?? [],
            'zero_interest_installments' => $this->object['zero_interest_installments'] ?? false,
        ];
    }

    /**
     * Get supported banks
     */
    public function getSupportedBanks(): array
    {
        $this->ensureDataExists();

        return $this->object['banks'] ?? [];
    }

    /**
     * Get country code
     */
    public function getCountry(): ?string
    {
        $this->ensureDataExists();

        return $this->object['country'] ?? null;
    }

    /**
     * Get supported currencies from all payment methods
     */
    public function getSupportedCurrencies(): array
    {
        $this->ensureDataExists();

        if (! $this->object || ! isset($this->object['payment_methods'])) {
            return [];
        }

        $allCurrencies = [];
        foreach ($this->object['payment_methods'] as $method) {
            if (isset($method['currencies'])) {
                $allCurrencies = array_merge($allCurrencies, $method['currencies']);
            }
        }

        return array_unique($allCurrencies);
    }

    /**
     * Check if a specific payment method is available
     */
    public function hasPaymentMethod(string $method): bool
    {
        $available = $this->getAvailablePaymentMethods();

        return in_array($method, $available);
    }

    /**
     * Check if installment payments are supported
     */
    public function supportsInstallments(): bool
    {
        try {
            $installmentBackends = $this->getInstallmentBackends();

            return ! empty($installmentBackends);
        } catch (\Exception $e) {
            return false;
        }
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
