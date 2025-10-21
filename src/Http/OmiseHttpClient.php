<?php

namespace Soap\LaravelOmise\Http;

use Soap\LaravelOmise\OmiseConfig;

/**
 * HTTP Client Wrapper for configuring cURL options in Omise-PHP library
 */
class OmiseHttpClient
{
    protected $config;

    protected $curlOptions = [];

    public function __construct(OmiseConfig $config)
    {
        $this->config = $config;
        $this->prepareCurlOptions();
    }

    /**
     * Prepare cURL options based on configuration
     */
    protected function prepareCurlOptions(): void
    {
        $httpConfig = $this->config->getHttpConfig();

        $this->curlOptions = [
            // Timeout settings
            CURLOPT_TIMEOUT => $httpConfig['timeout'] ?? 30,
            CURLOPT_CONNECTTIMEOUT => $httpConfig['connect_timeout'] ?? 10,

            // SSL settings
            CURLOPT_SSL_VERIFYPEER => $httpConfig['verify_ssl'] ?? true,
            CURLOPT_SSL_VERIFYHOST => $httpConfig['verify_ssl'] ? 2 : 0,

            // User agent
            CURLOPT_USERAGENT => $httpConfig['user_agent'] ?? 'Laravel-Omise-Package/1.0',

            // Response settings
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,

            // Error handling
            CURLOPT_FAILONERROR => false,

            // Additional options from config
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        ];

        // Add proxy settings if configured
        $this->addProxyOptions($httpConfig);

        // Add debug options if in development
        $this->addDebugOptions();
    }

    /**
     * Add proxy options if configured
     */
    protected function addProxyOptions(array $httpConfig): void
    {
        if (isset($httpConfig['proxy'])) {
            $proxy = $httpConfig['proxy'];

            if (isset($proxy['host'])) {
                $this->curlOptions[CURLOPT_PROXY] = $proxy['host'];

                if (isset($proxy['port'])) {
                    $this->curlOptions[CURLOPT_PROXYPORT] = $proxy['port'];
                }

                if (isset($proxy['username']) && isset($proxy['password'])) {
                    $this->curlOptions[CURLOPT_PROXYUSERPWD] = $proxy['username'].':'.$proxy['password'];
                }

                if (isset($proxy['type'])) {
                    $this->curlOptions[CURLOPT_PROXYTYPE] = $proxy['type'];
                }
            }
        }
    }

    /**
     * Add debug options for development
     */
    protected function addDebugOptions(): void
    {
        if ($this->config->isDevelopmentMode()) {
            // Enable verbose output for debugging
            $this->curlOptions[CURLOPT_VERBOSE] = true;

            // Log file for curl verbose output
            if (config('omise.logging.enabled')) {
                $logFile = storage_path('logs/omise-curl-debug.log');
                $this->curlOptions[CURLOPT_STDERR] = fopen($logFile, 'a');
            }
        }
    }

    /**
     * Get prepared cURL options
     */
    public function getCurlOptions(): array
    {
        return $this->curlOptions;
    }

    /**
     * Set additional cURL option
     */
    public function setCurlOption(int $option, $value): self
    {
        $this->curlOptions[$option] = $value;

        return $this;
    }

    /**
     * Execute cURL request with configured options
     */
    public function executeCurl(string $url, ?array $postData = null, array $headers = []): array
    {
        $curl = curl_init();

        // Set base options
        curl_setopt_array($curl, $this->curlOptions);

        // Set URL
        curl_setopt($curl, CURLOPT_URL, $url);

        // Set headers
        if (! empty($headers)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }

        // Set POST data if provided
        if ($postData !== null) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postData));
        }

        // Execute request
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        $errorNumber = curl_errno($curl);

        curl_close($curl);

        // Handle errors
        if ($errorNumber !== 0) {
            throw new \Exception("cURL Error ({$errorNumber}): {$error}");
        }

        return [
            'body' => $response,
            'http_code' => $httpCode,
            'error' => $error,
            'error_number' => $errorNumber,
        ];
    }

    /**
     * Create HTTP client instance for Omise objects
     */
    public static function createForOmise(OmiseConfig $config): self
    {
        return new self($config);
    }
}
