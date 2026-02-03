<?php

namespace ascio;

/**
 * TLDKit Fields API Client
 *
 * Fetches required_fields and conditional_fields data from the TLDKit API.
 * Handles pagination automatically. Supports authentication via query parameters.
 *
 * Local:      http://localhost:8021/exist/apps/aws/tldkit.xq (full path, no proxy)
 * Production: https://aws.ascio.info (proxied, no path needed)
 *
 * Authentication: Pass username, password, and env (testing/production) as query params.
 */
class TldKitFieldsClient
{
    private $baseUrl;
    private $perPage;
    private $timeout;
    private $username;
    private $password;
    private $env;

    /**
     * @param string $baseUrl Full base URL to the TLDKit endpoint
     * @param string|null $username Optional username for authentication
     * @param string|null $password Optional password for authentication
     * @param bool $testMode Whether to use testing environment (default: false = production)
     * @param int $perPage Number of results per page
     * @param int $timeout HTTP request timeout in seconds
     */
    public function __construct(
        string $baseUrl,
        ?string $username = null,
        ?string $password = null,
        bool $testMode = false,
        int $perPage = 500,
        int $timeout = 30
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->username = $username;
        $this->password = $password;
        $this->env = $testMode ? 'testing' : 'production';
        $this->perPage = $perPage;
        $this->timeout = $timeout;
    }

    /**
     * Fetch all TLD field data from the API, handling pagination.
     *
     * @return array Decoded JSON data with all TLD entries merged
     * @throws \RuntimeException If the API request fails
     */
    public function fetchAll(): array
    {
        $allData = [];
        $page = 1;

        do {
            $url = $this->buildUrl($page);
            $response = $this->makeRequest($url);
            $decoded = json_decode($response, true);

            if ($decoded === null) {
                throw new \RuntimeException("Invalid JSON response from TLDKit API: " . json_last_error_msg());
            }

            // Merge TLD entries from this page
            if (isset($decoded['data']) && is_array($decoded['data'])) {
                $allData = array_merge($allData, $decoded['data']);
            } elseif (isset($decoded['tlds']) && is_array($decoded['tlds'])) {
                $allData = array_merge($allData, $decoded['tlds']);
            } elseif (is_array($decoded) && !isset($decoded['_links'])) {
                // Flat array response (no pagination wrapper)
                $allData = $decoded;
                break;
            }

            // Check for next page
            $hasNext = isset($decoded['_links']['next']);
            $page++;

        } while ($hasNext);

        return $allData;
    }

    /**
     * Build the request URL with query parameters including authentication.
     */
    private function buildUrl(int $page): string
    {
        $separator = (strpos($this->baseUrl, '?') !== false) ? '&' : '?';
        $url = $this->baseUrl . $separator . 'export=all&per_page=' . $this->perPage;

        // Add authentication parameters if provided
        if (!empty($this->username) && !empty($this->password)) {
            $url .= '&username=' . urlencode($this->username);
            $url .= '&password=' . urlencode($this->password);
            $url .= '&env=' . urlencode($this->env);
        }

        if ($page > 1) {
            $url .= '&page=' . $page;
        }

        return $url;
    }

    /**
     * Make an HTTP GET request and return the response body.
     *
     * @param string $url
     * @return string Response body
     * @throws \RuntimeException On network or HTTP errors
     */
    private function makeRequest(string $url): string
    {
        $ch = curl_init();

        $headers = [
            'Accept: application/json',
            'User-Agent: WHMCS-FieldGenerator/1.0',
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new \RuntimeException("TLDKit API request failed: " . $curlError);
        }

        if ($httpCode === 401) {
            throw new \RuntimeException("TLDKit API authentication failed (HTTP 401). Check credentials.");
        }

        if ($httpCode === 403) {
            throw new \RuntimeException("TLDKit API access forbidden (HTTP 403). Check credentials and permissions.");
        }

        if ($httpCode !== 200) {
            throw new \RuntimeException("TLDKit API returned HTTP " . $httpCode . " for URL: " . $url);
        }

        if (empty($response)) {
            throw new \RuntimeException("Empty response from TLDKit API: " . $url);
        }

        return $response;
    }

    /**
     * Compute a hash of the API response for change detection.
     *
     * @param array $data The decoded API data
     * @return string MD5 hash
     */
    public function computeHash(array $data): string
    {
        return md5(json_encode($data));
    }
}
