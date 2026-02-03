<?php

namespace Ascio\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Ascio\Tests\Mocks\WhmcsFunctionsMock;
use Ascio\Tests\Mocks\CapsuleMock;
use Ascio\Tests\Mocks\SchemaMock;
use ReflectionClass;
use DOMDocument;
use DOMXPath;

// Define a testable version of TldSyncService that doesn't require init.php
if (!class_exists('TestableTldSyncService')) {
    /**
     * Testable version of TldSyncService without WHMCS dependencies
     */
    class TestableTldSyncService
    {
        private $apiUsername;
        private $apiPassword;
        private $baseUrl;
        private $tableName = 'tblasciotlds';
        private $maxRetries = 3;
        private $retryDelay = 0; // Set to 0 for tests

        public function __construct(array $config = [])
        {
            $this->apiUsername = $config['Username'] ?? '';
            $this->apiPassword = $config['Password'] ?? '';
            $testPrefix = ($config['TestMode'] ?? '') === 'on' ? 'demo.' : '';
            $this->baseUrl = 'https://tldkit.' . $testPrefix . 'ascio.com/api/v1';
        }

        /**
         * Check if LastUpdated column exists in the table
         */
        public function hasLastUpdatedColumn()
        {
            try {
                $columns = \WHMCS\Database\Capsule::schema()->getColumnListing($this->tableName);
                return in_array('LastUpdated', $columns);
            } catch (\Exception $e) {
                return false;
            }
        }

        /**
         * Create or recreate the table with proper structure
         */
        public function ensureTableStructure()
        {
            try {
                if (\WHMCS\Database\Capsule::schema()->hasTable($this->tableName)) {
                    if (!$this->hasLastUpdatedColumn()) {
                        echo "Table structure is outdated (missing LastUpdated column). Recreating table...\n";
                        \WHMCS\Database\Capsule::schema()->dropIfExists($this->tableName);
                        $this->createTable();
                    } else {
                        echo "Table structure is up to date.\n";
                    }
                } else {
                    echo "Table does not exist. Creating new table...\n";
                    $this->createTable();
                }
            } catch (\Exception $e) {
                throw new \Exception("Error ensuring table structure: " . $e->getMessage());
            }
        }

        /**
         * Create the tblasciotlds table with proper structure
         */
        public function createTable()
        {
            try {
                \WHMCS\Database\Capsule::schema()->create($this->tableName, function ($table) {
                    $table->string('Tld', 255)->primary();
                    $table->integer('Threshold');
                    $table->boolean('Renew');
                    $table->boolean('LocalPresenceRequired');
                    $table->boolean('LocalPresenceOffered');
                    $table->boolean('AuthCodeRequired');
                    $table->string('Country', 255);
                    $table->timestamp('LastUpdated')->nullable();
                });

                echo "Table '{$this->tableName}' created successfully.\n";
            } catch (\Exception $e) {
                throw new \Exception("Error creating table: " . $e->getMessage());
            }
        }

        /**
         * Get the last update date from existing data
         */
        public function getLastUpdateDate()
        {
            try {
                $maxDate = \WHMCS\Database\Capsule::table($this->tableName)
                    ->whereNotNull('LastUpdated')
                    ->max('LastUpdated');

                return $maxDate ?: null;
            } catch (\Exception $e) {
                return null;
            }
        }

        /**
         * Check if database has any existing TLD data
         */
        public function hasExistingData()
        {
            try {
                $count = \WHMCS\Database\Capsule::table($this->tableName)->count();
                return $count > 0;
            } catch (\Exception $e) {
                return false;
            }
        }

        /**
         * Make authenticated API request with improved error handling and retry logic
         */
        public function makeApiRequest($url, $attempt = 1)
        {
            echo "API Request (attempt {$attempt}): {$url}\n";

            if (empty($this->apiUsername) || empty($this->apiPassword)) {
                throw new \Exception("API credentials not configured. Please check WHMCS registrar settings for 'ascio'.");
            }

            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 30,
                    'header' => [
                        'Accept: text/xml',
                        'Content-Type: application/xml',
                        'User-Agent: WHMCS-TLD-Sync/1.0',
                        'Authorization: Basic ' . base64_encode($this->apiUsername . ':' . $this->apiPassword)
                    ],
                    'ignore_errors' => true
                ]
            ]);

            $response = @file_get_contents($url, false, $context);

            if ($response === false) {
                $error = error_get_last();
                $errorMsg = "API request failed completely for URL: {$url}";
                if ($error) {
                    $errorMsg .= " - Error: " . $error['message'];
                }

                if ($attempt < $this->maxRetries) {
                    echo "Network error, retrying in {$this->retryDelay} seconds... (attempt {$attempt}/{$this->maxRetries})\n";
                    if ($this->retryDelay > 0) {
                        sleep($this->retryDelay);
                    }
                    return $this->makeApiRequest($url, $attempt + 1);
                }

                throw new \Exception($errorMsg);
            }

            if (isset($http_response_header)) {
                $statusLine = $http_response_header[0];
                preg_match('/HTTP\/\d\.\d (\d{3})/', $statusLine, $matches);
                $statusCode = isset($matches[1]) ? (int)$matches[1] : 200;

                echo "HTTP Status: {$statusCode}\n";

                switch ($statusCode) {
                    case 200:
                        break;
                    case 401:
                        throw new \Exception("Authentication failed (HTTP 401). Please check API credentials in WHMCS registrar settings. URL: {$url}");
                    case 403:
                        throw new \Exception("Access forbidden (HTTP 403). API key may not have required permissions. URL: {$url}");
                    case 404:
                        throw new \Exception("API endpoint not found (HTTP 404). URL may be incorrect: {$url}");
                    case 429:
                        echo "Rate limit exceeded (HTTP 429), waiting before retry...\n";
                        if ($attempt < $this->maxRetries) {
                            if ($this->retryDelay > 0) {
                                sleep($this->retryDelay * 2);
                            }
                            return $this->makeApiRequest($url, $attempt + 1);
                        }
                        throw new \Exception("Rate limit exceeded and max retries reached. URL: {$url}");
                    case 500:
                    case 502:
                    case 503:
                    case 504:
                        $errorMsg = "Server error (HTTP {$statusCode}) from API";
                        if ($attempt < $this->maxRetries) {
                            echo "{$errorMsg}, retrying in {$this->retryDelay} seconds... (attempt {$attempt}/{$this->maxRetries})\n";
                            if ($this->retryDelay > 0) {
                                sleep($this->retryDelay);
                            }
                            return $this->makeApiRequest($url, $attempt + 1);
                        }
                        throw new \Exception("{$errorMsg} - max retries reached. URL: {$url}");
                    default:
                        if ($statusCode >= 400) {
                            throw new \Exception("HTTP error {$statusCode} from API. URL: {$url}. Response: " . substr($response, 0, 500));
                        }
                }
            }

            if (empty($response)) {
                throw new \Exception("Empty response received from API. URL: {$url}");
            }

            if (!$this->isValidXml($response)) {
                $preview = substr($response, 0, 200);
                throw new \Exception("Invalid XML response from API. URL: {$url}. Response preview: {$preview}");
            }

            echo "API request successful, received " . strlen($response) . " bytes\n";
            return $response;
        }

        /**
         * Validate if string is valid XML
         */
        public function isValidXml($xmlString)
        {
            if (empty($xmlString)) return false;

            if (strpos(trim($xmlString), '<?xml') !== 0 && strpos(trim($xmlString), '<') !== 0) {
                return false;
            }

            $dom = new \DOMDocument();
            $oldErrorReporting = libxml_use_internal_errors(true);
            $result = $dom->loadXML($xmlString);
            libxml_use_internal_errors($oldErrorReporting);

            return $result !== false;
        }

        /**
         * Parse XML response and extract TLD data with error handling
         */
        public function parseTldData($xmlContent)
        {
            try {
                $dom = new \DOMDocument();
                libxml_use_internal_errors(true);

                if (!$dom->loadXML($xmlContent)) {
                    $errors = libxml_get_errors();
                    $errorMsg = "XML parsing failed:";
                    foreach ($errors as $error) {
                        $errorMsg .= " " . trim($error->message);
                    }
                    libxml_clear_errors();
                    throw new \Exception($errorMsg);
                }

                $xpath = new \DOMXPath($dom);
                $xpath->registerNamespace('tldkit', 'http://schemas.datacontract.org/2004/07/TldKit.Models');

                $tldData = [];
                $nameNodes = $xpath->query('//tldkit:Name');
                $names = [];
                foreach ($nameNodes as $nameNode) {
                    $nameList = explode(',', $nameNode->textContent);
                    $names = array_merge($names, array_map('trim', $nameList));
                }

                foreach ($names as $tldName) {
                    $tldData[] = $this->extractTldDetails($tldName);
                }

                return $tldData;

            } catch (\Exception $e) {
                throw new \Exception("Error parsing TLD data: " . $e->getMessage());
            }
        }

        /**
         * Get detailed TLD information with enhanced error handling
         */
        public function extractTldDetails($tldName)
        {
            try {
                echo "Extracting details for TLD: {$tldName}\n";

                $url = "{$this->baseUrl}/TldKit/{$tldName}";
                $xmlContent = $this->makeApiRequest($url);

                $dom = new \DOMDocument();
                libxml_use_internal_errors(true);

                if (!$dom->loadXML($xmlContent)) {
                    $errors = libxml_get_errors();
                    $errorMsg = "XML parsing failed for TLD {$tldName}:";
                    foreach ($errors as $error) {
                        $errorMsg .= " " . trim($error->message);
                    }
                    libxml_clear_errors();
                    throw new \Exception($errorMsg);
                }

                $xpath = new \DOMXPath($dom);
                $xpath->registerNamespace('tldkit', 'http://schemas.datacontract.org/2004/07/TldKit.Models');

                $tldKitNodes = $xpath->query('//tldkit:TldKit');
                if ($tldKitNodes->length === 0) {
                    $errorMsg = "No TldKit data found in response for TLD: {$tldName}";
                    throw new \Exception($errorMsg);
                }

                $threshold = $this->extractThreshold($xpath, $tldName);
                $renew = $this->hasProduct($xpath, 'RENEW');
                $localPresenceRequired = $this->getElementValue($xpath, '//tldkit:LocalPresenceRequired') === 'true';
                $localPresenceOffered = $this->getElementValue($xpath, '//tldkit:LocalPresenceOffered') === 'true';
                $authCodeRequired = $this->getElementValue($xpath, '//tldkit:AuthCodeRequired') === 'true';
                $country = $this->getElementValue($xpath, '//tldkit:Country');

                echo "Successfully extracted data for TLD: {$tldName}\n";

                return [
                    'tld' => $tldName,
                    'threshold' => $threshold,
                    'renew' => $renew ? 1 : 0,
                    'local_presence_required' => $localPresenceRequired ? 1 : 0,
                    'local_presence_offered' => $localPresenceOffered ? 1 : 0,
                    'auth_code_required' => $authCodeRequired ? 1 : 0,
                    'country' => $country
                ];

            } catch (\Exception $e) {
                $errorMsg = "Error extracting TLD details for '{$tldName}': " . $e->getMessage();
                throw new \Exception($errorMsg);
            }
        }

        /**
         * Extract threshold value with special logic
         */
        public function extractThreshold($xpath, $tldName)
        {
            $thresholdValue = $this->getElementValue($xpath, '//tldkit:Threshold');
            $tldParts = explode('.', $tldName);
            $tld = end($tldParts);

            if ($thresholdValue == '-35' && strlen($tld) != 2) {
                return 0;
            }

            return (int) $thresholdValue;
        }

        /**
         * Check if TLD has specific product enabled
         */
        public function hasProduct($xpath, $command)
        {
            $products = $xpath->query("//tldkit:Product[tldkit:Command='{$command}' and tldkit:Enabled='true' and tldkit:ObjectType='DOMAINNAME']");
            return $products->length > 0;
        }

        /**
         * Get element value safely
         */
        public function getElementValue($xpath, $query)
        {
            $nodes = $xpath->query($query);
            return $nodes->length > 0 ? trim($nodes->item(0)->textContent) : '';
        }

        /**
         * Save single TLD data to database using WHMCS Capsule
         */
        public function saveTldData($tldData)
        {
            try {
                $existing = \WHMCS\Database\Capsule::table($this->tableName)
                    ->where('Tld', $tldData['tld'])
                    ->first();

                $dataToSave = [
                    'Tld' => $tldData['tld'],
                    'Threshold' => $tldData['threshold'],
                    'Renew' => $tldData['renew'],
                    'LocalPresenceRequired' => $tldData['local_presence_required'],
                    'LocalPresenceOffered' => $tldData['local_presence_offered'],
                    'AuthCodeRequired' => $tldData['auth_code_required'],
                    'Country' => $tldData['country'],
                    'LastUpdated' => \WHMCS\Database\Capsule::raw('NOW()')
                ];

                if ($existing) {
                    \WHMCS\Database\Capsule::table($this->tableName)
                        ->where('Tld', $tldData['tld'])
                        ->update($dataToSave);
                    echo "Updated TLD: {$tldData['tld']}\n";
                } else {
                    \WHMCS\Database\Capsule::table($this->tableName)->insert($dataToSave);
                    echo "Inserted TLD: {$tldData['tld']}\n";
                }

            } catch (\Exception $e) {
                $errorMsg = "Error saving TLD {$tldData['tld']}: " . $e->getMessage();
                echo "ERROR: {$errorMsg}\n";
                throw $e;
            }
        }

        /**
         * Get all TLD names currently in database
         */
        public function getExistingTldNames()
        {
            try {
                return \WHMCS\Database\Capsule::table($this->tableName)
                    ->pluck('Tld')
                    ->toArray();
            } catch (\Exception $e) {
                return [];
            }
        }

        /**
         * Get all available TLD names from API
         */
        public function getAllAvailableTlds()
        {
            try {
                echo "Fetching complete TLD list from API...\n";
                $url = "{$this->baseUrl}/TldKit";
                $xmlContent = $this->makeApiRequest($url);
                $dom = new \DOMDocument();
                libxml_use_internal_errors(true);

                if (!$dom->loadXML($xmlContent)) {
                    throw new \Exception("Failed to parse TLD list XML");
                }

                $xpath = new \DOMXPath($dom);
                $xpath->registerNamespace('arrays', 'http://schemas.microsoft.com/2003/10/Serialization/Arrays');

                $allTlds = [];
                $nameNodes = $xpath->query('//arrays:string');
                foreach ($nameNodes as $nameNode) {
                    $nameList = explode(',', $nameNode->textContent);
                    $allTlds = array_merge($allTlds, array_map('trim', $nameList));
                }

                return array_unique(array_filter($allTlds));

            } catch (\Exception $e) {
                echo "WARNING: Could not fetch complete TLD list: " . $e->getMessage() . "\n";
                return [];
            }
        }

        /**
         * Process missing TLDs that are available in API but not in database
         */
        public function processMissingTlds()
        {
            echo "Checking for missing TLDs in database...\n";

            $existingTlds = $this->getExistingTldNames();
            $availableTlds = $this->getAllAvailableTlds();

            if (empty($availableTlds)) {
                $errorMsg = "Could not retrieve available TLD list, skipping missing TLD check.";
                echo "{$errorMsg}\n";
                return 0;
            }

            $missingTlds = array_diff($availableTlds, $existingTlds);

            if (empty($missingTlds)) {
                echo "No missing TLDs found.\n";
                return 0;
            }

            $totalMissing = count($missingTlds);
            echo "Found {$totalMissing} missing TLDs, processing...\n";

            $processedCount = 0;
            $errorCount = 0;

            foreach ($missingTlds as $index => $tldName) {
                $currentIndex = $index + 1;
                $progressPercent = round(($currentIndex / $totalMissing) * 100, 1);

                try {
                    echo "[{$progressPercent}%] Processing missing TLD: {$tldName} ({$currentIndex}/{$totalMissing})\n";
                    $tldData = $this->extractTldDetails($tldName);
                    $this->saveTldData($tldData);
                    $processedCount++;

                } catch (\Exception $e) {
                    $errorMsg = "Error processing missing TLD {$tldName}: " . $e->getMessage();
                    echo "[{$progressPercent}%] ERROR: {$errorMsg}\n";
                    $errorCount++;
                }
            }

            echo "Missing TLD processing complete: {$processedCount} added, {$errorCount} errors.\n";
            return $processedCount;
        }

        /**
         * Main synchronization method
         */
        public function syncTlds($skip = 0, $take = 50, $lastDate = null, $showProgress = false)
        {
            try {
                if ($lastDate) {
                    echo "Incremental sync from: {$lastDate}\n";
                } else {
                    echo "Full sync (no existing data)\n";
                }

                $url = "{$this->baseUrl}/TldKitSortedByUpdatedDate?take={$take}&skip={$skip}";
                $xmlContent = $this->makeApiRequest($url);

                $dom = new \DOMDocument();
                libxml_use_internal_errors(true);

                if (!$dom->loadXML($xmlContent)) {
                    $errors = libxml_get_errors();
                    $errorMsg = "XML parsing failed for main TLD list:";
                    foreach ($errors as $error) {
                        $errorMsg .= " " . trim($error->message);
                    }
                    libxml_clear_errors();
                    throw new \Exception($errorMsg);
                }

                $xpath = new \DOMXPath($dom);
                $xpath->registerNamespace('tldkit', 'http://schemas.datacontract.org/2004/07/TldKit.Models');

                $tldKits = $xpath->query('//tldkit:TldKit');
                if ($tldKits->length == 0) {
                    echo "No more TLDs to process.\n";
                    return false;
                }

                $processedCount = 0;
                $errorCount = 0;
                $savedCount = 0;

                foreach ($tldKits as $index => $tldKit) {
                    $nodePosition = $index + 1;
                    $lastUpdated = $this->getElementValue($xpath, "(//tldkit:TldKit)[{$nodePosition}]/tldkit:LastUpdated");

                    if ($lastDate === null || strtotime($lastUpdated) > strtotime($lastDate)) {
                        $names = $this->getElementValue($xpath, "(//tldkit:TldKit)[{$nodePosition}]/tldkit:Name");
                        $nameArray = array_map('trim', explode(',', $names));

                        foreach ($nameArray as $tldName) {
                            if (!empty($tldName)) {
                                try {
                                    echo "Processing TLD: {$tldName}\n";
                                    $tldData = $this->extractTldDetails($tldName);
                                    $this->saveTldData($tldData);
                                    $savedCount++;
                                } catch (\Exception $e) {
                                    $errorMsg = "Error processing TLD {$tldName}: " . $e->getMessage();
                                    echo "ERROR: {$errorMsg}\n";
                                    $errorCount++;
                                }
                            }
                        }
                        $processedCount++;
                    } elseif ($lastDate !== null) {
                        echo "Reached TLDs older than last sync date. Stopping incremental sync.\n";
                        break;
                    }
                }

                echo "Batch complete: Processed {$processedCount} TLD groups, saved {$savedCount} TLDs with {$errorCount} errors.\n";
                return $processedCount > 0;

            } catch (\Exception $e) {
                $errorMsg = "Sync error: " . $e->getMessage();
                throw new \Exception($errorMsg);
            }
        }

        /**
         * Run synchronization (full or incremental based on existing data)
         */
        public function runSync()
        {
            $this->ensureTableStructure();

            $hasData = $this->hasExistingData();
            $lastDate = $hasData ? $this->getLastUpdateDate() : null;

            if (!$hasData) {
                echo "No existing data found. Running FULL synchronization...\n";
                $this->runFullSync($lastDate);
            } else {
                echo "Existing data found. Running INCREMENTAL synchronization...\n";
                $this->runIncrementalSync($lastDate);
            }
        }

        /**
         * Run full synchronization with pagination
         */
        public function runFullSync($lastDate = null)
        {
            $skip = 0;
            $take = 50;
            $totalProcessed = 0;
            $maxBatches = 100;

            echo "Starting full TLD synchronization...\n";
            echo "Estimated maximum batches: {$maxBatches}\n";

            do {
                $batchNumber = $totalProcessed + 1;
                $estimatedProgress = round(($totalProcessed / $maxBatches) * 100, 1);

                echo "\n=== BATCH {$batchNumber} (Est. {$estimatedProgress}%) ===\n";
                echo "Processing batch: skip={$skip}, take={$take}\n";

                try {
                    $hasMore = $this->syncTlds($skip, $take, $lastDate, true);
                    $skip += $take;
                    $totalProcessed++;
                } catch (\Exception $e) {
                    $errorMsg = "Error in batch processing: " . $e->getMessage();
                    echo "ERROR: {$errorMsg}\n";
                    break;
                }

            } while ($hasMore && $totalProcessed < $maxBatches);

            echo "\nFull synchronization completed. Total batches processed: {$totalProcessed}\n";
        }

        /**
         * Run incremental synchronization (only new/updated items)
         */
        public function runIncrementalSync($lastDate)
        {
            if (!$lastDate) {
                echo "No last update date found, falling back to full sync.\n";
                $this->runFullSync();
                return;
            }

            $skip = 0;
            $take = 50;
            $totalProcessed = 0;
            $foundNewer = false;
            $maxBatches = 50;

            echo "Starting incremental TLD synchronization from: {$lastDate}\n";
            echo "Maximum incremental batches: {$maxBatches}\n";

            do {
                $batchNumber = $totalProcessed + 1;

                echo "\n=== INCREMENTAL BATCH {$batchNumber} ===\n";
                echo "Processing batch: skip={$skip}, take={$take}\n";

                try {
                    $hasMore = $this->syncTlds($skip, $take, $lastDate, false);

                    if ($hasMore) {
                        $foundNewer = true;
                        $skip += $take;
                        $totalProcessed++;
                    }
                } catch (\Exception $e) {
                    $errorMsg = "Error in incremental batch processing: " . $e->getMessage();
                    echo "ERROR: {$errorMsg}\n";
                    break;
                }

            } while ($hasMore && $totalProcessed < $maxBatches);

            if (!$foundNewer) {
                echo "No new TLD updates found since last sync.\n";
            } else {
                echo "Incremental synchronization completed. Total batches processed: {$totalProcessed}\n";
            }

            echo "\n=== MISSING TLD CHECK ===\n";
            $missingCount = $this->processMissingTlds();

            if ($missingCount > 0) {
                echo "Added {$missingCount} missing TLDs to database.\n";
            }

            echo "\nIncremental sync with missing TLD check completed.\n";
        }

        /**
         * Public method to just create/recreate the table (useful for setup)
         */
        public function setupTable()
        {
            $this->ensureTableStructure();
        }
    }
}

/**
 * Unit tests for TldSyncService class
 *
 * Tests TLD synchronization functionality including:
 * - Table structure verification and creation
 * - XML parsing and validation
 * - API response handling with retry logic
 * - Data extraction from XML responses
 * - Database operations (save, get, update)
 * - Full sync vs incremental sync logic
 * - Missing TLD detection
 */
class TldSyncTest extends TestCase
{
    private ?TestableTldSyncService $service = null;

    /**
     * Sample XML response for TLD list
     */
    private const SAMPLE_TLD_LIST_XML = '<?xml version="1.0" encoding="utf-8"?>
<ArrayOfstring xmlns:i="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://schemas.microsoft.com/2003/10/Serialization/Arrays">
    <string>com</string>
    <string>net</string>
    <string>org</string>
    <string>de</string>
</ArrayOfstring>';

    /**
     * Sample XML response for TLD details
     */
    private const SAMPLE_TLD_DETAILS_XML = '<?xml version="1.0" encoding="utf-8"?>
<TldKit xmlns:i="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://schemas.datacontract.org/2004/07/TldKit.Models">
    <Name>com</Name>
    <Threshold>-30</Threshold>
    <LocalPresenceRequired>false</LocalPresenceRequired>
    <LocalPresenceOffered>false</LocalPresenceOffered>
    <AuthCodeRequired>true</AuthCodeRequired>
    <Country>US</Country>
    <LastUpdated>2024-01-15T10:30:00</LastUpdated>
    <Products>
        <Product>
            <Command>RENEW</Command>
            <Enabled>true</Enabled>
            <ObjectType>DOMAINNAME</ObjectType>
        </Product>
    </Products>
</TldKit>';

    /**
     * Sample XML response for sorted TLD list
     */
    private const SAMPLE_SORTED_TLD_XML = '<?xml version="1.0" encoding="utf-8"?>
<ArrayOfTldKit xmlns:i="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://schemas.datacontract.org/2004/07/TldKit.Models">
    <TldKit>
        <Name>com</Name>
        <LastUpdated>2024-01-15T10:30:00</LastUpdated>
    </TldKit>
    <TldKit>
        <Name>net,org</Name>
        <LastUpdated>2024-01-14T10:30:00</LastUpdated>
    </TldKit>
</ArrayOfTldKit>';

    protected function setUp(): void
    {
        parent::setUp();
        WhmcsFunctionsMock::reset();
        CapsuleMock::reset();
        SchemaMock::reset();

        $this->service = new TestableTldSyncService([
            'Username' => 'testuser',
            'Password' => 'testpass',
            'TestMode' => 'on'
        ]);
    }

    protected function tearDown(): void
    {
        $this->service = null;
        parent::tearDown();
    }

    // =========================================================================
    // Table Structure Verification Tests
    // =========================================================================

    #[Test]
    public function hasLastUpdatedColumnReturnsTrueWhenColumnExists(): void
    {
        SchemaMock::setTableColumns('tblasciotlds', [
            'Tld', 'Threshold', 'Renew', 'LocalPresenceRequired',
            'LocalPresenceOffered', 'AuthCodeRequired', 'Country', 'LastUpdated'
        ]);

        $result = $this->service->hasLastUpdatedColumn();

        $this->assertTrue($result);
    }

    #[Test]
    public function hasLastUpdatedColumnReturnsFalseWhenColumnMissing(): void
    {
        SchemaMock::setTableColumns('tblasciotlds', [
            'Tld', 'Threshold', 'Renew', 'LocalPresenceRequired',
            'LocalPresenceOffered', 'AuthCodeRequired', 'Country'
        ]);

        $result = $this->service->hasLastUpdatedColumn();

        $this->assertFalse($result);
    }

    #[Test]
    public function ensureTableStructureCreatesTableWhenNotExists(): void
    {
        SchemaMock::removeTable('tblasciotlds');

        ob_start();
        $this->service->ensureTableStructure();
        $output = ob_get_clean();

        $this->assertStringContainsString('Table does not exist', $output);
        $this->assertStringContainsString('created successfully', $output);
    }

    #[Test]
    public function ensureTableStructureRecreatesTableWhenOutdated(): void
    {
        SchemaMock::addTable('tblasciotlds');
        SchemaMock::setTableColumns('tblasciotlds', ['Tld', 'Threshold']);

        ob_start();
        $this->service->ensureTableStructure();
        $output = ob_get_clean();

        $this->assertStringContainsString('Table structure is outdated', $output);
    }

    #[Test]
    public function ensureTableStructureDoesNothingWhenUpToDate(): void
    {
        SchemaMock::addTable('tblasciotlds');
        SchemaMock::setTableColumns('tblasciotlds', [
            'Tld', 'Threshold', 'Renew', 'LocalPresenceRequired',
            'LocalPresenceOffered', 'AuthCodeRequired', 'Country', 'LastUpdated'
        ]);

        ob_start();
        $this->service->ensureTableStructure();
        $output = ob_get_clean();

        $this->assertStringContainsString('Table structure is up to date', $output);
    }

    #[Test]
    public function setupTableCallsEnsureTableStructure(): void
    {
        SchemaMock::addTable('tblasciotlds');
        SchemaMock::setTableColumns('tblasciotlds', [
            'Tld', 'Threshold', 'Renew', 'LocalPresenceRequired',
            'LocalPresenceOffered', 'AuthCodeRequired', 'Country', 'LastUpdated'
        ]);

        ob_start();
        $this->service->setupTable();
        $output = ob_get_clean();

        $this->assertStringContainsString('Table structure is up to date', $output);
    }

    // =========================================================================
    // XML Validation Tests
    // =========================================================================

    #[Test]
    public function isValidXmlReturnsTrueForValidXml(): void
    {
        $result = $this->service->isValidXml(self::SAMPLE_TLD_DETAILS_XML);

        $this->assertTrue($result);
    }

    #[Test]
    public function isValidXmlReturnsFalseForEmptyString(): void
    {
        $result = $this->service->isValidXml('');

        $this->assertFalse($result);
    }

    #[Test]
    public function isValidXmlReturnsFalseForNonXmlContent(): void
    {
        $result = $this->service->isValidXml('This is not XML content');

        $this->assertFalse($result);
    }

    #[Test]
    public function isValidXmlReturnsFalseForMalformedXml(): void
    {
        $malformedXml = '<?xml version="1.0"?><root><unclosed>';

        $result = $this->service->isValidXml($malformedXml);

        $this->assertFalse($result);
    }

    #[Test]
    public function isValidXmlReturnsTrueForXmlWithoutDeclaration(): void
    {
        $xmlWithoutDeclaration = '<root><element>value</element></root>';

        $result = $this->service->isValidXml($xmlWithoutDeclaration);

        $this->assertTrue($result);
    }

    #[Test]
    #[DataProvider('xmlValidationProvider')]
    public function isValidXmlHandlesVariousInputs(string $input, bool $expected): void
    {
        $result = $this->service->isValidXml($input);

        $this->assertEquals($expected, $result);
    }

    public static function xmlValidationProvider(): array
    {
        return [
            'valid XML with declaration' => ['<?xml version="1.0"?><root/>', true],
            'valid XML without declaration' => ['<root><child/></root>', true],
            'empty string' => ['', false],
            'plain text' => ['Hello World', false],
            'JSON instead of XML' => ['{"key": "value"}', false],
            'HTML without XML structure' => ['<!DOCTYPE html><html>', false],
            'whitespace only' => ['   ', false],
            'partial XML' => ['<root>', false],
        ];
    }

    // =========================================================================
    // Threshold Extraction Tests
    // =========================================================================

    #[Test]
    public function extractThresholdReturnsNormalThreshold(): void
    {
        $xpath = $this->createXPathFromXml(self::SAMPLE_TLD_DETAILS_XML);

        $result = $this->service->extractThreshold($xpath, 'com');

        $this->assertEquals(-30, $result);
    }

    #[Test]
    public function extractThresholdReturnsZeroForMinus35WithLongTld(): void
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<TldKit xmlns="http://schemas.datacontract.org/2004/07/TldKit.Models">
    <Threshold>-35</Threshold>
</TldKit>';
        $xpath = $this->createXPathFromXml($xml);

        $result = $this->service->extractThreshold($xpath, 'example.photography');

        $this->assertEquals(0, $result);
    }

    #[Test]
    public function extractThresholdKeepsMinus35ForTwoCharTld(): void
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<TldKit xmlns="http://schemas.datacontract.org/2004/07/TldKit.Models">
    <Threshold>-35</Threshold>
</TldKit>';
        $xpath = $this->createXPathFromXml($xml);

        $result = $this->service->extractThreshold($xpath, 'example.de');

        $this->assertEquals(-35, $result);
    }

    #[Test]
    #[DataProvider('thresholdExtractionProvider')]
    public function extractThresholdHandlesVariousCases(string $threshold, string $tld, int $expected): void
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<TldKit xmlns="http://schemas.datacontract.org/2004/07/TldKit.Models">
    <Threshold>' . $threshold . '</Threshold>
</TldKit>';
        $xpath = $this->createXPathFromXml($xml);

        $result = $this->service->extractThreshold($xpath, $tld);

        $this->assertEquals($expected, $result);
    }

    public static function thresholdExtractionProvider(): array
    {
        return [
            'normal threshold for .com' => ['-30', 'com', -30],
            'normal threshold for .net' => ['-45', 'net', -45],
            '-35 for 2-char TLD .de' => ['-35', 'de', -35],
            '-35 for 2-char TLD .uk' => ['-35', 'uk', -35],
            '-35 for 3-char TLD .org becomes 0' => ['-35', 'org', 0],
            '-35 for long TLD .photography becomes 0' => ['-35', 'photography', 0],
            'zero threshold' => ['0', 'com', 0],
            'positive threshold' => ['5', 'com', 5],
        ];
    }

    // =========================================================================
    // Has Product Tests
    // =========================================================================

    #[Test]
    public function hasProductReturnsTrueWhenProductEnabled(): void
    {
        $xpath = $this->createXPathFromXml(self::SAMPLE_TLD_DETAILS_XML);

        $result = $this->service->hasProduct($xpath, 'RENEW');

        $this->assertTrue($result);
    }

    #[Test]
    public function hasProductReturnsFalseWhenProductDisabled(): void
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<TldKit xmlns="http://schemas.datacontract.org/2004/07/TldKit.Models">
    <Products>
        <Product>
            <Command>RENEW</Command>
            <Enabled>false</Enabled>
            <ObjectType>DOMAINNAME</ObjectType>
        </Product>
    </Products>
</TldKit>';
        $xpath = $this->createXPathFromXml($xml);

        $result = $this->service->hasProduct($xpath, 'RENEW');

        $this->assertFalse($result);
    }

    #[Test]
    public function hasProductReturnsFalseWhenProductNotFound(): void
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<TldKit xmlns="http://schemas.datacontract.org/2004/07/TldKit.Models">
    <Products></Products>
</TldKit>';
        $xpath = $this->createXPathFromXml($xml);

        $result = $this->service->hasProduct($xpath, 'TRANSFER');

        $this->assertFalse($result);
    }

    // =========================================================================
    // Get Element Value Tests
    // =========================================================================

    #[Test]
    public function getElementValueReturnsValueWhenExists(): void
    {
        $xpath = $this->createXPathFromXml(self::SAMPLE_TLD_DETAILS_XML);

        $result = $this->service->getElementValue($xpath, '//tldkit:Country');

        $this->assertEquals('US', $result);
    }

    #[Test]
    public function getElementValueReturnsEmptyStringWhenNotExists(): void
    {
        $xpath = $this->createXPathFromXml(self::SAMPLE_TLD_DETAILS_XML);

        $result = $this->service->getElementValue($xpath, '//tldkit:NonExistent');

        $this->assertEquals('', $result);
    }

    #[Test]
    public function getElementValueTrimsWhitespace(): void
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<TldKit xmlns="http://schemas.datacontract.org/2004/07/TldKit.Models">
    <Country>  US  </Country>
</TldKit>';
        $xpath = $this->createXPathFromXml($xml);

        $result = $this->service->getElementValue($xpath, '//tldkit:Country');

        $this->assertEquals('US', $result);
    }

    // =========================================================================
    // Database Operations Tests
    // =========================================================================

    #[Test]
    public function getLastUpdateDateReturnsMaxDateWhenDataExists(): void
    {
        CapsuleMock::setTableData('tblasciotlds', [
            ['Tld' => 'com', 'LastUpdated' => '2024-01-10 10:00:00'],
            ['Tld' => 'net', 'LastUpdated' => '2024-01-15 10:00:00'],
            ['Tld' => 'org', 'LastUpdated' => '2024-01-12 10:00:00'],
        ]);

        $result = $this->service->getLastUpdateDate();

        $this->assertEquals('2024-01-15 10:00:00', $result);
    }

    #[Test]
    public function getLastUpdateDateReturnsNullWhenNoData(): void
    {
        CapsuleMock::setTableData('tblasciotlds', []);

        $result = $this->service->getLastUpdateDate();

        $this->assertNull($result);
    }

    #[Test]
    public function getLastUpdateDateReturnsNullWhenAllDatesNull(): void
    {
        CapsuleMock::setTableData('tblasciotlds', [
            ['Tld' => 'com', 'LastUpdated' => null],
            ['Tld' => 'net', 'LastUpdated' => null],
        ]);

        $result = $this->service->getLastUpdateDate();

        $this->assertNull($result);
    }

    #[Test]
    public function hasExistingDataReturnsTrueWhenDataExists(): void
    {
        CapsuleMock::setTableData('tblasciotlds', [
            ['Tld' => 'com', 'Threshold' => -30],
        ]);

        $result = $this->service->hasExistingData();

        $this->assertTrue($result);
    }

    #[Test]
    public function hasExistingDataReturnsFalseWhenEmpty(): void
    {
        CapsuleMock::setTableData('tblasciotlds', []);

        $result = $this->service->hasExistingData();

        $this->assertFalse($result);
    }

    #[Test]
    public function getExistingTldNamesReturnsAllTlds(): void
    {
        CapsuleMock::setTableData('tblasciotlds', [
            ['Tld' => 'com'],
            ['Tld' => 'net'],
            ['Tld' => 'org'],
        ]);

        $result = $this->service->getExistingTldNames();

        $this->assertCount(3, $result);
        $this->assertContains('com', $result);
        $this->assertContains('net', $result);
        $this->assertContains('org', $result);
    }

    #[Test]
    public function getExistingTldNamesReturnsEmptyArrayWhenNoData(): void
    {
        CapsuleMock::setTableData('tblasciotlds', []);

        $result = $this->service->getExistingTldNames();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function saveTldDataInsertsNewRecord(): void
    {
        CapsuleMock::setTableData('tblasciotlds', []);

        $tldData = [
            'tld' => 'com',
            'threshold' => -30,
            'renew' => 1,
            'local_presence_required' => 0,
            'local_presence_offered' => 0,
            'auth_code_required' => 1,
            'country' => 'US',
        ];

        ob_start();
        $this->service->saveTldData($tldData);
        $output = ob_get_clean();

        $this->assertStringContainsString('Inserted TLD: com', $output);

        $lastQuery = CapsuleMock::getLastQuery();
        $this->assertEquals('insert', $lastQuery['type']);
        $this->assertEquals('tblasciotlds', $lastQuery['table']);
    }

    #[Test]
    public function saveTldDataUpdatesExistingRecord(): void
    {
        CapsuleMock::setTableData('tblasciotlds', [
            ['Tld' => 'com', 'Threshold' => -25, 'Renew' => 0],
        ]);

        $tldData = [
            'tld' => 'com',
            'threshold' => -30,
            'renew' => 1,
            'local_presence_required' => 0,
            'local_presence_offered' => 0,
            'auth_code_required' => 1,
            'country' => 'US',
        ];

        ob_start();
        $this->service->saveTldData($tldData);
        $output = ob_get_clean();

        $this->assertStringContainsString('Updated TLD: com', $output);
    }

    // =========================================================================
    // API Request Tests (mocked network calls)
    // =========================================================================

    #[Test]
    public function makeApiRequestThrowsExceptionForMissingCredentials(): void
    {
        $service = new TestableTldSyncService([
            'Username' => '',
            'Password' => '',
            'TestMode' => 'on'
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('API credentials not configured');

        ob_start();
        try {
            $service->makeApiRequest('https://example.com/api');
        } finally {
            ob_end_clean();
        }
    }

    #[Test]
    public function makeApiRequestThrowsExceptionForMissingUsername(): void
    {
        $service = new TestableTldSyncService([
            'Username' => '',
            'Password' => 'testpass',
            'TestMode' => 'on'
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('API credentials not configured');

        ob_start();
        try {
            $service->makeApiRequest('https://example.com/api');
        } finally {
            ob_end_clean();
        }
    }

    #[Test]
    public function makeApiRequestThrowsExceptionForMissingPassword(): void
    {
        $service = new TestableTldSyncService([
            'Username' => 'testuser',
            'Password' => '',
            'TestMode' => 'on'
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('API credentials not configured');

        ob_start();
        try {
            $service->makeApiRequest('https://example.com/api');
        } finally {
            ob_end_clean();
        }
    }

    // =========================================================================
    // Missing TLD Detection Tests
    // =========================================================================

    #[Test]
    public function processMissingTldsReturnsZeroWhenApiReturnsEmpty(): void
    {
        CapsuleMock::setTableData('tblasciotlds', [
            ['Tld' => 'com'],
            ['Tld' => 'net'],
            ['Tld' => 'org'],
        ]);

        ob_start();
        $result = $this->service->processMissingTlds();
        ob_end_clean();

        // With network failing, getAllAvailableTlds returns empty
        $this->assertEquals(0, $result);
    }

    // =========================================================================
    // Sync Logic Tests
    // =========================================================================

    #[Test]
    public function runSyncCallsFullSyncWhenNoExistingData(): void
    {
        CapsuleMock::setTableData('tblasciotlds', []);
        SchemaMock::addTable('tblasciotlds');
        SchemaMock::setTableColumns('tblasciotlds', [
            'Tld', 'Threshold', 'Renew', 'LocalPresenceRequired',
            'LocalPresenceOffered', 'AuthCodeRequired', 'Country', 'LastUpdated'
        ]);

        ob_start();
        try {
            $this->service->runSync();
        } catch (\Exception $e) {
            // Expected - API call will fail in test environment
        }
        $output = ob_get_clean();

        $this->assertStringContainsString('No existing data found', $output);
        $this->assertStringContainsString('FULL synchronization', $output);
    }

    #[Test]
    public function runSyncCallsIncrementalSyncWhenDataExists(): void
    {
        CapsuleMock::setTableData('tblasciotlds', [
            ['Tld' => 'com', 'Threshold' => -30, 'LastUpdated' => '2024-01-15 10:00:00'],
        ]);
        SchemaMock::addTable('tblasciotlds');
        SchemaMock::setTableColumns('tblasciotlds', [
            'Tld', 'Threshold', 'Renew', 'LocalPresenceRequired',
            'LocalPresenceOffered', 'AuthCodeRequired', 'Country', 'LastUpdated'
        ]);

        ob_start();
        try {
            $this->service->runSync();
        } catch (\Exception $e) {
            // Expected - API call will fail in test environment
        }
        $output = ob_get_clean();

        $this->assertStringContainsString('Existing data found', $output);
        $this->assertStringContainsString('INCREMENTAL synchronization', $output);
    }

    // =========================================================================
    // Parse TLD Data Tests
    // =========================================================================

    #[Test]
    public function parseTldDataThrowsExceptionForInvalidXml(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Error parsing TLD data');

        $this->service->parseTldData('invalid xml content');
    }

    // =========================================================================
    // Integration-style Tests
    // =========================================================================

    #[Test]
    public function fullWorkflowValidatesTableStructure(): void
    {
        SchemaMock::addTable('tblasciotlds');
        SchemaMock::setTableColumns('tblasciotlds', [
            'Tld', 'Threshold', 'Renew', 'LocalPresenceRequired',
            'LocalPresenceOffered', 'AuthCodeRequired', 'Country', 'LastUpdated'
        ]);
        CapsuleMock::setTableData('tblasciotlds', []);

        ob_start();
        $this->service->setupTable();
        $output = ob_get_clean();

        $this->assertStringContainsString('Table structure is up to date', $output);
    }

    #[Test]
    public function syncTldsOutputsProgressInformation(): void
    {
        ob_start();
        try {
            $this->service->syncTlds(0, 10, null, true);
        } catch (\Exception $e) {
            // Expected - network call fails
        }
        $output = ob_get_clean();

        $this->assertStringContainsString('Full sync', $output);
    }

    #[Test]
    public function syncTldsOutputsIncrementalModeWithDate(): void
    {
        ob_start();
        try {
            $this->service->syncTlds(0, 10, '2024-01-15', false);
        } catch (\Exception $e) {
            // Expected - network call fails
        }
        $output = ob_get_clean();

        $this->assertStringContainsString('Incremental sync from: 2024-01-15', $output);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Create DOMXPath from XML string with namespace registration
     */
    private function createXPathFromXml(string $xml): DOMXPath
    {
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('tldkit', 'http://schemas.datacontract.org/2004/07/TldKit.Models');
        return $xpath;
    }
}
