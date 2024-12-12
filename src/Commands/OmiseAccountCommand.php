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
        $this->line('Account information retrieved successfully!');
        $this->table(['Key', 'Value'], [
            ['ID', $response->id],
            ['Email', $response->email],
            ['Type', $response->type],
            ['Name', $response->name],
            ['Bank', $response->bank],
            ['Description', $response->description],
            ['Public Key', $response->public_key],
            ['Secret Key', $response->secret_key],
            ['Currency', $response->currency],
            ['Location', $response->location],
            ['Created', $response->created],
        ]);

        return self::SUCCESS;
    }
}
