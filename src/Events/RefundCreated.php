<?php

namespace Soap\LaravelOmise\Events;

use OmiseRefund;
use Soap\LaravelOmise\Omise\Charge;

/**
 * Dispatched when a charge is refunded. The charge is as it was loaded before the refund.
 */
class RefundCreated
{
    /**
     * @var Charge
     */
    public $charge;

    /**
     * @var OmiseRefund
     */
    public $refund;

    public function __construct(Charge $charge, OmiseRefund $refund)
    {
        $this->charge = $charge;
        $this->refund = $refund;
    }
}
