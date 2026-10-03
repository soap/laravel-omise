<?php

namespace Soap\LaravelOmise\Omise;

use OmiseException;
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

    /**
     * The error code Omise answered with (e.g. `invalid_card`, `used_token`), see https://docs.omise.co/api-errors.
     *
     * Null when the error did not come from the API: the request did not reach Omise,
     * its response could not be read, or it was never sent. The outcome of the request
     * is then unknown, do not treat it as a declined payment.
     */
    public function getOmiseCode(): ?string
    {
        if (! $this->exception instanceof OmiseException) {
            return null;
        }

        // The error response of the API, as an array.
        $code = data_get($this->exception->getOmiseError(), 'code');

        return is_string($code) ? $code : null;
    }

    public function isError(): bool
    {
        return true;
    }

    /**
     * Convert to array representation
     */
    public function toArray(): array
    {
        return [
            'object' => 'error',
            'code' => $this->getCode(),
            'omise_code' => $this->getOmiseCode(),
            'message' => $this->getMessage(),
        ];
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
