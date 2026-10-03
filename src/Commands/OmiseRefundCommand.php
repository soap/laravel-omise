<?php

namespace Soap\LaravelOmise\Commands;

use Illuminate\Console\Command;
use Soap\LaravelOmise\Omise\Error;
use Soap\LaravelOmise\Omise\Helpers\OmiseMoney;

class OmiseRefundCommand extends Command
{
    public $signature = 'omise:refund';

    public $description = 'Refund a charge';

    public function handle(): int
    {
        // The command reports the errors itself.
        config(['omise.throw' => false]);

        $chargeId = $this->ask('Enter the charge ID to refund');

        $charge = app('omise')->charge()->find($chargeId);

        if ($charge instanceof Error) {
            $this->error('Omise api call failed');
            $this->error($charge->getMessage());

            return self::FAILURE;
        }

        $amount = $this->ask('Enter the amount to refund', (string) $charge->getAmount());

        $response = $charge->refund([
            'amount' => (int) round(OmiseMoney::toSubunit($amount, $charge->currency)),
        ]);

        if ($response instanceof Error) {
            $this->error('Omise api call failed');
            $this->error($response->getMessage());

            return self::FAILURE;
        }

        $this->line('Charge refunded successfully!');

        return self::SUCCESS;
    }
}
