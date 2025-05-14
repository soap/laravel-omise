<?php

namespace Soap\LaravelOmise\Commands;

use Illuminate\Console\Command;

class OmiseBalanceCommand extends Command
{
    public $signature = 'omise:balance';

    public $description = 'Retrieve balance information from Omise API';

    public function handle(): int
    {
        $response = app('omise')->balance()->retrieve();

        if ($response instanceof \Soap\LaravelOmise\Omise\Error) {
            $this->error($response->getMessage());

            return self::FAILURE;
        }
        $this->line('Balance information retrieved successfully!', 'info');
        $this->table(['Key', 'Value'], [
            ['Total', $response->getTotalAmount()],
            ['Transferable', $response->getTransferableAmount()],
            ['Reserved', $response->getReservedAmount()],
            ['On Hold', $response->getOnHoldAmoun()],
            ['Currency', $response->currency],
        ]);

        return self::SUCCESS;
    }
}
