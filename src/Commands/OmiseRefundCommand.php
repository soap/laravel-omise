<?php

namespace Soap\LaravelOmise\Commands;

use Illuminate\Console\Command;

class OmiseRefundCommand extends Command
{
    public $signature = 'omise:refund';

    public $description = 'Refund a charge';

    public function handle(): int
    {
        $chargeId = $this->ask('Enter the charge ID to refund');

        $amount = $this->ask('Enter the amount to refund');

        $response = app('omise')->charge()->refund($chargeId, $amount);

        if ($response instanceof \Soap\LaravelOmise\Omise\Error) {
            $this->error('Omise api call failed');
            $this->error($response->getMessage());

            return self::FAILURE;
        }

        $this->line('Charge refunded successfully!');

        return self::SUCCESS;
    }
}
