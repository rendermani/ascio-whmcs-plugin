<?php

namespace ascio;

/**
 * TLD Rules API Client
 *
 * Fetches TLD data, required fields, and conditional fields from the TLD Rules API.
 * Handles pagination automatically. Supports authentication via query parameters.
 *
 * API Endpoints:
 *   - /api/v1/tldkit/tlds       - List all TLDs with basic properties
 *   - /api/v1/tldkit/fields     - Get required special fields per TLD
 *   - /api/v1/tldkit/conditions - Get conditional field requirements
 *   - /api/v1/tldkit/stats      - Get statistics and sync info
 *
 * Local:      http://aws-local.ascio.loc
 * Production: https://aws.ascio.info
 */
class TldKitFieldsClient
{
    /** @var string Base URL without trailing slash */
    private $baseUrl;

    /** @var int Number of results per page for paginated endpoints */
    private $perPage;

    /** @var int HTTP request timeout in seconds */
    private $timeout;

    /** @var string|null Username for authentication */
    private $username;

    /** @var string|null Password for authentication */
    private $password;

    /** @var string Environment: 'testing' or 'production' */
    private $env;

    /**
     * Default API hosts
     */
    const HOST_LOCAL = 'http://aws-local.ascio.loc';
    const HOST_PRODUCTION = 'https://aws.ascio.info';

    /**
     * @param string $baseUrl Base URL to the API (e.g., http://aws-local.ascio.loc)
     * @param string|null $username Ascio username for authentication
     * @param string|null $password Ascio password for authentication
     * @param bool $testMode Whether to use testing environment (default: false = production)
     * @param int $perPage Number of results per page (max 500)
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
        $this->perPage = min($perPage, 500);
        $this->timeout = $timeout;
    }

    /**
     * Fetch all TLD data with basic properties, handling pagination.
     *
     * @return array Array of TLD entries
     * @throws \RuntimeException If the API request fails
     */
    public function fetchTlds(): array
    {
        return $this->fetchPaginated('/api/v1/tldkit/tlds');
    }

    /**
     * Fetch required fields for all TLDs.
     *
     * @param string|null $tldFilter Optional comma-separated TLD filter
     * @return array API response with 'data' array of TLDs and their required_fields
     * @throws \RuntimeException If the API request fails
     */
    public function fetchFields(?string $tldFilter = null): array
    {
        $params = [];
        if ($tldFilter) {
            $params['tld'] = $tldFilter;
        }
        $url = $this->buildUrl('/api/v1/tldkit/fields', $params);
        $response = $this->makeRequest($url);
        return json_decode($response, true);
    }

    /**
     * Fetch conditional field requirements.
     *
     * @param string|null $tldFilter Optional comma-separated TLD filter
     * @return array API response with 'data' array of TLDs and their conditional_fields
     * @throws \RuntimeException If the API request fails
     */
    public function fetchConditions(?string $tldFilter = null): array
    {
        $params = [];
        if ($tldFilter) {
            $params['tld'] = $tldFilter;
        }
        $url = $this->buildUrl('/api/v1/tldkit/conditions', $params);
        $response = $this->makeRequest($url);
        return json_decode($response, true);
    }

    /**
     * Fetch API statistics.
     *
     * @return array Statistics including counts and last_sync
     * @throws \RuntimeException If the API request fails
     */
    public function fetchStats(): array
    {
        $url = $this->buildUrl('/api/v1/tldkit/stats');
        $response = $this->makeRequest($url);
        return json_decode($response, true);
    }

    /**
     * Fetch all data needed for field generation.
     * Combines TLDs, fields, and conditions into a unified structure.
     *
     * @return array Combined data with 'tlds', 'fields', and 'conditions' keys
     * @throws \RuntimeException If the API request fails
     */
    public function fetchAll(): array
    {
        // Fetch fields and conditions (these are the main data sources)
        $fieldsResponse = $this->fetchFields();
        $conditionsResponse = $this->fetchConditions();

        // Return unified structure
        return [
            'generated' => $fieldsResponse['generated'] ?? date('c'),
            'fields' => $fieldsResponse['data'] ?? [],
            'conditions' => $conditionsResponse['data'] ?? [],
            'total_fields' => $fieldsResponse['total'] ?? 0,
            'total_conditions' => $conditionsResponse['total_with_conditions'] ?? 0,
        ];
    }

    /**
     * Legacy method: Fetch TLD data in the old format for backward compatibility.
     * Maps new API response to old format expected by ascio.php syncOnConfigSave.
     *
     * @return array Data in legacy format with 'tld' key containing TLD entries
     * @throws \RuntimeException If the API request fails
     */
    public function fetchAllLegacy(): array
    {
        $tlds = $this->fetchTlds();

        // Map to legacy format
        $legacyTlds = [];
        foreach ($tlds as $tld) {
            $legacyTlds[] = [
                'tld' => $tld['tld'] ?? '',
                'Threshold' => $tld['threshold'] ?? 0,
                'Renew' => 'true', // Always supported in new API
                'LocalPresenceRequired' => ($tld['local_presence']['required'] ?? false) ? 'true' : 'false',
                'LocalPresenceOffered' => ($tld['local_presence']['offered'] ?? false) ? 'true' : 'false',
                'AuthCodeRequired' => ($tld['auth_code_required'] ?? false) ? 'true' : 'false',
                'Country' => $tld['country'] ?? null,
            ];
        }

        return ['tld' => $legacyTlds];
    }

    /**
     * Fetch paginated endpoint, handling all pages automatically.
     *
     * @param string $endpoint API endpoint path
     * @param array $extraParams Additional query parameters
     * @return array Merged data from all pages
     * @throws \RuntimeException If the API request fails
     */
    private function fetchPaginated(string $endpoint, array $extraParams = []): array
    {
        $allData = [];
        $page = 1;

        do {
            $params = array_merge($extraParams, [
                'page' => $page,
                'per_page' => $this->perPage,
            ]);

            $url = $this->buildUrl($endpoint, $params);
            $response = $this->makeRequest($url);
            $decoded = json_decode($response, true);

            if ($decoded === null) {
                throw new \RuntimeException("Invalid JSON response from TLD Rules API: " . json_last_error_msg());
            }

            // Merge data from this page
            if (isset($decoded['data']) && is_array($decoded['data'])) {
                $allData = array_merge($allData, $decoded['data']);
            }

            // Check for next page
            $hasNext = isset($decoded['_links']['next']);
            $page++;

        } while ($hasNext);

        return $allData;
    }

    /**
     * Build the request URL with query parameters including authentication.
     *
     * @param string $endpoint API endpoint path
     * @param array $params Additional query parameters
     * @return string Full URL
     */
    private function buildUrl(string $endpoint, array $params = []): string
    {
        $url = $this->baseUrl . $endpoint;

        // Add authentication parameters
        if (!empty($this->username) && !empty($this->password)) {
            $params['username'] = $this->username;
            $params['password'] = $this->password;
            $params['env'] = $this->env;
        }

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }

    /**
     * Make an HTTP GET request and return the response body.
     *
     * @param string $url Full URL to request
     * @return string Response body
     * @throws \RuntimeException On network or HTTP errors
     */
    private function makeRequest(string $url): string
    {
        $ch = curl_init();

        $headers = [
            'Accept: application/json',
            'User-Agent: WHMCS-Ascio/1.0',
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
            throw new \RuntimeException("TLD Rules API request failed: " . $curlError);
        }

        if ($httpCode === 401) {
            // Try to parse error message from response
            $errorData = json_decode($response, true);
            $message = $errorData['message'] ?? 'Authentication failed';
            throw new \RuntimeException("TLD Rules API authentication failed (HTTP 401): " . $message);
        }

        if ($httpCode === 403) {
            throw new \RuntimeException("TLD Rules API access forbidden (HTTP 403). Check credentials and permissions.");
        }

        if ($httpCode !== 200) {
            throw new \RuntimeException("TLD Rules API returned HTTP " . $httpCode);
        }

        if (empty($response)) {
            throw new \RuntimeException("Empty response from TLD Rules API");
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

    /**
     * Get the configured base URL.
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Get the configured environment.
     *
     * @return string 'testing' or 'production'
     */
    public function getEnv(): string
    {
        return $this->env;
    }
}
