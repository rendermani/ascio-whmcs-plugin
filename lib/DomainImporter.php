<?php
/**
 * Ascio Domain Importer
 *
 * Bulk import domains from Ascio to WHMCS.
 * Fetches domains from Ascio API and matches them to WHMCS clients.
 *
 * @copyright Copyright (c) Tucows Inc.
 */

namespace ascio;

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/Tools.php';
require_once __DIR__ . '/Request.php';

/**
 * Domain Importer for bulk importing domains from Ascio to WHMCS
 */
class DomainImporter
{
    /** @var array Ascio API credentials */
    protected array $params;

    /** @var string Ascio account username */
    protected string $account;

    /** @var string Ascio account password */
    protected string $password;

    /** @var bool Test mode flag */
    protected bool $testMode;

    /** @var \SoapClient SOAP client */
    protected ?\SoapClient $client = null;

    /** @var array Import statistics */
    protected array $stats = [
        'imported' => 0,
        'skipped' => 0,
        'conflicts' => 0,
        'unmatched' => 0,
        'errors' => 0,
    ];

    /** @var array Import log entries */
    protected array $log = [];

    /**
     * Constructor
     *
     * @param array $params Ascio API credentials containing:
     *   - Username: Ascio account username
     *   - Password: Ascio account password
     *   - TestMode: 'on' for test mode
     */
    public function __construct(array $params)
    {
        $this->params = $params;
        $this->account = $params['Username'] ?? '';
        $this->password = $params['Password'] ?? '';
        $this->testMode = ($params['TestMode'] ?? '') === 'on';
    }

    /**
     * Get SOAP client with authentication headers
     *
     * @return \SoapClient
     */
    protected function getClient(): \SoapClient
    {
        if ($this->client === null) {
            $wsdl = $this->testMode ? ASCIO_V3_WSDL_TEST : ASCIO_V3_WSDL_LIVE;
            $options = [
                'cache_wsdl' => WSDL_CACHE_MEMORY,
                'trace' => 1,
                'exceptions' => true,
            ];

            $this->client = new \SoapClient($wsdl, $options);

            // Set SOAP header authentication
            $credentials = [
                'Account' => $this->account,
                'Password' => $this->password
            ];
            $header = new \SoapHeader(
                'http://www.ascio.com/2013/02',
                'SecurityHeaderDetails',
                $credentials,
                false
            );
            $this->client->__setSoapHeaders($header);
        }

        return $this->client;
    }

    /**
     * Fetch domains from Ascio API with pagination
     *
     * @param int $page Page number (1-based)
     * @param int $perPage Number of domains per page
     * @return array Array with 'domains' and 'total' keys
     */
    public function fetchDomainsFromAscio(int $page = 1, int $perPage = 100): array
    {
        $client = $this->getClient();

        // Build search criteria for all active domains
        $criteria = [
            'Mode' => 'Relaxed',
            'WithoutStates' => ['deleted'],
            'Clauses' => []
        ];

        $pageInfo = [
            'PageIndex' => $page,
            'PageSize' => $perPage
        ];

        $ascioParams = [
            'Criteria' => $criteria,
            'PageInfo' => $pageInfo
        ];

        $callParams = ['parameters' => ['request' => $ascioParams]];
        $response = $client->__soapCall('SearchDomain', $callParams);
        $result = $response->SearchDomainResult;

        if ($result->ResultCode != 200 && $result->ResultCode != 201) {
            $errorMsg = $result->Errors->string ?? $result->ResultMessage ?? 'Unknown error';
            throw new \Exception('Ascio API error: ' . Tools::cleanString($errorMsg));
        }

        $domains = [];
        $totalResults = $result->TotalResults ?? 0;

        if (isset($result->Domains->Domain)) {
            $domainList = is_array($result->Domains->Domain)
                ? $result->Domains->Domain
                : [$result->Domains->Domain];

            foreach ($domainList as $domain) {
                $registrantEmail = $domain->Registrant->Email ?? null;
                $registrantCompany = $domain->Registrant->OrgName ?? null;
                $registrantName = ($domain->Registrant->FirstName ?? '') . ' ' . ($domain->Registrant->LastName ?? '');

                $domains[] = [
                    'domain_name' => $domain->DomainName ?? '',
                    'domain_handle' => $domain->DomainHandle ?? '',
                    'expiry_date' => $domain->ExpDate ?? null,
                    'status' => $domain->Status ?? '',
                    'registrant_email' => $registrantEmail,
                    'registrant_company' => trim($registrantCompany),
                    'registrant_name' => trim($registrantName),
                    'created_date' => $domain->CreDate ?? null,
                ];
            }
        }

        return [
            'domains' => $domains,
            'total' => $totalResults,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($totalResults / $perPage),
        ];
    }

    /**
     * Match a client by email or company name
     *
     * @param string $email Registrant email address
     * @param string|null $company Company name (optional)
     * @return int|null WHMCS client ID or null if not found
     */
    public function matchClient(string $email, ?string $company = null): ?int
    {
        // First try exact email match
        $client = Capsule::table('tblclients')
            ->where('email', $email)
            ->first();

        if ($client) {
            return (int) $client->id;
        }

        // Try company name match if provided
        if ($company && strlen(trim($company)) > 0) {
            $client = Capsule::table('tblclients')
                ->where('companyname', $company)
                ->first();

            if ($client) {
                return (int) $client->id;
            }
        }

        return null;
    }

    /**
     * Import a single domain
     *
     * @param array $domainData Domain data from Ascio
     * @param int $clientId WHMCS client ID
     * @param bool $dryRun If true, don't actually import
     * @return array Result with 'action', 'message', and 'success' keys
     */
    public function importDomain(array $domainData, int $clientId, bool $dryRun = false): array
    {
        $domainName = $domainData['domain_name'];

        // Check if domain already exists in WHMCS
        $existingDomain = Capsule::table('tbldomains')
            ->where('domain', $domainName)
            ->first();

        if ($existingDomain) {
            if ((int) $existingDomain->userid === $clientId) {
                // Domain exists with same client - skip
                return [
                    'action' => 'skipped',
                    'success' => true,
                    'message' => "Domain already exists for this client",
                    'domain_id' => $existingDomain->id,
                ];
            } else {
                // Domain exists with different client - conflict
                return [
                    'action' => 'conflict',
                    'success' => false,
                    'message' => "Domain exists with different client (ID: {$existingDomain->userid})",
                    'domain_id' => $existingDomain->id,
                    'existing_client_id' => $existingDomain->userid,
                ];
            }
        }

        if ($dryRun) {
            return [
                'action' => 'would_import',
                'success' => true,
                'message' => "Would import domain for client ID: {$clientId}",
            ];
        }

        // Import the domain using WHMCS localAPI
        $parts = explode('.', $domainName, 2);
        $sld = $parts[0];
        $tld = $parts[1] ?? '';

        // Parse expiry date
        $expiryDate = null;
        if (!empty($domainData['expiry_date'])) {
            $expiry = Tools::dateFromXsDateTime($domainData['expiry_date']);
            if ($expiry) {
                $expiryDate = $expiry;
            }
        }

        // Parse registration date
        $registrationDate = date('Y-m-d');
        if (!empty($domainData['created_date'])) {
            $regDate = Tools::dateFromXsDateTime($domainData['created_date']);
            if ($regDate) {
                $registrationDate = $regDate;
            }
        }

        // Calculate next due date (same as expiry or 30 days before)
        $nextDueDate = $expiryDate ?: date('Y-m-d', strtotime('+1 year'));

        // Use direct database insert for more control
        try {
            $domainId = Capsule::table('tbldomains')->insertGetId([
                'userid' => $clientId,
                'orderid' => 0,
                'type' => 'Register',
                'registrationdate' => $registrationDate,
                'domain' => $domainName,
                'firstpaymentamount' => 0,
                'recurringamount' => 0,
                'registrar' => 'ascio',
                'registrationperiod' => 1,
                'expirydate' => $expiryDate,
                'nextduedate' => $nextDueDate,
                'status' => 'Active',
                'paymentmethod' => '',
                'dnsmanagement' => 0,
                'emailforwarding' => 0,
                'idprotection' => 0,
                'donotrenew' => 0,
                'is_premium' => 0,
                'promoid' => 0,
                'notes' => 'Imported from Ascio on ' . date('Y-m-d H:i:s'),
            ]);

            // Store domain handle
            if (!empty($domainData['domain_handle'])) {
                Capsule::table('tblasciohandles')->insert([
                    'type' => 'domain',
                    'whmcs_id' => $domainId,
                    'ascio_id' => $domainData['domain_handle'],
                    'domain' => $domainName,
                ]);
            }

            return [
                'action' => 'imported',
                'success' => true,
                'message' => "Domain imported successfully",
                'domain_id' => $domainId,
            ];

        } catch (\Exception $e) {
            return [
                'action' => 'error',
                'success' => false,
                'message' => "Import failed: " . $e->getMessage(),
            ];
        }
    }

    /**
     * Run the full import process
     *
     * @param bool $dryRun If true, don't actually import
     * @param callable|null $progressCallback Callback for progress updates
     * @return array Import results with statistics
     */
    public function runImport(bool $dryRun = false, ?callable $progressCallback = null): array
    {
        $this->stats = [
            'imported' => 0,
            'skipped' => 0,
            'conflicts' => 0,
            'unmatched' => 0,
            'errors' => 0,
        ];
        $this->log = [];
        $results = [];

        $page = 1;
        $perPage = 100;
        $totalProcessed = 0;

        do {
            // Fetch domains from Ascio
            $fetchResult = $this->fetchDomainsFromAscio($page, $perPage);
            $domains = $fetchResult['domains'];
            $totalDomains = $fetchResult['total'];
            $totalPages = $fetchResult['total_pages'];

            foreach ($domains as $domain) {
                $domainName = $domain['domain_name'];
                $email = $domain['registrant_email'] ?? '';
                $company = $domain['registrant_company'] ?? null;

                // Try to match client
                $clientId = null;
                if (!empty($email)) {
                    $clientId = $this->matchClient($email, $company);
                }

                if ($clientId === null) {
                    // No matching client found
                    $this->stats['unmatched']++;
                    $this->logImport($domainName, 'unmatched', null,
                        "No matching client found for email: {$email}" .
                        ($company ? ", company: {$company}" : ''));

                    $results[] = [
                        'domain' => $domainName,
                        'action' => 'unmatched',
                        'client_id' => null,
                        'message' => "No matching client found",
                    ];
                    continue;
                }

                // Import the domain
                $importResult = $this->importDomain($domain, $clientId, $dryRun);

                switch ($importResult['action']) {
                    case 'imported':
                    case 'would_import':
                        $this->stats['imported']++;
                        $action = $dryRun ? 'would_import' : 'imported';
                        break;
                    case 'skipped':
                        $this->stats['skipped']++;
                        $action = 'skipped';
                        break;
                    case 'conflict':
                        $this->stats['conflicts']++;
                        $action = 'conflict';
                        break;
                    default:
                        $this->stats['errors']++;
                        $action = 'error';
                }

                $this->logImport($domainName, $action, $clientId, $importResult['message']);

                $results[] = [
                    'domain' => $domainName,
                    'action' => $importResult['action'],
                    'client_id' => $clientId,
                    'message' => $importResult['message'],
                ];

                $totalProcessed++;

                // Call progress callback if provided
                if ($progressCallback) {
                    $progressCallback($totalProcessed, $totalDomains, $domainName);
                }
            }

            $page++;

        } while ($page <= $totalPages);

        return [
            'stats' => $this->stats,
            'results' => $results,
            'total_processed' => $totalProcessed,
            'dry_run' => $dryRun,
        ];
    }

    /**
     * Log an import action to the database
     *
     * @param string $domainName Domain name
     * @param string $action Action taken (imported/skipped/conflict/unmatched/error)
     * @param int|null $clientId Client ID if matched
     * @param string $message Log message
     */
    protected function logImport(string $domainName, string $action, ?int $clientId, string $message): void
    {
        // Check if table exists
        if (!Capsule::schema()->hasTable('tblascio_import_log')) {
            return;
        }

        Capsule::table('tblascio_import_log')->insert([
            'domain_name' => $domainName,
            'action' => $action,
            'client_id' => $clientId,
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->log[] = [
            'domain_name' => $domainName,
            'action' => $action,
            'client_id' => $clientId,
            'message' => $message,
        ];
    }

    /**
     * Get import statistics
     *
     * @return array
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * Get import log
     *
     * @return array
     */
    public function getLog(): array
    {
        return $this->log;
    }

    /**
     * Get recent import logs from database
     *
     * @param int $limit Number of logs to retrieve
     * @return array
     */
    public static function getRecentLogs(int $limit = 100): array
    {
        if (!Capsule::schema()->hasTable('tblascio_import_log')) {
            return [];
        }

        return Capsule::table('tblascio_import_log')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Clear import logs
     *
     * @return int Number of deleted rows
     */
    public static function clearLogs(): int
    {
        if (!Capsule::schema()->hasTable('tblascio_import_log')) {
            return 0;
        }

        return Capsule::table('tblascio_import_log')->delete();
    }
}
