<?php

/**
 * Unified Ascio Callback Handler
 *
 * Handles incoming callbacks from Ascio for all product types.
 * Routes to appropriate module callback handler based on object type.
 *
 * URL Parameters:
 *   - OrderId: The Ascio order ID
 *   - MessageId: The queue message ID
 *   - OrderStatus: Current order status
 *   - ObjectType: (optional) Type of object (SslCertificateType, NameWatchType, etc.)
 *   - Object: (optional) Object identifier (domain name, etc.)
 *
 * For backward compatibility with existing domain callbacks, requests without
 * ObjectType are routed to the domain callback handler.
 */

try {
    require_once __DIR__ . '/../init.php';
    require_once __DIR__ . '/core/lib/autoload.php';

    use Ascio\Core\Params;
    use Ascio\Core\ObjectType;

    $type = $_POST ? 'POST' : 'GET';
    $params = $_POST ?: $_GET;

    syslog(LOG_INFO, "{$type}: Unified callback received from " . $_SERVER['REMOTE_ADDR']);
    syslog(LOG_INFO, print_r($params, true));

    $orderId = $params['OrderId'] ?? null;
    $messageId = $params['MessageId'] ?? null;
    $orderStatus = $params['OrderStatus'] ?? null;
    $objectType = $params['ObjectType'] ?? null;
    $object = $params['Object'] ?? null;

    if (!$orderId || !$messageId || !$orderStatus) {
        throw new Exception('Missing required callback parameters: OrderId, MessageId, OrderStatus');
    }

    echo "Callback received\n";
    echo "OrderId: {$orderId}\n";
    echo "MessageId: {$messageId}\n";
    echo "OrderStatus: {$orderStatus}\n";

    // Route based on object type
    if ($objectType) {
        echo "ObjectType: {$objectType}\n";

        $coreParams = new Params();

        switch ($objectType) {
            case ObjectType::SSL_CERTIFICATE:
            case 'SslCertificateType':
                require_once __DIR__ . '/ssl/lib/Params.php';
                require_once __DIR__ . '/ssl/lib/SslCallback.php';
                $sslParams = new \ascio\whmcs\ssl\Params();
                $callback = new \ascio\whmcs\ssl\SslCallback($sslParams, $orderId);
                $callback->process($orderId, $orderStatus, $messageId);
                echo "SSL callback processed\n";
                break;

            case ObjectType::NAME_WATCH:
            case 'NameWatchType':
                require_once __DIR__ . '/monitoring/lib/MonitoringCallback.php';
                $callback = new \Ascio\Monitoring\MonitoringCallback($coreParams, $orderId);
                $callback->process($orderId, $orderStatus, $messageId);
                echo "Monitoring callback processed\n";
                break;

            case ObjectType::DEFENSIVE:
            case 'DefensiveType':
                require_once __DIR__ . '/defensive/lib/DefensiveCallback.php';
                $callback = new \Ascio\Defensive\DefensiveCallback($coreParams, $orderId);
                $callback->process($orderId, $orderStatus, $messageId);
                echo "Defensive callback processed\n";
                break;

            case ObjectType::MARK:
            case 'MarkType':
                require_once __DIR__ . '/tmch/lib/TmchCallback.php';
                $callback = new \Ascio\Tmch\TmchCallback($coreParams, $orderId);
                $callback->process($orderId, $orderStatus, $messageId);
                echo "TMCH callback processed\n";
                break;

            case 'DomainType':
            default:
                // Fall back to domain callback for domains and unknown types
                routeToDomainCallback($orderId, $messageId, $orderStatus, $object, $type);
                break;
        }
    } else {
        // No ObjectType specified - assume domain (backward compatibility)
        routeToDomainCallback($orderId, $messageId, $orderStatus, $object, $type);
    }

    echo "Callback processed successfully\n";

} catch (Exception $e) {
    syslog(LOG_ERR, "Error processing callback: " . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
    http_response_code(500);
}

/**
 * Route to legacy domain callback handler.
 *
 * @param string $orderId
 * @param string $messageId
 * @param string $orderStatus
 * @param string|null $object
 * @param string $type
 */
function routeToDomainCallback($orderId, $messageId, $orderStatus, $object, $type): void
{
    require_once __DIR__ . '/../includes/registrarfunctions.php';
    require_once __DIR__ . '/domains/lib/Request.php';

    use ascio\Request;

    // Determine account based on request path
    $pathArr = explode('/', $_SERVER['PHP_SELF']);
    $account = (end($pathArr) === 'callbacks_usd.php') ? 'ascio_usd' : 'ascio';

    $cfg = getRegistrarConfigOptions($account);
    $request = new Request($cfg);
    $request->getCallbackData($orderStatus, $messageId, $orderId, $type);

    echo "Domain callback processed\n";
}
