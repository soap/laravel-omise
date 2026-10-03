<?php

namespace Soap\LaravelOmise\Exceptions;

use RuntimeException;
use Soap\LaravelOmise\Omise\Error;
use Throwable;

/**
 * Thrown by `Error::throw()`. The exception of the SDK or the HTTP client, when
 * there is one, is available from `getPrevious()`.
 */
class OmiseRequestException extends RuntimeException
{
    /**
     * @var Error
     */
    protected $error;

    public function __construct(Error $error, ?Throwable $previous = null)
    {
        parent::__construct($error->getMessage(), 0, $previous);

        $this->error = $error;
    }

    public function getError(): Error
    {
        return $this->error;
    }

    /**
     * The error code of the package (e.g. `bad_request`, `not_found`).
     */
    public function getErrorCode(): string
    {
        return $this->error->getCode();
    }

    /**
     * The error code Omise answered with (e.g. `invalid_card`), null when the error did not come from the API.
     */
    public function getOmiseCode(): ?string
    {
        return $this->error->getOmiseCode();
    }
}
