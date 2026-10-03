<?php

namespace Soap\LaravelOmise\Http;

use Exception;
use InvalidArgumentException;
use OmiseApiResource;
use OmiseException;
use OmiseHttpExecutor;
use OmiseHttpExecutorInterface;
use ReflectionProperty;
use RuntimeException;

/**
 * Decides how requests reach the Omise API.
 *
 * - `sdk` (default): the curl executor of `omise/omise-php`.
 * - `laravel`: `LaravelHttpExecutor`, on top of the Laravel HTTP client.
 *
 * It also sends the requests the SDK can only make once the `OMISE_PUBLIC_KEY` and
 * `OMISE_SECRET_KEY` constants are defined (capture, refund, expire, update, delete).
 */
class Transport
{
    public const DRIVER_SDK = 'sdk';

    public const DRIVER_LARAVEL = 'laravel';

    /**
     * The executor installed into the SDK, null while the SDK uses its own.
     *
     * @var OmiseHttpExecutorInterface|null
     */
    protected static $executor = null;

    public static function useDriver(?string $driver): void
    {
        $driver = $driver ?: self::DRIVER_SDK;

        if ($driver === self::DRIVER_SDK) {
            static::use(null);

            return;
        }

        if ($driver !== self::DRIVER_LARAVEL) {
            throw new InvalidArgumentException("Unsupported Omise HTTP driver [{$driver}]. Use \"sdk\" or \"laravel\".");
        }

        if (! static::$executor instanceof LaravelHttpExecutor) {
            static::use(new LaravelHttpExecutor);
        }
    }

    /**
     * Installs an executor for every Omise API request, or restores the SDK's own with null.
     */
    public static function use(?OmiseHttpExecutorInterface $executor): void
    {
        if ($executor === null && static::$executor === null) {
            return;
        }

        if (! property_exists(OmiseApiResource::class, 'httpExecutor')) {
            throw new RuntimeException('Replacing the Omise HTTP executor requires omise/omise-php ^3.0.');
        }

        (new ReflectionProperty(OmiseApiResource::class, 'instances'))->setValue(null, $executor === null ? [] : new ResourceRegistry);
        (new ReflectionProperty(OmiseApiResource::class, 'httpExecutor'))->setValue(null, $executor);

        static::$executor = $executor;
    }

    public static function executor(): OmiseHttpExecutorInterface
    {
        if (static::$executor !== null) {
            return static::$executor;
        }

        return class_exists(OmiseHttpExecutor::class) ? new OmiseHttpExecutor : new LaravelHttpExecutor;
    }

    /**
     * Calls an API endpoint with the given key and returns the decoded response.
     *
     * @param  array<string, mixed>|null  $params
     * @return array<string, mixed>
     *
     * @throws OmiseException|Exception
     */
    public static function request(string $method, string $path, string $key, ?array $params = null): array
    {
        $result = static::executor()->execute(LaravelHttpExecutor::SDK_API_URL.ltrim($path, '/'), $method, $key, $params);

        $response = json_decode($result, true);

        if (! is_array($response) || ! isset($response['object'])) {
            throw new Exception('Unknown error. (Bad Response)');
        }

        if ($response['object'] === 'error') {
            throw OmiseException::getInstance($response);
        }

        return $response;
    }
}
