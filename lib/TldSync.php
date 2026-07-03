<?php
/**
 * TLD Data Synchronization Script for WHMCS
 * Uses WHMCS Database Layer (Capsule) instead of PDO
 */

use WHMCS\Database\Capsule;
use ascio\Tools as Tools;

require_once(realpath(dirname(__FILE__))."/../../../../init.php");;
require_once realpath(dirname(__FILE__))."/../../../../includes/registrarfunctions.php";
require_once("vendor/autoload.php");
require_once("Tools.php");

class TldSyncService
{

    private $apiUsername;
    private $apiPassword;
    private $testMode;
    private $baseUrl;
    private $tableName = 'tblasciotlds';
    private $maxRetries = 3;
    private $retryDelay = 2; // seconds

    public function __construct()
    {
        $config = getRegistrarConfigOptions('ascio');
        $this->apiUsername = $config['Username'];
        $this->apiPassword = $config['Password'];
        $this->testMode = ($config["TestMode"] ?? '') === 'on';
        // Use configurable TLD Rules API URL, default to production
        $this->baseUrl = $config['TldRulesApiUrl'] ?? 'https://aws.ascio.info';
    }

    /**
     * Check if LastUpdated column exists in the table
     */
    private function hasLastUpdatedColumn()
    {
        try {
            $columns = Capsule::schema()->getColumnListing($this->tableName);
            return in_array('LastUpdated', $columns);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Create or recreate the table with proper structure
     */
    private function ensureTableStructure()
    {
        try {
            // Check if table exists and has proper structure
            if (Capsule::schema()->hasTable($this->tableName)) {
                if (!$this->hasLastUpdatedColumn()) {
                    echo "Table structure is outdated (missing LastUpdated column). Recreating table...\n";
                    
                    // Drop existing table
                    Capsule::schema()->dropIfExists($this->tableName);
                    
                    // Create new table with proper structure
                    $this->createTable();
                } else {
                    echo "Table structure is up to date.\n";
                }
            } else {
                echo "Table does not exist. Creating new table...\n";
                $this->createTable();
            }
        } catch (Exception $e) {
            throw new Exception("Error ensuring table structure: " . $e->getMessage());
        }
    }

    /**
     * Create the tblasciotlds table with proper structure
     */
    private function createTable()
    {
        try {
            Capsule::schema()->create($this->tableName, function ($table) {
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
        } catch (Exception $e) {
            throw new Exception("Error creating table: " . $e->getMessage());
        }
    }

    /**
     * Get the last update date from existing data
     */
    private function getLastUpdateDate()
    {
        try {
            $maxDate = Capsule::table($this->tableName)
                ->whereNotNull('LastUpdated')
                ->max('LastUpdated');

            return $maxDate ?: null;
        } catch (Exception $e) {
            return null; // Return null if table doesn't exist or has no data
        }
    }

    /**
     * Check if database has any existing TLD data
     */
    private function hasExistingData()
    {
        try {
            $count = Capsule::table($this->tableName)->count();
            return $count > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Make authenticated API request with improved error handling and retry logic
     */
    private function makeApiRequest($url, $attempt = 1)
    {
        echo "API Request (attempt {$attempt}): {$url}\n";
        
        // Validate credentials first
        if (empty($this->apiUsername) || empty($this->apiPassword)) {
            throw new Exception("API credentials not configured. Please check WHMCS registrar settings for 'ascio'.");
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
                'ignore_errors' => true // Don't fail on HTTP error codes
            ]
        ]);

        $response = @file_get_contents($url, false, $context);
        // Check if request failed completely
        if ($response === false) {
            $error = error_get_last();
            $errorMsg = "API request failed completely for URL: {$url}";
            if ($error) {
                $errorMsg .= " - Error: " . $error['message'];
            }
            
            // Retry logic for network failures
            if ($attempt < $this->maxRetries) {
                echo "Network error, retrying in {$this->retryDelay} seconds... (attempt {$attempt}/{$this->maxRetries})\n";
                sleep($this->retryDelay);
                return $this->makeApiRequest($url, $attempt + 1);
            }
            
            throw new Exception($errorMsg);
        }

        // Check HTTP response headers
        if (isset($http_response_header)) {
            $statusLine = $http_response_header[0];
            preg_match('/HTTP\/\d\.\d (\d{3})/', $statusLine, $matches);
            $statusCode = isset($matches[1]) ? (int)$matches[1] : 200;
            
            echo "HTTP Status: {$statusCode}\n";
            
            switch ($statusCode) {
                case 200:
                    // Success - continue processing
                    break;
                    
                case 401:
                    throw new Exception("Authentication failed (HTTP 401). Please check API credentials in WHMCS registrar settings. URL: {$url}");
                    
                case 403:
                    throw new Exception("Access forbidden (HTTP 403). API key may not have required permissions. URL: {$url}");
                    
                case 404:
                    throw new Exception("API endpoint not found (HTTP 404). URL may be incorrect: {$url}");
                    
                case 429:
                    echo "Rate limit exceeded (HTTP 429), waiting before retry...\n";
                    if ($attempt < $this->maxRetries) {
                        sleep($this->retryDelay * 2); // Longer delay for rate limits
                        return $this->makeApiRequest($url, $attempt + 1);
                    }
                    throw new Exception("Rate limit exceeded and max retries reached. URL: {$url}");
                    
                case 500:
                case 502:
                case 503:
                case 504:
                    $errorMsg = "Server error (HTTP {$statusCode}) from API";
                    if ($attempt < $this->maxRetries) {
                        echo "{$errorMsg}, retrying in {$this->retryDelay} seconds... (attempt {$attempt}/{$this->maxRetries})\n";
                        sleep($this->retryDelay);
                        return $this->makeApiRequest($url, $attempt + 1);
                    }
                    throw new Exception("{$errorMsg} - max retries reached. URL: {$url}");
                    
                default:
                    if ($statusCode >= 400) {
                        throw new Exception("HTTP error {$statusCode} from API. URL: {$url}. Response: " . substr($response, 0, 500));
                    }
            }
        }

        // Validate response content
        if (empty($response)) {
            throw new Exception("Empty response received from API. URL: {$url}");
        }

        // Check if response looks like XML
        if (!$this->isValidXml($response)) {
            $preview = substr($response, 0, 200);
            throw new Exception("Invalid XML response from API. URL: {$url}. Response preview: {$preview}");
        }

        echo "API request successful, received " . strlen($response) . " bytes\n";
        return $response;
    }

    /**
     * Validate if string is valid XML
     */
    private function isValidXml($xmlString)
    {
        if (empty($xmlString)) return false;
        
        // Basic check for XML structure
        if (strpos(trim($xmlString), '<?xml') !== 0 && strpos(trim($xmlString), '<') !== 0) {
            return false;
        }
        
        // Try to parse with DOMDocument
        $dom = new DOMDocument();
        $oldErrorReporting = libxml_use_internal_errors(true);
        $result = $dom->loadXML($xmlString);
        libxml_use_internal_errors($oldErrorReporting);
        
        return $result !== false;
    }

    /**
     * Parse XML response and extract TLD data with error handling
     */
    private function parseTldData($xmlContent)
    {
        try {
            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            
            if (!$dom->loadXML($xmlContent)) {
                $errors = libxml_get_errors();
                $errorMsg = "XML parsing failed:";
                foreach ($errors as $error) {
                    $errorMsg .= " " . trim($error->message);
                }
                libxml_clear_errors();
                throw new Exception($errorMsg);
            }
            
            $xpath = new DOMXPath($dom);
            
            // Register namespaces
            $xpath->registerNamespace('tldkit', 'http://schemas.datacontract.org/2004/07/TldKit.Models');

            $tldData = [];

            // Extract TLD names (can be comma-separated)
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
            
        } catch (Exception $e) {
            throw new Exception("Error parsing TLD data: " . $e->getMessage());
        }
    }

    /**
     * Get detailed TLD information with enhanced error handling
     */
    private function extractTldDetails($tldName)
    {
        try {
            echo "Extracting details for TLD: {$tldName}\n";
            
            $url = "{$this->baseUrl}/TldKit/{$tldName}";
            $xmlContent = $this->makeApiRequest($url);

            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            
            if (!$dom->loadXML($xmlContent)) {
                $errors = libxml_get_errors();
                $errorMsg = "XML parsing failed for TLD {$tldName}:";
                foreach ($errors as $error) {
                    $errorMsg .= " " . trim($error->message);
                }
                libxml_clear_errors();
                Tools::log($errorMsg);
                throw new Exception($errorMsg);
            }
            
            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('tldkit', 'http://schemas.datacontract.org/2004/07/TldKit.Models');

            // Check if we got a valid TldKit response
            $tldKitNodes = $xpath->query('//tldkit:TldKit');
            if ($tldKitNodes->length === 0) {
                $errorMsg = "No TldKit data found in response for TLD: {$tldName}";
                Tools::log($errorMsg);
                throw new Exception($errorMsg);
            }

            // Extract basic info
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
            
        } catch (Exception $e) {
            $errorMsg = "Error extracting TLD details for '{$tldName}': " . $e->getMessage();
            Tools::log($errorMsg);
            throw new Exception($errorMsg);
        }
    }

    /**
     * Extract threshold value with special logic
     */
    private function extractThreshold($xpath, $tldName)
    {
        $thresholdValue = $this->getElementValue($xpath, '//tldkit:Threshold');
        $tldParts = explode('.', $tldName);
        $tld = end($tldParts);

        // Apply same logic as XQuery: if threshold is -35 and TLD is not 2 chars, set to 0
        if ($thresholdValue == '-35' && strlen($tld) != 2) {
            return 0;
        }

        return (int) $thresholdValue;
    }

    /**
     * Check if TLD has specific product enabled
     */
    private function hasProduct($xpath, $command)
    {
        $products = $xpath->query("//tldkit:Product[tldkit:Command='{$command}' and tldkit:Enabled='true' and tldkit:ObjectType='DOMAINNAME']");
        return $products->length > 0;
    }

    /**
     * Get element value safely
     */
    private function getElementValue($xpath, $query)
    {
        $nodes = $xpath->query($query);
        return $nodes->length > 0 ? trim($nodes->item(0)->textContent) : '';
    }

    /**
     * Save single TLD data to database using WHMCS Capsule
     */
    private function saveTldData($tldData)
    {
        try {
            // Check if record exists
            $existing = Capsule::table($this->tableName)
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
                'LastUpdated' => Capsule::raw('NOW()')
            ];

            if ($existing) {
                // Update existing record
                Capsule::table($this->tableName)
                    ->where('Tld', $tldData['tld'])
                    ->update($dataToSave);
                echo "Updated TLD: {$tldData['tld']}\n";
            } else {
                // Insert new record
                Capsule::table($this->tableName)->insert($dataToSave);
                echo "Inserted TLD: {$tldData['tld']}\n";
            }

        } catch (Exception $e) {
            $errorMsg = "Error saving TLD {$tldData['tld']}: " . $e->getMessage();
            echo "ERROR: {$errorMsg}\n";
            Tools::log($errorMsg);
            throw $e; // Re-throw to let caller handle if needed
        }
    }

    /**
     * Get all TLD names currently in database
     */
    private function getExistingTldNames()
    {
        try {
            return Capsule::table($this->tableName)
                ->pluck('Tld')
                ->toArray();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get all available TLD names from API
     */
    private function getAllAvailableTlds()
    {
        try {
            echo "Fetching complete TLD list from API...\n";
            $url = "{$this->baseUrl}/TldKit";
            $xmlContent = $this->makeApiRequest($url);
            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            
            if (!$dom->loadXML($xmlContent)) {
                throw new Exception("Failed to parse TLD list XML");
            }
            
            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('arrays', 'http://schemas.microsoft.com/2003/10/Serialization/Arrays');

            $allTlds = [];
            $nameNodes = $xpath->query('//arrays:string');
            foreach ($nameNodes as $nameNode) {
                $nameList = explode(',', $nameNode->textContent);
                $allTlds = array_merge($allTlds, array_map('trim', $nameList));
            }

            return array_unique(array_filter($allTlds)); // Remove duplicates and empty values
            
        } catch (Exception $e) {
            echo "WARNING: Could not fetch complete TLD list: " . $e->getMessage() . "\n";
            return [];
        }
    }

    /**
     * Process missing TLDs that are available in API but not in database
     */
    private function processMissingTlds()
    {
        echo "Checking for missing TLDs in database...\n";
        
        $existingTlds = $this->getExistingTldNames();
        $availableTlds = $this->getAllAvailableTlds();
        
        if (empty($availableTlds)) {
            $errorMsg = "Could not retrieve available TLD list, skipping missing TLD check.";
            echo "{$errorMsg}\n";
            Tools::log($errorMsg);
            return 0;
        }
        
        $missingTlds = array_diff($availableTlds, $existingTlds);
        
        if (empty($missingTlds)) {
            echo "✓ No missing TLDs found.\n";
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
                
                // Small delay to be respectful to API
                usleep(500000); // 0.5 second delay
                
            } catch (Exception $e) {
                $errorMsg = "Error processing missing TLD {$tldName}: " . $e->getMessage();
                echo "[{$progressPercent}%] ERROR: {$errorMsg}\n";
                Tools::log($errorMsg);
                $errorCount++;
            }
        }
        
        echo "✓ Missing TLD processing complete: {$processedCount} added, {$errorCount} errors.\n";
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

            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            
            if (!$dom->loadXML($xmlContent)) {
                $errors = libxml_get_errors();
                $errorMsg = "XML parsing failed for main TLD list:";
                foreach ($errors as $error) {
                    $errorMsg .= " " . trim($error->message);
                }
                libxml_clear_errors();
                Tools::log($errorMsg);
                throw new Exception($errorMsg);
            }
            
            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('tldkit', 'http://schemas.datacontract.org/2004/07/TldKit.Models');

            // Check if we have data to process
            $tldKits = $xpath->query('//tldkit:TldKit');
            if ($tldKits->length == 0) {
                echo "No more TLDs to process.\n";
                return false;
            }

            $processedCount = 0;
            $errorCount = 0;
            $savedCount = 0;
            
            // Count total TLDs in this batch for progress tracking (only for full sync)
            $totalTldsInBatch = 0;
            if ($showProgress) {
                foreach ($tldKits as $index => $tldKit) {
                    $nodePosition = $index + 1;
                    $lastUpdated = $this->getElementValue($xpath, "(//tldkit:TldKit)[{$nodePosition}]/tldkit:LastUpdated");
                    
                    if ($lastDate === null || strtotime($lastUpdated) > strtotime($lastDate)) {
                        $names = $this->getElementValue($xpath, "(//tldkit:TldKit)[{$nodePosition}]/tldkit:Name");
                        $nameArray = array_map('trim', explode(',', $names));
                        $totalTldsInBatch += count(array_filter($nameArray));
                    }
                }
                echo "Processing {$totalTldsInBatch} TLDs in this batch...\n";
            }
            
            $currentTldIndex = 0;
            
            foreach ($tldKits as $index => $tldKit) {
                // Query relative to this specific TldKit node using position
                $nodePosition = $index + 1; // XPath positions are 1-based
                $lastUpdated = $this->getElementValue($xpath, "(//tldkit:TldKit)[{$nodePosition}]/tldkit:LastUpdated");

                // If we have a lastDate (incremental sync), only process newer items
                if ($lastDate === null || strtotime($lastUpdated) > strtotime($lastDate)) {
                    $names = $this->getElementValue($xpath, "(//tldkit:TldKit)[{$nodePosition}]/tldkit:Name");
                    $nameArray = array_map('trim', explode(',', $names));

                    foreach ($nameArray as $tldName) {
                        if (!empty($tldName)) {
                            $currentTldIndex++;
                            
                            try {
                                if ($showProgress && $totalTldsInBatch > 0) {
                                    $progressPercent = round(($currentTldIndex / $totalTldsInBatch) * 100, 1);
                                    echo "[{$progressPercent}%] Processing TLD: {$tldName} ({$currentTldIndex}/{$totalTldsInBatch})\n";
                                } else {
                                    echo "Processing TLD: {$tldName}\n";
                                }
                                
                                $tldData = $this->extractTldDetails($tldName);
                                
                                // Save immediately after extracting details
                                $this->saveTldData($tldData);
                                $savedCount++;
                                
                                // Small delay between requests to be respectful to API
                                usleep(250000); // 0.25 second delay
                                
                            } catch (Exception $e) {
                                $errorMsg = "Error processing TLD {$tldName}: " . $e->getMessage();
                                if ($showProgress && $totalTldsInBatch > 0) {
                                    $progressPercent = round(($currentTldIndex / $totalTldsInBatch) * 100, 1);
                                    echo "[{$progressPercent}%] ERROR: {$errorMsg}\n";
                                } else {
                                    echo "ERROR: {$errorMsg}\n";
                                }
                                Tools::log($errorMsg);
                                $errorCount++;
                                // Continue with other TLDs instead of failing completely
                            }
                        }
                    }
                    $processedCount++;
                } else if ($lastDate !== null) {
                    echo "Reached TLDs older than last sync date. Stopping incremental sync.\n";
                    break;
                }
            }

            echo "✓ Batch complete: Processed {$processedCount} TLD groups, saved {$savedCount} TLDs with {$errorCount} errors.\n";
            return $processedCount > 0;

        } catch (Exception $e) {
            $errorMsg = "Sync error: " . $e->getMessage();
            Tools::log($errorMsg);
            throw new Exception($errorMsg);
        }
    }

    /**
     * Run synchronization (full or incremental based on existing data)
     */
    public function runSync()
    {
        // First, ensure table structure is correct
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
    private function runFullSync($lastDate = null)
    {
        $skip = 0;
        $take = 50;
        $totalProcessed = 0;
        $maxBatches = 100; // Safety limit

        echo "Starting full TLD synchronization...\n";
        echo "Estimated maximum batches: {$maxBatches}\n";

        do {
            $batchNumber = $totalProcessed + 1;
            $estimatedProgress = round(($totalProcessed / $maxBatches) * 100, 1);
            
            echo "\n=== BATCH {$batchNumber} (Est. {$estimatedProgress}%) ===\n";
            echo "Processing batch: skip={$skip}, take={$take}\n";
            
            try {
                // Show progress for full sync since we can estimate
                $hasMore = $this->syncTlds($skip, $take, $lastDate, true);
                $skip += $take;
                $totalProcessed++;

                // Add small delay to be respectful to API
                sleep(1);
            } catch (Exception $e) {
                $errorMsg = "Error in batch processing: " . $e->getMessage();
                echo "ERROR: {$errorMsg}\n";
                Tools::log($errorMsg);
                break; // Stop processing on critical errors
            }

        } while ($hasMore && $totalProcessed < $maxBatches);

        echo "\n✓ Full synchronization completed. Total batches processed: {$totalProcessed}\n";
    }

    /**
     * Run incremental synchronization (only new/updated items)
     */
    private function runIncrementalSync($lastDate)
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
        $maxBatches = 50; // Smaller limit for incremental

        echo "Starting incremental TLD synchronization from: {$lastDate}\n";
        echo "Maximum incremental batches: {$maxBatches}\n";

        // First, process updated TLDs (no percentage - we don't know total)
        do {
            $batchNumber = $totalProcessed + 1;
            
            echo "\n=== INCREMENTAL BATCH {$batchNumber} ===\n";
            echo "Processing batch: skip={$skip}, take={$take}\n";
            
            try {
                // Don't show progress percentage for incremental sync
                $hasMore = $this->syncTlds($skip, $take, $lastDate, false);

                if ($hasMore) {
                    $foundNewer = true;
                    $skip += $take;
                    $totalProcessed++;

                    sleep(1);
                }
            } catch (Exception $e) {
                $errorMsg = "Error in incremental batch processing: " . $e->getMessage();
                echo "ERROR: {$errorMsg}\n";
                Tools::log($errorMsg);
                break; // Stop processing on critical errors
            }

        } while ($hasMore && $totalProcessed < $maxBatches);

        if (!$foundNewer) {
            echo "✓ No new TLD updates found since last sync.\n";
        } else {
            echo "✓ Incremental synchronization completed. Total batches processed: {$totalProcessed}\n";
        }

        // Second, process missing TLDs that are available in API but not in database
        echo "\n=== MISSING TLD CHECK ===\n";
        $missingCount = $this->processMissingTlds();
        
        if ($missingCount > 0) {
            echo "✓ Added {$missingCount} missing TLDs to database.\n";
        }
        
        echo "\n✓ Incremental sync with missing TLD check completed.\n";
    }

    /**
     * Public method to just create/recreate the table (useful for setup)
     */
    public function setupTable()
    {
        $this->ensureTableStructure();
    }
}

try {
    $startTime = time();
    echo "=== TLD Synchronization Started ===\n";
    echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";

    $syncService = new TldSyncService();
    $syncService->runSync(); // Will automatically choose full or incremental sync

    $endTime = time();
    $duration = $endTime - $startTime;
    $durationFormatted = gmdate("H:i:s", $duration);

    echo "\n=== TLD Synchronization Completed ===\n";
    echo "End time: " . date('Y-m-d H:i:s') . "\n";
    echo "Total duration: {$durationFormatted}\n";
    echo "✓ Sync completed successfully!\n";

    // Regenerate additional domain fields from TLD Rules API
    echo "\n=== Generating Additional Domain Fields ===\n";
    try {
        require_once(realpath(dirname(__FILE__)) . "/FieldRegistry.php");
        require_once(realpath(dirname(__FILE__)) . "/ConditionalFieldMapper.php");
        require_once(realpath(dirname(__FILE__)) . "/FieldGenerator.php");
        require_once(realpath(dirname(__FILE__)) . "/TldKitFieldsClient.php");

        // Use configured TLD Rules API URL from registrar config
        $config = getRegistrarConfigOptions('ascio');
        $fieldsApiUrl = $config['TldRulesApiUrl'] ?? 'https://aws.ascio.info';
        $username = $config['Username'] ?? '';
        $password = $config['Password'] ?? '';
        $testMode = ($config['TestMode'] ?? '') === 'on';
        $ascioBasePath = realpath(dirname(__FILE__) . "/..");

        echo "  API URL: {$fieldsApiUrl}\n";

        $client = new \ascio\TldKitFieldsClient($fieldsApiUrl, $username, $password, $testMode);
        $apiData = $client->fetchAll();
        $newHash = $client->computeHash($apiData);

        // Only regenerate if data changed
        $hashFile = $ascioBasePath . '/resources/domains/.fields-hash';
        $oldHash = file_exists($hashFile) ? trim(file_get_contents($hashFile)) : '';

        if ($newHash !== $oldHash) {
            $registry = new \ascio\FieldRegistry();
            $mapper = new \ascio\ConditionalFieldMapper($registry);
            $generator = new \ascio\FieldGenerator($registry, $mapper);

            $files = $generator->writeAll($apiData, $ascioBasePath);
            file_put_contents($hashFile, $newHash);

            foreach ($files as $name => $path) {
                echo "  Generated: {$name}\n";
            }
            echo "✓ Field definitions updated.\n";
        } else {
            echo "✓ Field definitions unchanged (hash match).\n";
        }
    } catch (Exception $e) {
        $warnMsg = "Warning: Could not regenerate field definitions: " . $e->getMessage();
        echo "{$warnMsg}\n";
        echo "Keeping existing generated files as fallback.\n";
        Tools::log($warnMsg);
    }

} catch (Exception $e) {
    $errorMsg = "FATAL ERROR: " . $e->getMessage();
    echo "\n=== FATAL ERROR ===\n";
    echo "{$errorMsg}\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    echo "❌ Sync failed!\n";

    // Log the fatal error
    Tools::log($errorMsg . " Stack: " . $e->getTraceAsString());
}