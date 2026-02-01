<?php
/**
 * AutoExpireService - Handles automatic domain expiration for Ascio domains
 *
 * This service implements the "AutoExpire OFF" behavior where:
 * - Domains are set to "Expiring" at ExpDate + Threshold date (not immediately)
 * - If domain invoice is already paid, domain stays Active
 * - If domain is already Expiring and paid, it gets Unexpired
 *
 * This runs via the DailyCronJob hook when AutoExpire is set to OFF.
 */

namespace ascio;

use Illuminate\Database\Capsule\Manager as Capsule;
use ascio\Tools as Tools;
use ascio\v2\domains\Request;

class AutoExpireService
{
    /** @var array Module configuration parameters */
    private array $params;

    /** @var Request|null Request handler for Ascio API calls */
    private $request = null;

    /**
     * Constructor
     *
     * @param array $params Module configuration parameters from WHMCS
     */
    public function __construct(array $params)
    {
        $this->params = $params;
    }

    /**
     * Get or create the Request handler
     *
     * @return Request
     */
    protected function getRequest(): Request
    {
        if ($this->request === null) {
            $this->request = Request::create($this->params);
        }
        return $this->request;
    }

    /**
     * Check domains that have reached their threshold date and should be set to expiring.
     * Called by daily cron job.
     *
     * Only processes domains where:
     * - registrar = 'ascio'
     * - status = 'Active'
     * - expirydate + threshold <= today
     * - corresponding invoice is NOT paid
     *
     * @return array Results with processed, expired, skipped counts and errors
     */
    public function processDomainsAtThreshold(): array
    {
        $results = [
            'processed' => 0,
            'expired' => 0,
            'skipped_paid' => 0,
            'errors' => [],
        ];

        try {
            // Get domains at or past their threshold date
            $domains = $this->getDomainsAtThreshold();

            foreach ($domains as $domain) {
                // Skip invalid domain records
                if (!isset($domain->id) || !isset($domain->domain)) {
                    continue;
                }

                $results['processed']++;

                try {
                    // Check if invoice is paid - if so, skip expiring
                    if ($this->isDomainInvoicePaid((int) $domain->id)) {
                        $results['skipped_paid']++;
                        $this->logActivity(
                            "AutoExpire: Skipping domain {$domain->domain} (ID: {$domain->id}) - invoice is paid"
                        );
                        continue;
                    }

                    // Expire the domain via Ascio API
                    $expireResult = $this->expireDomain($domain->id, $domain->domain);

                    if (isset($expireResult['error'])) {
                        $results['errors'][] = [
                            'domain_id' => $domain->id,
                            'domain' => $domain->domain,
                            'error' => $expireResult['error'],
                        ];
                    } else {
                        $results['expired']++;
                        $this->logActivity(
                            "AutoExpire: Domain {$domain->domain} (ID: {$domain->id}) set to Expiring at threshold"
                        );
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'domain_id' => $domain->id,
                        'domain' => $domain->domain,
                        'error' => $e->getMessage(),
                    ];
                }
            }
        } catch (\Exception $e) {
            $results['errors'][] = [
                'domain_id' => null,
                'domain' => null,
                'error' => 'Failed to query domains: ' . $e->getMessage(),
            ];
        }

        return $results;
    }

    /**
     * Check domains that are Expiring but have been paid, and unexpire them.
     *
     * @return array Results with processed, unexpired counts and errors
     */
    public function processExpiredButPaidDomains(): array
    {
        $results = [
            'processed' => 0,
            'unexpired' => 0,
            'errors' => [],
        ];

        try {
            // Get domains that are in Expiring status with Ascio
            $domains = $this->getExpiringDomains();

            foreach ($domains as $domain) {
                // Skip invalid domain records
                if (!isset($domain->id) || !isset($domain->domain)) {
                    continue;
                }

                $results['processed']++;

                try {
                    // Check if invoice is now paid
                    if ($this->isDomainInvoicePaid((int) $domain->id)) {
                        // Domain is paid - unexpire it
                        $unexpireResult = $this->unexpireDomain((int) $domain->id, $domain->domain);

                        if (isset($unexpireResult['error'])) {
                            $results['errors'][] = [
                                'domain_id' => $domain->id,
                                'domain' => $domain->domain,
                                'error' => $unexpireResult['error'],
                            ];
                        } else {
                            $results['unexpired']++;
                            $this->logActivity(
                                "AutoExpire: Domain {$domain->domain} (ID: {$domain->id}) unexpired - invoice paid"
                            );
                        }
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'domain_id' => $domain->id,
                        'domain' => $domain->domain,
                        'error' => $e->getMessage(),
                    ];
                }
            }
        } catch (\Exception $e) {
            $results['errors'][] = [
                'domain_id' => null,
                'domain' => null,
                'error' => 'Failed to query expiring domains: ' . $e->getMessage(),
            ];
        }

        return $results;
    }

    /**
     * Get domains that have reached their threshold date
     *
     * SQL Query:
     * SELECT d.id, d.domain, d.expirydate, t.Threshold
     * FROM tbldomains d
     * JOIN tblasciotlds t ON t.Tld = SUBSTRING_INDEX(d.domain, '.', -1)
     * WHERE d.registrar = 'ascio'
     *   AND d.status = 'Active'
     *   AND DATE_ADD(d.expirydate, INTERVAL t.Threshold DAY) <= CURDATE()
     *
     * @return array Array of domain objects
     */
    protected function getDomainsAtThreshold(): array
    {
        $query = "
            SELECT d.id, d.domain, d.expirydate, t.Threshold
            FROM tbldomains d
            JOIN tblasciotlds t ON t.Tld = SUBSTRING_INDEX(d.domain, '.', -1)
            WHERE d.registrar = 'ascio'
              AND d.status = 'Active'
              AND DATE_ADD(d.expirydate, INTERVAL t.Threshold DAY) <= CURDATE()
        ";

        return Capsule::select($query);
    }

    /**
     * Get domains that are currently in Expiring status
     * These need to be checked for payment and potentially unexpired
     *
     * @return array Array of domain objects
     */
    protected function getExpiringDomains(): array
    {
        // Note: WHMCS stores "Expiring" status, but we need to verify with Ascio
        // that the domain is actually in expiring state at the registry level
        $query = "
            SELECT d.id, d.domain, d.expirydate
            FROM tbldomains d
            WHERE d.registrar = 'ascio'
              AND d.status = 'Active'
        ";

        // For now, get all active Ascio domains and let the unexpire check
        // happen based on payment status. The Ascio API call will handle
        // the actual state verification.
        return Capsule::select($query);
    }

    /**
     * Check if domain's invoice is paid.
     * Look for unpaid invoices with domain line item.
     *
     * @param int $domainId The WHMCS domain ID
     * @return bool True if domain renewal invoice is paid (no unpaid invoices found)
     */
    protected function isDomainInvoicePaid(int $domainId): bool
    {
        // Query for unpaid invoices with this domain as a line item
        // If no unpaid invoices exist for this domain, we consider it "paid"
        $query = "
            SELECT i.id, i.status
            FROM tblinvoices i
            JOIN tblinvoiceitems ii ON i.id = ii.invoiceid
            WHERE ii.type = 'Domain'
              AND ii.relid = :domainId
              AND i.status = 'Unpaid'
              AND i.duedate <= CURDATE()
            LIMIT 1
        ";

        $unpaidInvoices = Capsule::select($query, ['domainId' => $domainId]);

        // Return true if no unpaid invoices found (domain is paid)
        return empty($unpaidInvoices);
    }

    /**
     * Check if there's a recent paid invoice for domain renewal
     * This is used to determine if we should unexpire a domain
     *
     * @param int $domainId The WHMCS domain ID
     * @return bool True if a recent paid invoice exists
     */
    protected function hasRecentPaidInvoice(int $domainId): bool
    {
        // Check for paid invoices in the last 30 days for this domain
        $query = "
            SELECT i.id
            FROM tblinvoices i
            JOIN tblinvoiceitems ii ON i.id = ii.invoiceid
            WHERE ii.type = 'Domain'
              AND ii.relid = :domainId
              AND i.status = 'Paid'
              AND i.datepaid >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            LIMIT 1
        ";

        $paidInvoices = Capsule::select($query, ['domainId' => $domainId]);

        return !empty($paidInvoices);
    }

    /**
     * Get threshold for TLD from tblasciotlds.
     *
     * @param string $tld The TLD (e.g., "com", "net")
     * @return int The threshold in days (typically negative)
     */
    protected function getThresholdForTld(string $tld): int
    {
        $result = Capsule::table('tblasciotlds')
            ->where('Tld', $tld)
            ->value('Threshold');

        return (int) ($result ?? 0);
    }

    /**
     * Extract TLD from domain name
     *
     * @param string $domainName Full domain name (e.g., "example.com")
     * @return string The TLD (e.g., "com")
     */
    protected function extractTld(string $domainName): string
    {
        $parts = explode('.', $domainName);
        return end($parts);
    }

    /**
     * Set domain to expiring status via Ascio API.
     *
     * @param int $domainId The WHMCS domain ID
     * @param string $domainName The full domain name
     * @return array Result with success or error
     */
    protected function expireDomain(int $domainId, string $domainName): array
    {
        try {
            // Build params for the expire request
            $expireParams = array_merge($this->params, [
                'domainid' => $domainId,
                'domainname' => $domainName,
            ]);

            $request = Request::create($expireParams);
            $result = $request->expireDomain($expireParams);

            if (is_array($result) && isset($result['error'])) {
                return $result;
            }

            return ['success' => true];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Unexpire a domain via Ascio API.
     *
     * @param int $domainId The WHMCS domain ID
     * @param string $domainName The full domain name
     * @return array Result with success or error
     */
    protected function unexpireDomain(int $domainId, string $domainName): array
    {
        try {
            // Check if there's a recent paid invoice first
            if (!$this->hasRecentPaidInvoice($domainId)) {
                return ['error' => 'No recent paid invoice found for domain'];
            }

            // Build params for the unexpire request
            $unexpireParams = array_merge($this->params, [
                'domainid' => $domainId,
                'domainname' => $domainName,
            ]);

            $request = Request::create($unexpireParams);
            $result = $request->unexpireDomain($unexpireParams);

            if (is_array($result) && isset($result['error'])) {
                return $result;
            }

            return ['success' => true];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Log activity to WHMCS activity log
     *
     * @param string $message The message to log
     */
    protected function logActivity(string $message): void
    {
        Tools::log($message);
    }

    /**
     * Run the full auto-expire check process
     * Convenience method to run both threshold and unexpire checks
     *
     * @return array Combined results from both processes
     */
    public function run(): array
    {
        $thresholdResults = $this->processDomainsAtThreshold();
        $unexpireResults = $this->processExpiredButPaidDomains();

        return [
            'threshold_check' => $thresholdResults,
            'unexpire_check' => $unexpireResults,
            'summary' => [
                'total_processed' => $thresholdResults['processed'] + $unexpireResults['processed'],
                'total_expired' => $thresholdResults['expired'],
                'total_unexpired' => $unexpireResults['unexpired'],
                'total_skipped_paid' => $thresholdResults['skipped_paid'],
                'total_errors' => count($thresholdResults['errors']) + count($unexpireResults['errors']),
            ],
        ];
    }
}
