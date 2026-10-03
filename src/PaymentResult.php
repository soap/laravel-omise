<?php

namespace Soap\LaravelOmise;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Soap\LaravelOmise\Omise\Charge;

/**
 * The charge of a payment and what the customer has to do next.
 *
 * A result is not a paid charge: a card can be declined (`isFailed()`), and a QR code
 * or a redirect still awaits the customer (`isPending()`).
 *
 * @implements Arrayable<string, mixed>
 */
class PaymentResult implements Arrayable, JsonSerializable
{
    /**
     * @var Charge
     */
    protected $charge;

    public function __construct(Charge $charge)
    {
        $this->charge = $charge;
    }

    public function charge(): Charge
    {
        return $this->charge;
    }

    public function chargeId(): ?string
    {
        return $this->charge->id;
    }

    public function status(): ?string
    {
        return $this->charge->status;
    }

    /**
     * The type of the source of the charge (`promptpay`, `mobile_banking_scb`...), `card` when there is none.
     */
    public function paymentMethod(): string
    {
        return (string) data_get($this->charge->source, 'type', 'card');
    }

    public function isSuccessful(): bool
    {
        return $this->charge->isSuccessful();
    }

    public function isPending(): bool
    {
        return $this->charge->status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->charge->isFailed();
    }

    /**
     * Why the charge failed (e.g. `insufficient_fund`), null when it did not.
     */
    public function failureCode(): ?string
    {
        return $this->charge->failure_code;
    }

    public function failureMessage(): ?string
    {
        return $this->charge->failure_message;
    }

    /**
     * Whether the customer still has to scan a QR code or to be redirected.
     */
    public function requiresAction(): bool
    {
        return $this->isPending() && ($this->qrCodeUrl() !== null || $this->redirectUrl() !== null);
    }

    /**
     * Whether the customer has to be redirected to `redirectUrl()` (3-D Secure, banking app).
     */
    public function requiresRedirect(): bool
    {
        return $this->isPending() && $this->qrCodeUrl() === null && $this->redirectUrl() !== null;
    }

    /**
     * The `authorize_uri` of the charge.
     */
    public function redirectUrl(): ?string
    {
        return $this->charge->authorize_uri ?: null;
    }

    /**
     * The image of the QR code to scan (PromptPay).
     */
    public function qrCodeUrl(): ?string
    {
        return data_get($this->charge->source, 'scannable_code.image.download_uri');
    }

    public function expiresAt(): ?CarbonInterface
    {
        return $this->charge->expires_at ? Carbon::parse($this->charge->expires_at) : null;
    }

    /**
     * Always false, a failed API call is a `Soap\LaravelOmise\Omise\Error`.
     */
    public function isError(): bool
    {
        return false;
    }

    /**
     * @return $this
     */
    public function throw()
    {
        return $this;
    }

    public function toArray(): array
    {
        return [
            'charge_id' => $this->chargeId(),
            'status' => $this->status(),
            'payment_method' => $this->paymentMethod(),
            'amount' => $this->charge->amount,
            'currency' => $this->charge->currency,
            'successful' => $this->isSuccessful(),
            'pending' => $this->isPending(),
            'failed' => $this->isFailed(),
            'failure_code' => $this->failureCode(),
            'failure_message' => $this->failureMessage(),
            'requires_action' => $this->requiresAction(),
            'redirect_url' => $this->redirectUrl(),
            'qr_code_url' => $this->qrCodeUrl(),
            'expires_at' => $this->charge->expires_at,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
