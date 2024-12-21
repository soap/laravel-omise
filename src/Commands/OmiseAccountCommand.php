<?php

namespace Soap\LaravelOmise\Commands;

use Illuminate\Console\Command;

class OmiseAccountCommand extends Command
{
    public $signature = 'omise:account';

    public $description = 'Retrieve account information from Omise API';

    public function handle(): int
    {
        $response = app('omise')->account()->retrieve();

        if ($response instanceof \Soap\LaravelOmise\Omise\Error) {
            $this->error($response->getMessage());

            return self::FAILURE;
        }
        $this->line('Account information retrieved successfully!', 'info');
        $this->table(['Key', 'Value'], [
            ['ID', $response->id],
            ['Email', $response->email],
            ['Live Mode', $response->livemode ? 'yes' : 'no'],
            ['Webhook Uri', $response->webhook_uri],
            ['Country', $response->country],
            ['Api Version', $response->api_version],
            ['Currency', $response->currency],
            ['Created At', $response->created_at],
        ]);

        return self::SUCCESS;
    }
}
