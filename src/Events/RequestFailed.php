<?php

namespace Soap\LaravelOmise\Events;

use Soap\LaravelOmise\Omise\Error;

/**
 * Dispatched when an API call failed, before the error is returned or thrown.
 */
class RequestFailed
{
    /**
     * @var Error
     */
    public $error;

    public function __construct(Error $error)
    {
        $this->error = $error;
    }
}
