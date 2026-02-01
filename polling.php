<?php

/**
 * Unified Ascio Polling Script
 *
 * Polls the Ascio message queue for all product types.
 * Can be run via cron for background processing.
 *
 * Usage:
 *   php polling.php                    # Poll all types
 *   php polling.php domains            # Poll domains only
 *   php polling.php ssl                # Poll SSL only
 *   php polling.php monitoring         # Poll monitoring only
 *   php polling.php defensive          # Poll defensive only
 *   php polling.php tmch               # Poll TMCH only
 *   php polling.php products           # Poll all products except domains
 *
 * For backward compatibility, the original domains/polling.php still works
 * for domain-only polling.
 */

set_time_limit(6000);

require_once realpath(dirname(__FILE__)) . '/../init.php';
require_once __DIR__ . '/core/lib/autoload.php';

use Ascio\Core\Params;
use Ascio\Core\QueuePoller;
use Ascio\Core\ObjectType;

// Parse command line arguments
$pollDomains = true;
$pollProducts = true;

if ($argc > 1) {
    $arg = strtolower($argv[1]);

    switch ($arg) {
        case 'domains':
            $pollProducts = false;
            break;
        case 'products':
            $pollDomains = false;
            break;
        case 'ssl':
        case 'monitoring':
        case 'defensive':
        case 'tmch':
            $pollDomains = false;
            $pollProducts = $arg;
            break;
    }
}

// Logger
$logger = function (string $message, array $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[{$timestamp}] {$message}\n";
    syslog(LOG_INFO, "Ascio Poll: {$message}");
};

$logger("Starting unified Ascio polling...");

// Poll domains (v2 API - existing implementation)
if ($pollDomains) {
    $logger("Polling domains...");
    pollDomains($logger);
}

// Poll products (v3 API - new implementation)
if ($pollProducts) {
    pollProducts($pollProducts, $logger);
}

$logger("Polling complete.");

/**
 * Poll domains using existing v2 API implementation.
 *
 * @param callable $logger
 */
function pollDomains(callable $logger): void
{
    require_once realpath(dirname(__FILE__)) . '/../includes/registrarfunctions.php';
    require_once realpath(dirname(__FILE__)) . '/domains/lib/Request.php';

    use ascio\v2\domains\Request;

    try {
        $cfg = getRegistrarConfigOptions('ascio');
        $request = new Request($cfg);
        $count = 0;

        $result = $request->poll();
        while ($result->item && $result->item->MsgId) {
            $item = $result->item;
            $logger("Domain message: {$item->MsgId}, Order: {$item->OrderId}, Status: {$item->OrderStatus}");
            $request->getCallbackData($item->OrderStatus, $item->MsgId, $item->OrderId, 'Poll-Message');
            $count++;
            $result = $request->poll();
        }

        $logger("Domains: processed {$count} messages");

    } catch (Exception $e) {
        $logger("Domain polling error: " . $e->getMessage());
    }
}

/**
 * Poll products using v3 API.
 *
 * @param string|bool $types Type to poll or true for all
 * @param callable $logger
 */
function pollProducts($types, callable $logger): void
{
    try {
        $params = new Params();
        $poller = new QueuePoller($params);
        $poller->setLogger($logger);

        // Register callbacks for available modules
        $modules = [
            'ssl' => [ObjectType::SSL_CERTIFICATE, 'ascio/ssl/lib/SslCallback.php', null],
            'monitoring' => [ObjectType::NAME_WATCH, 'ascio/monitoring/lib/MonitoringCallback.php', \Ascio\Monitoring\MonitoringCallback::class],
            'defensive' => [ObjectType::DEFENSIVE, 'ascio/defensive/lib/DefensiveCallback.php', \Ascio\Defensive\DefensiveCallback::class],
            'tmch' => [ObjectType::MARK, 'ascio/tmch/lib/TmchCallback.php', \Ascio\Tmch\TmchCallback::class],
        ];

        foreach ($modules as $name => [$objectType, $file, $class]) {
            // Skip if specific type requested and this isn't it
            if (is_string($types) && $types !== $name) {
                continue;
            }

            $filePath = __DIR__ . '/' . str_replace('ascio/', '', $file);
            if (!file_exists($filePath)) {
                $logger("Skipping {$name}: module not installed");
                continue;
            }

            // SSL uses different callback structure - skip for now
            if ($name === 'ssl') {
                $logger("SSL polling: use ssl module's own polling mechanism");
                continue;
            }

            require_once $filePath;
            $poller->registerCallback($objectType, $class);
            $logger("Registered {$name} callback");
        }

        // Run polling
        $results = $poller->poll();

        foreach ($results as $type => $result) {
            $typeName = ObjectType::getDisplayName($type);
            $logger("{$typeName}: {$result['processed']} processed, {$result['success']} success, {$result['failed']} failed");
        }

    } catch (Exception $e) {
        $logger("Product polling error: " . $e->getMessage());
    }
}
