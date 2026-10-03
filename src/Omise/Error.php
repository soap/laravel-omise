<?php

namespace Soap\LaravelOmise\Omise;

use Soap\LaravelOmise\Exceptions\OmiseRequestException;
use Throwable;

class Error extends BaseObject
{
    /**
     * @var string
     */
    protected $code = 'unexpected_error';

    protected $message = 'There is an unexpected error happened, please contact our support for further investigation.';

    /**
     * @var Throwable|null
     */
    protected $exception = null;

    public function __construct($error = [])
    {
        isset($error['code']) ? $this->setCode($error['code']) : '';
        isset($error['message']) ? $this->setMessage($error['message']) : '';

        if (($error['exception'] ?? null) instanceof Throwable) {
            $this->exception = $error['exception'];
        }
    }

    /**
     * @param  string  $code
     */
    protected function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * @param  string  $message
     */
    protected function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * The exception of the SDK or the HTTP client that caused the error, if any.
     */
    public function getException(): ?Throwable
    {
        return $this->exception;
    }

    public function isError(): bool
    {
        return true;
    }

    /**
     * @return never
     *
     * @throws OmiseRequestException
     */
    public function throw()
    {
        throw new OmiseRequestException($this, $this->exception);
    }
}
