<?php
/**
 * Ascio Message Queue Polling
 *
 * Polls the Ascio message queue for pending notifications and processes them.
 * Supports both v2 and v3 APIs with automatic version detection.
 *
 * v2 API methods:
 *   - PollMessage: Poll for next message in queue
 *   - AckMessage: Acknowledge/remove message from queue
 *
 * v3 API methods:
 *   - PollQueue: Poll for next message in queue
 *   - AckQueueMessage: Acknowledge/remove message from queue
 *
 * The API version is determined by:
 * 1. Environment variable ASCIO_USE_V3 (true/1/yes)
 * 2. Registrar config setting ApiVersion ('v3')
 * 3. Default: v2 for backward compatibility
 */

use ascio\v2\domains\Request as RequestV2;
use ascio\v3\domains\Request as RequestV3;
use ascio\ApiVersion;

// Allow long-running polling process
set_time_limit(6000);

require_once(realpath(dirname(__FILE__))."/../../../init.php");
require_once realpath(dirname(__FILE__))."/../../../includes/registrarfunctions.php";
require_once(realpath(dirname(__FILE__))."/lib/Request.php");
require_once(realpath(dirname(__FILE__))."/lib/RequestV3.php");
require_once(realpath(dirname(__FILE__))."/lib/ApiVersion.php");

// Determine which account to use (for multi-brand setup)
$pathArr = explode("/", $_SERVER['PHP_SELF']);
$account = $pathArr[count($pathArr)-1] == "polling_usd.php" ? "ascio_usd" : "ascio";
$cfg = getRegistrarConfigOptions($account);

// Determine API version
$apiVersion = ApiVersion::getVersion($cfg);
syslog(LOG_INFO, "Ascio polling: Starting with API version " . $apiVersion);
echo "Ascio polling: Using API " . $apiVersion . "\n";

if ($apiVersion === ApiVersion::VERSION_V3) {
	// v3 API polling
	pollWithV3Api($cfg);
} else {
	// v2 API polling (default)
	pollWithV2Api($cfg);
}

/**
 * Poll messages using Ascio v3 API
 *
 * @param array $cfg Registrar configuration
 * @return void
 */
function pollWithV3Api($cfg) {
	$requestV3 = new RequestV3($cfg);
	$requestV2 = new RequestV2($cfg); // For callback processing (has WHMCS integration logic)

	try {
		$result = $requestV3->poll();

		// Check for poll errors
		if (is_array($result) && isset($result['error'])) {
			syslog(LOG_WARNING, "Ascio v3 poll error, falling back to v2: " . $result['error']);
			echo "v3 poll error, falling back to v2\n";
			pollWithV2Api($cfg);
			return;
		}

		// Process messages while there are items in the queue
		// v3 response structure may differ from v2
		while (hasMessage($result)) {
			$item = getMessageItem($result);
			$msgId = $item->MsgId ?? $item->msgId ?? null;

			if (!$msgId) {
				syslog(LOG_WARNING, "Ascio v3 poll: No message ID found in result");
				break;
			}

			echo "v3 getMessage " . $msgId . "\n";
			syslog(LOG_INFO, "Ascio v3 poll: Processing message " . $msgId);

			// Get order status from the message
			$orderStatus = $item->OrderStatus ?? $item->orderStatus ?? null;
			$orderId = $item->OrderId ?? $item->orderId ?? null;

			// Process the callback data using v2 Request (has all WHMCS integration)
			if ($orderStatus && $orderId) {
				$requestV2->getCallbackData($orderStatus, $msgId, $orderId, "Poll-Message-V3");
			}

			// Acknowledge the message using v3 API
			syslog(LOG_INFO, "Ascio v3 poll: Acking " . $msgId);
			$ackResult = $requestV3->ackQueueMessage($msgId);

			if (is_array($ackResult) && isset($ackResult['error'])) {
				syslog(LOG_WARNING, "Ascio v3: Failed to acknowledge message " . $msgId . ": " . $ackResult['error']);
			}

			// Poll for next message
			$result = $requestV3->poll();

			// Check if poll returned an error
			if (is_array($result) && isset($result['error'])) {
				syslog(LOG_ERR, "Ascio v3 poll error during loop: " . $result['error']);
				break;
			}
		}

		echo "v3 polling completed\n";
		syslog(LOG_INFO, "Ascio v3 poll: Completed");

	} catch (Exception $e) {
		syslog(LOG_ERR, "Ascio v3 poll exception, falling back to v2: " . $e->getMessage());
		echo "v3 exception, falling back to v2: " . $e->getMessage() . "\n";

		// Fallback to v2 during transition period
		pollWithV2Api($cfg);
	}
}

/**
 * Poll messages using Ascio v2 API (original implementation)
 *
 * @param array $cfg Registrar configuration
 * @return void
 */
function pollWithV2Api($cfg) {
	$request = new RequestV2($cfg);
	$result = $request->poll();

	while ($result->item && $result->item->MsgId) {
		echo "v2 getMessage " . $result->item->MsgId . "\n";
		$item = $result->item;
		$request->getCallbackData($item->OrderStatus, $item->MsgId, $item->OrderId, "Poll-Message");
		syslog(LOG_INFO, "Ascio v2 poll: Acking " . $result->item->MsgId);
		$result = $request->poll();
	}

	echo "v2 polling completed\n";
	syslog(LOG_INFO, "Ascio v2 poll: Completed");
}

/**
 * Check if poll result contains a message
 *
 * @param mixed $result Poll result
 * @return bool True if there is a message to process
 */
function hasMessage($result) {
	if (is_array($result)) {
		return false;
	}

	// Check v3 response structure
	if (isset($result->Message) && $result->Message) {
		return true;
	}

	// Check v2-compatible structure
	if (isset($result->item) && $result->item) {
		$item = $result->item;
		return isset($item->MsgId) && $item->MsgId;
	}

	return false;
}

/**
 * Get the message item from poll result
 *
 * @param mixed $result Poll result
 * @return object|null The message item
 */
function getMessageItem($result) {
	// v3 response may use Message instead of item
	if (isset($result->Message)) {
		return $result->Message;
	}

	// v2-compatible structure
	if (isset($result->item)) {
		return $result->item;
	}

	return null;
}

?>
