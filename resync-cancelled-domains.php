<?php

/**
 * PS-166 remediation: re-check every Ascio domain currently marked Cancelled
 * in WHMCS against the live Ascio v3 API, and correct any that are actually
 * still active/pending at the registry.
 *
 * A bug (fixed separately) caused failed/malformed Ascio lookups during
 * routine operations to be misreported as deletions, wrongly cancelling
 * active domains in WHMCS. This script finds domains still carrying that
 * incorrect status and re-syncs them using the same lookup path
 * (Request::searchDomain -> setDomainStatus) used by normal operation,
 * so a domain is only left/changed to Cancelled when Ascio itself confirms
 * a deleted status.
 *
 * Usage:
 *   php resync-cancelled-domains.php
 */

use ascio\Request;
use WHMCS\Database\Capsule;

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

    $cancelledDomains = Capsule::table('tbldomains')
        ->whereIn('registrar', ['ascio', 'ascio_usd'])
        ->where('status', 'Cancelled')
        ->get(['id', 'domain', 'registrar']);

    echo "Found " . count($cancelledDomains) . " Ascio domain(s) currently marked Cancelled.\n\n";

    $summary = ['checked' => 0, 'corrected' => 0, 'stillCancelled' => 0, 'errors' => 0];

    foreach ($cancelledDomains as $row) {
        $summary['checked']++;
        echo "[{$row->id}] {$row->domain} ({$row->registrar}): ";

        try {
            $request = $requestFactory($row->registrar, $row->id, $row->domain);
            $result = $request->searchDomain();

            if (is_array($result) && isset($result['error'])) {
                echo "SKIPPED - lookup failed: {$result['error']}\n";
                $summary['errors']++;
                continue;
            }

            $newStatus = Capsule::table('tbldomains')->where('id', $row->id)->value('status');

            if ($newStatus === 'Cancelled') {
                echo "no active/pending record found at Ascio, left as Cancelled.\n";
                $summary['stillCancelled']++;
            } else {
                echo "CORRECTED - Ascio reports domain is actually '{$newStatus}'.\n";
                $summary['corrected']++;
            }
        } catch (\Throwable $e) {
            echo "ERROR - " . $e->getMessage() . "\n";
            $summary['errors']++;
        }
    }

    echo "\n=== Summary ===\n";
    echo "Checked:              {$summary['checked']}\n";
    echo "Corrected (unstuck):  {$summary['corrected']}\n";
    echo "Confirmed cancelled:  {$summary['stillCancelled']}\n";
    echo "Errors/skipped:       {$summary['errors']}\n";

    return $summary;
}

if (php_sapi_name() === 'cli' && isset($argv[0]) && realpath($argv[0]) === __FILE__) {
    resyncCancelledDomains();
}
