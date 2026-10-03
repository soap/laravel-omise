<?php

namespace Soap\LaravelOmise\Concerns;

use Soap\LaravelOmise\Events\RequestFailed;
use Soap\LaravelOmise\Exceptions\OmiseRequestException;
use Soap\LaravelOmise\Omise\Error;

trait ReportsFailures
{
    /**
     * The result of a failed API call: an Error, or an exception when `omise.throw` is enabled.
     *
     * @param  array<string, mixed>  $error
     * @return Error
     *
     * @throws OmiseRequestException
     */
    protected function fail(array $error)
    {
        $error = new Error($error);

        event(new RequestFailed($error));

        if (config('omise.throw')) {
            $error->throw();
        }

        return $error;
    }
}
