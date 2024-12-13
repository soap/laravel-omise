<?php

namespace Soap\LaravelOmise\Commands;

use Illuminate\Console\Command;

class OmiseVerifyCommand extends Command
{
    public $signature = 'omise:verify';

    public $description = 'Verify connection to Omise API';

    public function handle(): int
    {
        if (! app('omise')->validConfig()) {
            $this->error('Omise keys configuration is invalid');

            return self::FAILURE;
        }
        $this->line('Omise keys configuration is valid!');
        $this->line('Verifying connection to Omise API...');
        $response = app('omise')->account()->retrieve();
        if ($response instanceof \Soap\LaravelOmise\Omise\Error) {
            $this->error('Omise api call failed');
            $this->error($response->getMessage());

            return self::FAILURE;
        }

        $this->line('Connection to Omise API verified successfully!');

        return self::SUCCESS;
    }
}
