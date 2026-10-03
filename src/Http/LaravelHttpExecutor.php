<?php

namespace Soap\LaravelOmise\Http;

use Illuminate\Support\Facades\Http;
use OmiseHttpExecutorInterface;

/**
 * Sends Omise API requests with the Laravel HTTP client instead of the SDK's curl
 * executor, so they honour the `omise.url`, `omise.api_version` and `omise.http`
 * configuration and can be faked with `Http::fake()`.
 */
class LaravelHttpExecutor implements OmiseHttpExecutorInterface
{
    /**
     * The API URL the SDK builds its endpoints from.
     */
    public const SDK_API_URL = 'https://api.omise.co/';

    /**
     * @param  string  $url
     * @param  string  $requestMethod
     * @param  string  $key
     * @param  array<string, mixed>|null  $params
     * @return string
     */
    public function execute($url, $requestMethod, $key, $params = null)
    {
        $request = Http::withBasicAuth((string) $key, '')
            ->withUserAgent($this->userAgent())
            ->connectTimeout((int) config('omise.http.connect_timeout', 30))
            ->timeout((int) config('omise.http.timeout', 60));

        if ($version = config('omise.api_version')) {
            $request = $request->withHeaders(['Omise-Version' => $version]);
        }

        if (is_array($params) && count($params) > 0) {
            $request = $request->withBody($this->encode($params), 'application/x-www-form-urlencoded');
        }

        return $request->send($requestMethod, $this->resolveUrl($url))->body();
    }

    /**
     * Points an SDK endpoint at the configured `omise.url`.
     */
    protected function resolveUrl(string $url): string
    {
        $base = rtrim((string) config('omise.url'), '/').'/';

        if ($base === '/' || $base === self::SDK_API_URL || ! str_starts_with($url, self::SDK_API_URL)) {
            return $url;
        }

        return $base.substr($url, strlen(self::SDK_API_URL));
    }

    /**
     * Encodes parameters the same way as the SDK (`items[]=a&items[]=b` for lists).
     *
     * @param  array<string, mixed>  $params
     */
    protected function encode(array $params): string
    {
        return (string) preg_replace('/%5B\d+%5D/simU', '%5B%5D', http_build_query($params));
    }

    protected function userAgent(): string
    {
        $userAgent = 'OmisePHP/'.(defined('OMISE_PHP_LIB_VERSION') ? OMISE_PHP_LIB_VERSION : 'unknown').' PHP/'.PHP_VERSION;

        if ($version = config('omise.api_version')) {
            $userAgent .= ' OmiseAPI/'.$version;
        }

        return $userAgent.' LaravelOmise';
    }
}
