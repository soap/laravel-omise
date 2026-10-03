<?php

namespace Soap\LaravelOmise\Commands;

use Exception;
use Illuminate\Console\Command;
use InvalidArgumentException;
use OmiseToken;
use Soap\LaravelOmise\Omise;
use Soap\LaravelOmise\Omise\Helpers\OmiseMoney;

class OmisePayCommand extends Command
{
    use DisplaysPayments;

    public $signature = 'omise:pay
        {method : Payment method: card, promptpay, mobile_banking, installment}
        {amount : Amount in the currency unit, e.g. 20 or 20.50}
        {--currency=THB : Currency of the amount}
        {--card= : Card token of Omise.js}
        {--test-card : Create a token of the Omise test card 4242 4242 4242 4242 (test keys only)}
        {--customer= : Customer id}
        {--bank= : Bank of mobile banking or installment, e.g. scb, kbank, bay}
        {--term= : Number of months of an installment}
        {--return-uri= : Where Omise sends the customer back after a redirect}
        {--description= : Description of the charge}
        {--authorize-only : Authorize a card without capturing it}
        {--force : Do not ask before charging with live keys}
        {--json : Output as JSON}';

    public $description = 'Create a payment with a payment method';

    public function handle(Omise $omise): int
    {
        // The command reports the errors itself.
        config(['omise.throw' => false]);

        if (! $omise->validConfig()) {
            $this->error('Omise keys configuration is invalid: '.implode(', ', $omise->configErrors()));

            return self::FAILURE;
        }

        if ($omise->liveMode() && ! $this->option('force') && ! $this->confirm('The LIVE keys are in use, this creates a real charge. Continue?')) {
            return self::FAILURE;
        }

        try {
            $amount = (int) round(OmiseMoney::toSubunit($this->argument('amount'), $this->option('currency')));
            $details = $this->details($omise);

            $payment = $omise->payments()->createPayment($this->argument('method'), $amount, $this->option('currency'), $details);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return $this->displayPayment($payment);
    }

    /**
     * @return array<string, mixed>
     */
    protected function details(Omise $omise): array
    {
        $details = array_filter([
            'card' => $this->option('test-card') ? $this->testCardToken($omise) : $this->option('card'),
            'customer' => $this->option('customer'),
            'bank' => $this->option('bank'),
            'term' => $this->option('term'),
            'return_uri' => $this->option('return-uri'),
            'description' => $this->option('description'),
        ], fn ($value) => filled($value));

        if ($this->option('authorize-only')) {
            $details['capture'] = false;
        }

        return $details;
    }

    /**
     * A token of the test card of Omise, so that a card payment can be tried without a
     * page that runs Omise.js. Live keys never tokenize a card from the server.
     *
     * @throws InvalidArgumentException
     */
    protected function testCardToken(Omise $omise): string
    {
        if ($omise->liveMode()) {
            throw new InvalidArgumentException('--test-card is only available with test keys.');
        }

        try {
            $token = OmiseToken::create(['card' => [
                'name' => 'Laravel Omise',
                'number' => '4242424242424242',
                'expiration_month' => 12,
                'expiration_year' => (int) date('Y') + 2,
                'security_code' => '123',
            ]], $omise->getPublicKey(), $omise->getSecretKey());
        } catch (Exception $e) {
            throw new InvalidArgumentException('The test card could not be tokenized: '.$e->getMessage(), 0, $e);
        }

        return (string) $token['id'];
    }
}
