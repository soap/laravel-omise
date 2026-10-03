<?php

namespace Soap\LaravelOmise\Services;

use Soap\LaravelOmise\Concerns\ReportsFailures;
use Soap\LaravelOmise\Contracts\PaymentProcessorInterface;
use Soap\LaravelOmise\Omise;
use Soap\LaravelOmise\Omise\Error;
use Soap\LaravelOmise\PaymentResult;

abstract class AbstractPaymentProcessor implements PaymentProcessorInterface
{
    use ReportsFailures;

    /**
     * Details that are sent as they are with every charge.
     *
     * @var array<int, string>
     */
    protected $chargeDetails = ['description', 'metadata', 'customer', 'return_uri', 'expires_at', 'ip'];

    /**
     * @var Omise
     */
    protected $omise;

    public function __construct(Omise $omise)
    {
        $this->omise = $omise;
    }

    public function createPayment(int $amount, string $currency = 'THB', array $details = [])
    {
        $details = $this->withDefaults($details);
        $problems = $this->validate($amount, $currency, $details);

        if ($problems !== []) {
            return $this->fail([
                'code' => 'invalid_payment',
                'message' => implode(' ', $problems),
            ]);
        }

        $charge = $this->omise->charge()->create($this->chargeParams($amount, $currency, $details));

        return $charge instanceof Error ? $charge : new PaymentResult($charge);
    }

    public function validate(int $amount, string $currency, array $details = []): array
    {
        $problems = [];
        $currencies = $this->getSupportedCurrencies();

        if ($amount <= 0) {
            $problems[] = 'The amount must be greater than zero.';
        }

        if ($currencies !== [] && ! in_array(strtoupper($currency), $currencies, true)) {
            $problems[] = sprintf('The currency must be %s.', implode(' or ', $currencies));
        }

        return array_merge($problems, $this->validateDetails($this->withDefaults($details)));
    }

    public function isOffline(): bool
    {
        return false;
    }

    public function hasRefundSupport(): bool
    {
        return true;
    }

    public function getSupportedCurrencies(): array
    {
        return [];
    }

    /**
     * Whether the Omise account can accept the payment method, from its capabilities (one API call).
     */
    public function isAvailable(): bool
    {
        return $this->capability() !== null;
    }

    /**
     * The amount Omise accepts for a charge of the account, in the smallest unit of the
     * currency, from its capabilities (one API call). Null when Omise does not tell.
     *
     * @return array{min: int|null, max: int|null}|null
     */
    public function amountLimits(): ?array
    {
        $limits = $this->omise->capabilities()->retrieve()->limits['charge_amount'] ?? null;

        return is_array($limits) ? ['min' => $limits['min'] ?? null, 'max' => $limits['max'] ?? null] : null;
    }

    /**
     * The payment method as the capabilities of the account describe it.
     *
     * @return array<string, mixed>|null
     */
    protected function capability(): ?array
    {
        return $this->omise->capabilities()->getBackendByType($this->capabilityName());
    }

    /**
     * The name, or the start of the names, of the payment method in the capabilities.
     */
    protected function capabilityName(): string
    {
        return $this->getPaymentMethod();
    }

    /**
     * What is wrong with the details of the payment method.
     *
     * @param  array<string, mixed>  $details
     * @return array<int, string>
     */
    protected function validateDetails(array $details): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $details
     * @return array<string, mixed>
     */
    protected function withDefaults(array $details): array
    {
        if (! isset($details['return_uri']) && filled(config('omise.payments.return_uri'))) {
            $details['return_uri'] = config('omise.payments.return_uri');
        }

        return $details;
    }

    /**
     * The parameters of the charge.
     *
     * @param  array<string, mixed>  $details
     * @return array<string, mixed>
     */
    protected function chargeParams(int $amount, string $currency, array $details): array
    {
        return array_merge(
            ['amount' => $amount, 'currency' => strtolower($currency)],
            array_intersect_key($details, array_flip($this->chargeDetails))
        );
    }
}
