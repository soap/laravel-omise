<?php

namespace Soap\LaravelOmise\Commands;

use Illuminate\Console\Command;

class OmiseVerifyCommand extends Command
{
    public $signature = 'omise:verify';

    public $description = 'Verify connection to Omise API';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
