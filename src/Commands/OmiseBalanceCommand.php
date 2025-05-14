<?php

namespace Soap\LaravelOmise\Commands;

use Illuminate\Console\Command;

class OmiseBalanceCommand extends Command
{
    public $signature = 'omise:balance {--json : Output as JSON}';

    public $description = 'Retrieve balance information from Omise API';

    public function handle(): int
    {
        $response = app('omise')->balance()->retrieve();

        if ($response instanceof \Soap\LaravelOmise\Omise\Error) {
            $this->error($response->getMessage());

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line(json_encode([
                'total' => $response->getTotalAmount(),
                'transferable' => $response->getTransferableAmount(),
                'reserved' => $response->getReservedAmount(),
                'on_hold' => $response->getOnHoldAmount(),
                'currency' => $response->currency,
                'created_at' => $response->getCreatedAt()->format('Y-m-d H:i:s P'),
            ]));

            return self::SUCCESS;
        }

        $this->line('Balance information retrieved successfully!', 'info');
        $this->table(['Key', 'Value'], [
            ['Total', $response->getTotalAmount()],
            ['Transferable', $response->getTransferableAmount()],
            ['Reserved', $response->getReservedAmount()],
            ['On Hold', $response->getOnHoldAmount()],
            ['Currency', $response->currency],
            ['Created At', $response->getCreatedAt()->format('Y-m-d H:i:s P')],
        ]);

        return self::SUCCESS;
    }
}
