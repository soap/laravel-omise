<?php

namespace Soap\LaravelOmise\Events;

use Soap\LaravelOmise\Omise\Charge;

/**
 * Dispatched when Omise accepted a new charge. It is not paid yet when it
 * awaits a capture or a payment of the customer, see the status of the charge.
 */
class ChargeCreated
{
    /**
     * @var Charge
     */
    public $charge;

    public function __construct(Charge $charge)
    {
        $this->charge = $charge;
    }
}
