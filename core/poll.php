<?php

/**
 * Unified polling endpoint for all Ascio products.
 * Run via cron to process async order status updates.
 *
 * Usage:
 *   php poll.php                    # Poll all registered types
 *   php poll.php ssl                # Poll SSL only
 *   php poll.php monitoring         # Poll monitoring only
 *   php poll.php defensive          # Poll defensive only
 *   php poll.php tmch               # Poll TMCH only
 */

require_once __DIR__ . '/lib/autoload.php';

use Ascio\Core\Params;
use Ascio\Core\QueuePoller;
use Ascio\Core\ObjectType;

// Initialize WHMCS if running within WHMCS context
$whmcsInit = realpath(__DIR__ . '/../../init.php');
if (file_exists($whmcsInit)) {
    require_once $whmcsInit;
}

// Map CLI arguments to object types
$typeMap = [
    'ssl' => ObjectType::SSL_CERTIFICATE,
    'monitoring' => ObjectType::NAME_WATCH,
    'defensive' => ObjectType::DEFENSIVE,
    'tmch' => ObjectType::MARK,
];

// Parse command line arguments
$pollTypes = [];
if ($argc > 1) {
    for ($i = 1; $i < $argc; $i++) {
        $arg = strtolower($argv[$i]);
        if (isset($typeMap[$arg])) {
            $pollTypes[] = $typeMap[$arg];
        }
    }
}

// Simple logger for CLI
$logger = function (string $message, array $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[{$timestamp}] {$message}\n";
    if (!empty($context)) {
        echo "  Context: " . json_encode($context) . "\n";
    }
};

try {
    $params = new Params();
    $poller = new QueuePoller($params);
    $poller->setLogger($logger);

    // Register callbacks (modules register themselves when loaded)
    // SSL Callback
    $sslCallback = realpath(__DIR__ . '/../ssl/lib/SslCallback.php');
    if (file_exists($sslCallback)) {
        require_once __DIR__ . '/../ssl/lib/Params.php';
        require_once __DIR__ . '/../ssl/lib/SslCallback.php';
        // SslCallback uses different namespace, need adapter
    }

    // Monitoring Callback
    $monitoringCallback = realpath(__DIR__ . '/../monitoring/lib/MonitoringCallback.php');
    if (file_exists($monitoringCallback)) {
        require_once $monitoringCallback;
        $poller->registerCallback(ObjectType::NAME_WATCH, \Ascio\Monitoring\MonitoringCallback::class);
    }

    // Defensive Callback
    $defensiveCallback = realpath(__DIR__ . '/../defensive/lib/DefensiveCallback.php');
    if (file_exists($defensiveCallback)) {
        require_once $defensiveCallback;
        $poller->registerCallback(ObjectType::DEFENSIVE, \Ascio\Defensive\DefensiveCallback::class);
    }

    // TMCH Callback
    $tmchCallback = realpath(__DIR__ . '/../tmch/lib/TmchCallback.php');
    if (file_exists($tmchCallback)) {
        require_once $tmchCallback;
        $poller->registerCallback(ObjectType::MARK, \Ascio\Tmch\TmchCallback::class);
    }

    // Run polling
    $logger("Starting Ascio queue polling...");

    if (!empty($pollTypes)) {
        $results = $poller->pollTypes($pollTypes);
    } else {
        $results = $poller->poll();
    }

    // Report results
    foreach ($results as $type => $result) {
        $typeName = ObjectType::getDisplayName($type);
        $logger("{$typeName}: Processed {$result['processed']}, Success {$result['success']}, Failed {$result['failed']}");

        if (!empty($result['errors'])) {
            foreach ($result['errors'] as $error) {
                $logger("  Error: Order {$error['orderId']} - {$error['error']}");
            }
        }
    }

    $logger("Polling complete.");

} catch (\Exception $e) {
    $logger("Fatal error: " . $e->getMessage());
    exit(1);
}
