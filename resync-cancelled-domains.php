<?php

/**
 * Ascio remediation sweep. For every Ascio domain in WHMCS this:
 *
 *  1. Re-syncs the registry handle (tblasciohandles). A callback bug on prod
 *     (getCallbackData handle-store defects + a Domain->DomaiHandle typo, all
 *     fixed separately) left handle rows stale, wrong, or missing for ~15
 *     months. searchDomain() prefers a stored handle, so a stale row would
 *     just be re-confirmed; we therefore delete the handle first to force a
 *     fresh SearchDomain-by-name lookup, which stores the current handle.
 *
 *  2. Re-checks status (PS-166). The same lookup path (searchDomain ->
 *     setDomainStatus) corrects domains wrongly left Cancelled, so a domain is
 *     only left Cancelled when Ascio itself confirms a deleted status.
 *
 * Deleted domains legitimately return no active record: their handle stays
 * gone and their status stays Cancelled - both correct.
 *
 * Usage:
 *   php resync-cancelled-domains.php
 */

use ascio\v2\domains\Request;
use Illuminate\Database\Capsule\Manager as Capsule;

if (!function_exists('getRegistrarConfigOptions')) {
    require_once(realpath(dirname(__FILE__)) . "/../../../init.php");
    require_once realpath(dirname(__FILE__)) . "/../../../includes/registrarfunctions.php";
}
require_once(realpath(dirname(__FILE__)) . "/lib/Request.php");

function resyncCancelledDomains(?callable $requestFactory = null): array
{
    $requestFactory = $requestFactory ?? function (string $registrar, int $domainId, string $domainName): Request {
        $cfg = getRegistrarConfigOptions($registrar);
        return new Request(array_merge($cfg, [
            'domainid' => $domainId,
            'domainname' => $domainName,
        ]));
    };

    $domains = Capsule::table('tbldomains')
        ->whereIn('registrar', ['ascio', 'ascio_usd'])
        ->get(['id', 'domain', 'registrar']);

    echo "Found " . count($domains) . " Ascio domain(s) to re-sync.\n\n";

    $summary = [
        'checked' => 0,
        'corrected' => 0,
        'stillCancelled' => 0,
        'handleUpdated' => 0,
        'errors' => 0,
    ];

    foreach ($domains as $row) {
        $summary['checked']++;
        echo "[{$row->id}] {$row->domain} ({$row->registrar}): ";

        try {
            $request = $requestFactory($row->registrar, $row->id, $row->domain);

            $oldStatus = Capsule::table('tbldomains')->where('id', $row->id)->value('status');
            $oldHandle = $request->getHandle('domain', $row->id, $row->domain);

            // Force a fresh SearchDomain-by-name lookup: searchDomain() prefers
            // the stored handle, so a stale one would only be re-confirmed.
            $request->deleteOldHandle($row->id);
            $result = $request->searchDomain();

            if (is_array($result) && isset($result['error'])) {
                // Deleted/expired domains have no active record. Report but do
                // not treat as an error for still-Cancelled domains.
                echo "no active record at Ascio";
                if ($oldStatus === 'Cancelled') {
                    echo ", left as Cancelled.\n";
                    $summary['stillCancelled']++;
                } else {
                    echo " (lookup: {$result['error']}).\n";
                    $summary['errors']++;
                }
                continue;
            }

            $newStatus = Capsule::table('tbldomains')->where('id', $row->id)->value('status');
            $newHandle = $request->getHandle('domain', $row->id, $row->domain);

            $parts = [];
            if ($newHandle !== $oldHandle) {
                $summary['handleUpdated']++;
                $parts[] = "handle " . ($oldHandle ?? 'none') . " -> " . ($newHandle ?? 'none');
            } else {
                $parts[] = "handle unchanged (" . ($newHandle ?? 'none') . ")";
            }

            if ($newStatus !== $oldStatus) {
                $summary['corrected']++;
                $parts[] = "status {$oldStatus} -> {$newStatus}";
            } elseif ($newStatus === 'Cancelled') {
                $summary['stillCancelled']++;
            }

            echo implode(', ', $parts) . ".\n";
        } catch (\Throwable $e) {
            echo "ERROR - " . $e->getMessage() . "\n";
            $summary['errors']++;
        }
    }

    echo "\n=== Summary ===\n";
    echo "Checked:              {$summary['checked']}\n";
    echo "Handles updated:      {$summary['handleUpdated']}\n";
    echo "Status corrected:     {$summary['corrected']}\n";
    echo "Confirmed cancelled:  {$summary['stillCancelled']}\n";
    echo "Errors/skipped:       {$summary['errors']}\n";

    return $summary;
}

if (php_sapi_name() === 'cli' && isset($argv[0]) && realpath($argv[0]) === __FILE__) {
    resyncCancelledDomains();
}
