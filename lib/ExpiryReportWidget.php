<?php
/**
 * Ascio Domain Expiry Report Widget
 *
 * Provides domain expiry reporting and statistics for the WHMCS admin dashboard.
 * Queries domains registered via Ascio that are expiring within configurable periods.
 *
 * @copyright Copyright (c) Tucows Inc.
 */

namespace ascio;

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Domain Expiry Report Widget for Ascio WHMCS Module
 *
 * Provides methods to:
 * - Get expiring domains with filtering
 * - Get expiry statistics for 30/60/90 day periods
 * - Export domain lists to CSV
 */
class ExpiryReportWidget
{
    /** @var string Cache key prefix */
    private const CACHE_KEY_PREFIX = 'ascio_expiry_report_';

    /** @var int Cache TTL in seconds (1 hour) */
    private const CACHE_TTL = 3600;

    /** @var array In-memory cache for stats */
    private static array $statsCache = [];

    /** @var int|null Cache timestamp */
    private static ?int $cacheTimestamp = null;

    /**
     * Get domains expiring within the specified number of days
     *
     * @param int $days Number of days to look ahead (default: 30)
     * @param string|null $tld Filter by TLD (e.g., 'com', 'net')
     * @param string|null $status Filter by domain status ('Active', 'Pending', or null for both)
     * @param int $page Page number for pagination (1-based)
     * @param int $perPage Number of results per page
     * @return array{domains: array, total: int, page: int, perPage: int, totalPages: int}
     */
    public static function getExpiringDomains(
        int $days = 30,
        ?string $tld = null,
        ?string $status = null,
        int $page = 1,
        int $perPage = 25
    ): array {
        $query = Capsule::table('tbldomains')
            ->select([
                'tbldomains.id',
                'tbldomains.domain',
                'tbldomains.expirydate',
                'tbldomains.status',
                'tbldomains.userid',
                'tblclients.firstname',
                'tblclients.lastname',
                'tblclients.companyname',
                'tblclients.email'
            ])
            ->leftJoin('tblclients', 'tbldomains.userid', '=', 'tblclients.id')
            ->where('tbldomains.registrar', '=', 'ascio')
            ->whereRaw('tbldomains.expirydate BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)', [$days])
            ->orderBy('tbldomains.expirydate', 'asc');

        // Apply status filter
        if ($status !== null && in_array($status, ['Active', 'Pending'])) {
            $query->where('tbldomains.status', '=', $status);
        } else {
            $query->whereIn('tbldomains.status', ['Active', 'Pending']);
        }

        // Apply TLD filter
        if ($tld !== null && $tld !== '' && $tld !== 'all') {
            $tld = ltrim($tld, '.');
            $query->where('tbldomains.domain', 'LIKE', '%.' . $tld);
        }

        // Get total count before pagination
        $total = $query->count();

        // Apply pagination
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $domains = $query->offset($offset)->limit($perPage)->get();

        // Calculate days left for each domain
        $now = new \DateTime();
        $results = [];
        foreach ($domains as $domain) {
            $expiryDate = new \DateTime($domain->expirydate);
            $daysLeft = $now->diff($expiryDate)->days;
            if ($expiryDate < $now) {
                $daysLeft = -$daysLeft;
            }

            $results[] = [
                'id' => $domain->id,
                'domain' => $domain->domain,
                'expirydate' => $domain->expirydate,
                'days_left' => $daysLeft,
                'status' => $domain->status,
                'userid' => $domain->userid,
                'client_name' => self::formatClientName($domain),
                'client_email' => $domain->email ?? '',
            ];
        }

        return [
            'domains' => $results,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => (int) ceil($total / $perPage),
        ];
    }

    /**
     * Get expiry statistics for 30/60/90 day periods
     *
     * @return array{30: int, 60: int, 90: int, total_active: int}
     */
    public static function getExpiryStats(): array
    {
        // Check cache
        if (self::isCacheValid()) {
            return self::$statsCache;
        }

        $stats = [
            '30' => 0,
            '60' => 0,
            '90' => 0,
            'total_active' => 0,
        ];

        // Count domains expiring in 30 days
        $stats['30'] = (int) Capsule::table('tbldomains')
            ->where('registrar', '=', 'ascio')
            ->whereIn('status', ['Active', 'Pending'])
            ->whereRaw('expirydate BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)')
            ->count();

        // Count domains expiring in 60 days
        $stats['60'] = (int) Capsule::table('tbldomains')
            ->where('registrar', '=', 'ascio')
            ->whereIn('status', ['Active', 'Pending'])
            ->whereRaw('expirydate BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)')
            ->count();

        // Count domains expiring in 90 days
        $stats['90'] = (int) Capsule::table('tbldomains')
            ->where('registrar', '=', 'ascio')
            ->whereIn('status', ['Active', 'Pending'])
            ->whereRaw('expirydate BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)')
            ->count();

        // Count total active Ascio domains
        $stats['total_active'] = (int) Capsule::table('tbldomains')
            ->where('registrar', '=', 'ascio')
            ->whereIn('status', ['Active', 'Pending'])
            ->count();

        // Update cache
        self::$statsCache = $stats;
        self::$cacheTimestamp = time();

        return $stats;
    }

    /**
     * Export domains to CSV format
     *
     * @param array $domains Array of domain data from getExpiringDomains()
     * @return string CSV content
     */
    public static function exportToCsv(array $domains): string
    {
        $output = fopen('php://temp', 'r+');

        // Write header (explicitly set escape to empty string for PHP 8.4+)
        fputcsv($output, [
            'Domain',
            'Client Name',
            'Client Email',
            'Expiry Date',
            'Days Left',
            'Status',
        ], ',', '"', '');

        // Write data rows
        foreach ($domains as $domain) {
            fputcsv($output, [
                $domain['domain'],
                $domain['client_name'],
                $domain['client_email'],
                $domain['expirydate'],
                $domain['days_left'],
                $domain['status'],
            ], ',', '"', '');
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Get list of unique TLDs for Ascio domains
     *
     * @return array List of TLDs
     */
    public static function getAvailableTlds(): array
    {
        $domains = Capsule::table('tbldomains')
            ->select('domain')
            ->where('registrar', '=', 'ascio')
            ->whereIn('status', ['Active', 'Pending'])
            ->distinct()
            ->get();

        $tlds = [];
        foreach ($domains as $domain) {
            $parts = explode('.', $domain->domain, 2);
            if (isset($parts[1])) {
                $tld = $parts[1];
                if (!in_array($tld, $tlds)) {
                    $tlds[] = $tld;
                }
            }
        }

        sort($tlds);
        return $tlds;
    }

    /**
     * Clear the internal cache
     */
    public static function clearCache(): void
    {
        self::$statsCache = [];
        self::$cacheTimestamp = null;
    }

    /**
     * Check if the cache is still valid
     *
     * @return bool True if cache is valid
     */
    private static function isCacheValid(): bool
    {
        if (empty(self::$statsCache) || self::$cacheTimestamp === null) {
            return false;
        }

        return (time() - self::$cacheTimestamp) < self::CACHE_TTL;
    }

    /**
     * Format client name from domain record
     *
     * @param object $domain Domain record with client data
     * @return string Formatted client name
     */
    private static function formatClientName(object $domain): string
    {
        $name = trim(($domain->firstname ?? '') . ' ' . ($domain->lastname ?? ''));

        if (!empty($domain->companyname)) {
            if (!empty($name)) {
                return $domain->companyname . ' (' . $name . ')';
            }
            return $domain->companyname;
        }

        return $name;
    }
}
