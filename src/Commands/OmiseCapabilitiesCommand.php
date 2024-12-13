<?php

namespace Soap\LaravelOmise\Commands;

use Illuminate\Console\Command;

class OmiseCapabilitiesCommand extends Command
{
    public $signature = 'omise:capabilities';

    public $description = 'Retrieve capabilities information from Omise API';

    public function handle(): int
    {

        return self::SUCCESS;
    }
}