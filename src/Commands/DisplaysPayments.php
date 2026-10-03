<?php

namespace Soap\LaravelOmise\Commands;

use Illuminate\Console\Command;
use Soap\LaravelOmise\Omise\Error;
use Soap\LaravelOmise\Omise\Helpers\OmiseMoney;
use Soap\LaravelOmise\PaymentResult;

/**
 * @mixin Command
 */
trait DisplaysPayments
{
    /**
     * @param  PaymentResult|Error  $payment
     */
    protected function displayPayment($payment): int
    {
        if ($this->option('json')) {
            $this->line((string) json_encode($payment->toArray()));

            return $payment instanceof Error ? self::FAILURE : self::SUCCESS;
        }

        if ($payment instanceof Error) {
            $this->error($payment->getMessage());
            $this->line('Code: '.$payment->getCode().($payment->getOmiseCode() ? ' (Omise: '.$payment->getOmiseCode().')' : ''));

            return self::FAILURE;
        }

        $charge = $payment->charge();

        $this->table(['Charge', 'Method', 'Amount', 'Status', 'Livemode'], [[
            $payment->chargeId(),
            $payment->paymentMethod(),
            OmiseMoney::toCurrencyUnit($charge->amount, $charge->currency).' '.strtoupper($charge->currency),
            $payment->status(),
            $charge->livemode ? 'yes' : 'no',
        ]]);

        if ($payment->isFailed()) {
            $this->error('The charge failed: '.$payment->failureCode().' - '.$payment->failureMessage());
        } elseif ($payment->qrCodeUrl() !== null && $payment->isPending()) {
            $this->line('Scan the QR code: '.$payment->qrCodeUrl());
        } elseif ($payment->requiresRedirect()) {
            $this->line('Open to continue: '.$payment->redirectUrl());
        } elseif ($payment->isSuccessful()) {
            $this->line('The charge is paid.', 'info');
        }

        if ($payment->isPending()) {
            if ($payment->expiresAt() !== null) {
                $this->line('Expires at: '.$payment->expiresAt()->toDateTimeString().' UTC');
            }

            $this->line('Check it again with: omise:payment-status '.$payment->chargeId());
        }

        return self::SUCCESS;
    }
}
