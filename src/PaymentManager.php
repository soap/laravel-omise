<?php

namespace Soap\LaravelOmise;

use InvalidArgumentException;
use OmiseRefund;
use Soap\LaravelOmise\Contracts\PaymentProcessorFactoryInterface;
use Soap\LaravelOmise\Contracts\PaymentProcessorInterface;
use Soap\LaravelOmise\Exceptions\OmiseRequestException;
use Soap\LaravelOmise\Omise\Error;

class PaymentManager
{
    /**
     * @var Omise
     */
    protected $omise;

    /**
     * @var PaymentProcessorFactoryInterface
     */
    protected $factory;

    public function __construct(Omise $omise, PaymentProcessorFactoryInterface $factory)
    {
        $this->omise = $omise;
        $this->factory = $factory;
    }

    /**
     * A manager with the same processors that sends requests with the given Omise
     * instance, e.g. the one of `Omise::withKeys()`.
     *
     * @return static
     */
    public function using(Omise $omise)
    {
        $manager = clone $this;
        $manager->omise = $omise;
        $manager->factory = $this->factory->using($omise);

        return $manager;
    }

    /**
     * Create a charge with a payment method.
     *
     * @param  int  $amount  in the smallest unit of the currency (satang for THB)
     * @param  array<string, mixed>  $details
     * @return PaymentResult|Error
     *
     * @throws InvalidArgumentException when the payment method is not registered
     * @throws OmiseRequestException when `omise.throw` is enabled
     */
    public function createPayment(string $paymentMethod, int $amount, string $currency = 'THB', array $details = [])
    {
        return $this->processor($paymentMethod)->createPayment($amount, $currency, $details);
    }

    /**
     * The current state of a payment, e.g. on the return page or while a QR code is shown.
     *
     * @return PaymentResult|Error
     */
    public function status(string $chargeId)
    {
        $charge = $this->omise->charge()->find($chargeId);

        return $charge instanceof Error ? $charge : new PaymentResult($charge);
    }

    /**
     * Refund a charge, what is left of it unless an amount is given.
     *
     * @param  int|null  $amount  in the smallest unit of the currency
     * @return OmiseRefund|Error
     */
    public function refundPayment(string $chargeId, ?int $amount = null)
    {
        $charge = $this->omise->charge()->find($chargeId);

        if ($charge instanceof Error) {
            return $charge;
        }

        return $charge->refund([
            'amount' => $amount ?? ($charge->amount - $charge->getRawRefundedAmount()),
        ]);
    }

    public function processor(string $paymentMethod): PaymentProcessorInterface
    {
        return $this->factory->make($paymentMethod);
    }

    /**
     * Register a payment processor, or replace the one of a payment method.
     *
     * @param  class-string<PaymentProcessorInterface>  $processorClass
     * @return $this
     */
    public function extend(string $paymentMethod, string $processorClass)
    {
        $this->factory->register($paymentMethod, $processorClass);

        return $this;
    }

    public function supports(string $paymentMethod): bool
    {
        return $this->factory->supports($paymentMethod);
    }

    /**
     * @return array<int, string>
     */
    public function getSupportedMethods(): array
    {
        return $this->factory->getSupportedMethods();
    }
}
