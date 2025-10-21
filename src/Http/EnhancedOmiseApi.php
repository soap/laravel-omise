<?php

namespace Soap\LaravelOmise\Http;

class EnhancedOmiseApi
{
    protected static $httpClient;

    public static function setHttpClient(OmiseHttpClient $httpClient): void
    {
        self::$httpClient = $httpClient;
    }

    /**
     * Custom request implementation with configured HTTP client
     */
    public static function requestWithCustomClient($method, $url, $key, $data = null)
    {
        $curlOptions = self::$httpClient->getCurlOptions();

        // Prepare headers
        $headers = [
            'Authorization: Basic '.base64_encode($key.':'),
            'Content-Type: application/json',
            'User-Agent: '.($curlOptions[CURLOPT_USERAGENT] ?? 'Laravel-Omise-Package/1.0'),
        ];

        $curl = curl_init();

        // Apply our custom cURL options
        curl_setopt_array($curl, $curlOptions);

        // Set request-specific options
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($method));

        // Add data for POST/PUT requests
        if ($data && ($method === 'POST' || $method === 'PUT')) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }

        // Execute request
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        $errorNumber = curl_errno($curl);

        curl_close($curl);

        // Handle cURL errors
        if ($errorNumber !== 0) {
            throw new \Exception("HTTP Request failed: {$error} (Code: {$errorNumber})");
        }

        // Handle HTTP errors
        if ($httpCode >= 400) {
            $errorData = json_decode($response, true);
            throw new \Exception("HTTP {$httpCode}: ".($errorData['message'] ?? 'Unknown error'));
        }

        return json_decode($response, true);
    }
}
