<?php

namespace Soap\LaravelOmise\Commands;

use Illuminate\Console\Command;
use Soap\LaravelOmise\Omise;

class OmisePaymentStatusCommand extends Command
{
    use DisplaysPayments;

    public $signature = 'omise:payment-status
        {charge : Charge id}
        {--json : Output as JSON}';

    public $description = 'Show the current state of a payment';

    public function handle(Omise $omise): int
    {
        // The command reports the errors itself.
        config(['omise.throw' => false]);

        return $this->displayPayment($omise->payments()->status($this->argument('charge')));
    }
}
