<?php
/**
 * Ascio Auto-Expire Cron Script
 *
 * This script runs the auto-expire check for Ascio domains.
 * Can be used as an alternative to the DailyCronJob hook.
 *
 * Usage:
 *   php /path/to/modules/registrars/ascio/cron/auto_expire_check.php
 *
 * Crontab example (run daily at 2 AM):
 *   0 2 * * * /usr/bin/php /var/www/html/modules/registrars/ascio/cron/auto_expire_check.php >> /var/log/ascio-autoexpire.log 2>&1
 *
 * This implements the "AutoExpire OFF" behavior where:
 * - Domains stay Active after registration
 * - At ExpDate + Threshold date, unpaid domains are set to Expiring
 * - If domain invoice is paid, domain stays Active
 * - If domain is already Expiring and invoice gets paid, domain is Unexpired
 */

// Prevent execution via web browser
if (php_sapi_name() !== 'cli') {
    die('This script must be run from the command line.');
}

// Calculate path to WHMCS root
$whmcsRoot = realpath(__DIR__ . '/../../../../');
if (!$whmcsRoot || !file_exists($whmcsRoot . '/init.php')) {
    die("Error: Could not locate WHMCS installation. Expected at: " . ($whmcsRoot ?: 'unknown') . "\n");
}

echo "=== Ascio Auto-Expire Check ===\n";
echo "Started: " . date('Y-m-d H:i:s') . "\n";
echo "WHMCS Root: {$whmcsRoot}\n\n";

// Load WHMCS
try {
    require_once $whmcsRoot . '/init.php';
    require_once $whmcsRoot . '/includes/registrarfunctions.php';
} catch (\Exception $e) {
    die("Error loading WHMCS: " . $e->getMessage() . "\n");
}

// Load AutoExpireService
require_once __DIR__ . '/../lib/AutoExpireService.php';

use ascio\AutoExpireService;

try {
    // Get registrar configuration
    $registrarConfig = getRegistrarConfigOptions('ascio');

    if (empty($registrarConfig['Username']) || empty($registrarConfig['Password'])) {
        die("Error: Ascio registrar not configured. Please configure credentials in WHMCS.\n");
    }

    // Check if AutoExpire is OFF
    if (isset($registrarConfig['AutoExpire']) && $registrarConfig['AutoExpire'] === 'on') {
        echo "AutoExpire is ON - This feature only runs when AutoExpire is OFF.\n";
        echo "When AutoExpire is ON, domains are expired immediately after registration/transfer.\n";
        echo "This script handles threshold-based expiration for AutoExpire=OFF mode.\n";
        exit(0);
    }

    echo "AutoExpire setting: OFF (threshold-based expiration active)\n";
    echo "Test Mode: " . ($registrarConfig['TestMode'] === 'on' ? 'Yes' : 'No') . "\n\n";

    // Initialize the AutoExpireService
    $params = [
        'Username' => $registrarConfig['Username'],
        'Password' => $registrarConfig['Password'],
        'TestMode' => $registrarConfig['TestMode'] ?? '',
    ];

    $autoExpireService = new AutoExpireService($params);

    // Run the auto-expire check
    echo "--- Processing domains at threshold ---\n";
    $thresholdResults = $autoExpireService->processDomainsAtThreshold();

    echo "Processed: {$thresholdResults['processed']}\n";
    echo "Expired: {$thresholdResults['expired']}\n";
    echo "Skipped (paid): {$thresholdResults['skipped_paid']}\n";
    echo "Errors: " . count($thresholdResults['errors']) . "\n\n";

    if (!empty($thresholdResults['errors'])) {
        echo "Threshold check errors:\n";
        foreach ($thresholdResults['errors'] as $error) {
            echo "  - Domain: " . ($error['domain'] ?? 'N/A') .
                 " (ID: " . ($error['domain_id'] ?? 'N/A') . ") - " .
                 $error['error'] . "\n";
        }
        echo "\n";
    }

    echo "--- Processing expired but paid domains ---\n";
    $unexpireResults = $autoExpireService->processExpiredButPaidDomains();

    echo "Processed: {$unexpireResults['processed']}\n";
    echo "Unexpired: {$unexpireResults['unexpired']}\n";
    echo "Errors: " . count($unexpireResults['errors']) . "\n\n";

    if (!empty($unexpireResults['errors'])) {
        echo "Unexpire check errors:\n";
        foreach ($unexpireResults['errors'] as $error) {
            echo "  - Domain: " . ($error['domain'] ?? 'N/A') .
                 " (ID: " . ($error['domain_id'] ?? 'N/A') . ") - " .
                 $error['error'] . "\n";
        }
        echo "\n";
    }

    // Summary
    echo "=== Summary ===\n";
    $totalProcessed = $thresholdResults['processed'] + $unexpireResults['processed'];
    $totalErrors = count($thresholdResults['errors']) + count($unexpireResults['errors']);

    echo "Total domains processed: {$totalProcessed}\n";
    echo "Total expired: {$thresholdResults['expired']}\n";
    echo "Total unexpired: {$unexpireResults['unexpired']}\n";
    echo "Total skipped (paid): {$thresholdResults['skipped_paid']}\n";
    echo "Total errors: {$totalErrors}\n";
    echo "\nCompleted: " . date('Y-m-d H:i:s') . "\n";

    // Exit with error code if there were errors
    exit($totalErrors > 0 ? 1 : 0);

} catch (\Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
