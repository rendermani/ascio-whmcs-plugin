<?php
/**
 * Ascio Callback Handler
 *
 * Processes callbacks from Ascio for order status updates.
 * Supports both v2 and v3 APIs with automatic version detection.
 *
 * v2 API: Uses GetMessageQueue to retrieve callback data
 * v3 API: Uses GetQueueMessage to retrieve callback data
 *
 * The API version is determined by:
 * 1. Environment variable ASCIO_USE_V3 (true/1/yes)
 * 2. Registrar config setting ApiVersion ('v3')
 * 3. Default: v2 for backward compatibility
 */

use ascio\v2\domains\Request as RequestV2;
use ascio\v3\domains\Request as RequestV3;
use ascio\ApiVersion;

try {
	require_once("../../../init.php");
	require_once "../../../includes/registrarfunctions.php";
	require_once("lib/Request.php");
	require_once("lib/RequestV3.php");
	require_once("lib/ApiVersion.php");

	$type = $_POST ? "POST" : "GET";
	syslog(LOG_INFO, $type . ": Callback received from ".$_SERVER['REMOTE_ADDR']);
	syslog(LOG_INFO, print_r($_GET, 1));
	syslog(LOG_INFO, print_r($_POST, 1));

	$orderId = $_GET["OrderId"];
	$messageId = $_GET["MessageId"];
	$orderStatus = $_GET["OrderStatus"];
	$domain = $_GET["Object"];

	if(!($orderId && $messageId && $orderStatus)) {
		throw new Exception("Please provide callback parameters: OrderId, MessageId, and OrderStatus are required", 1);
	}

	echo "Callback received, ";
	echo "OrderId: ".$orderId. ", ";
	echo "MessageId: ".$messageId. ", ";
	echo "OrderStatus: ".$orderStatus;

	// Determine which account to use (for multi-brand setup)
	// This allows a second registrar module (ascio_usd) to be installed
	$pathArr = explode("/", $_SERVER['PHP_SELF']);
	$account = $pathArr[count($pathArr)-1] == "callbacks_usd.php" ? "ascio_usd" : "ascio";
	$cfg = getRegistrarConfigOptions($account);

	// Determine API version and process callback accordingly
	$apiVersion = ApiVersion::getVersion($cfg);
	syslog(LOG_INFO, "Ascio callback: Using API version " . $apiVersion);

	if ($apiVersion === ApiVersion::VERSION_V3) {
		// Use v3 API
		$request = new RequestV3($cfg);

		try {
			// v3 uses getQueueMessage instead of getMessageQueue
			$result = $request->getQueueMessage($messageId);

			if (is_array($result) && isset($result['error'])) {
				throw new Exception("v3 API error: " . $result['error']);
			}

			// Process the callback data using v2 request for compatibility
			// (v2 Request has all the WHMCS integration logic)
			$requestV2 = new RequestV2($cfg);
			$requestV2->getCallbackData($orderStatus, $messageId, $orderId, $type);

			// Acknowledge the message using v3 API
			$ackResult = $request->ackQueueMessage($messageId);

			if (is_array($ackResult) && isset($ackResult['error'])) {
				syslog(LOG_WARNING, "Ascio v3: Failed to acknowledge message " . $messageId . ": " . $ackResult['error']);
			} else {
				syslog(LOG_INFO, "Ascio v3: Successfully acknowledged message " . $messageId);
			}
		} catch (Exception $v3Error) {
			// Log the v3 error
			syslog(LOG_WARNING, "Ascio v3 callback error, falling back to v2: " . $v3Error->getMessage());

			// Fallback to v2 API during transition period
			$request = new RequestV2($cfg);
			$request->getCallbackData($orderStatus, $messageId, $orderId, $type);
		}
	} else {
		// Use v2 API (default)
		$request = new RequestV2($cfg);
		$request->getCallbackData($orderStatus, $messageId, $orderId, $type);
	}

	echo " - Callback received and processed by WHMCS";
} catch (Exception $e) {
	echo "Something unexpected happened: ";
	syslog(LOG_ERR, "Error processing callback: " . $e->getMessage());
	var_dump($e->getMessage());
}

?>
