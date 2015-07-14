<?php

try {
	require_once("../../../init.php");
	require_once "../../../includes/registrarfunctions.php";
	require_once("lib/Request.php");
	$type = $_POST ? "POST" : "GET";
	syslog(LOG_INFO,$type . ": Callback received from ".$_SERVER['REMOTE_ADDR']);
	syslog(LOG_INFO, print_r($_GET,1));
	syslog(LOG_INFO, print_r($_POST,1));
	
	$orderId = $_GET["OrderId"];
	$messageId = $_GET["MessageId"];
	$orderStatus = $_GET["OrderStatus"];
	$domain = $_GET["Object"];

	if(!($orderId && $messageId && $orderStatus)) throw new Exception("Please provide callback parameters", 1);

	echo "Callback received, ";
	echo "OrderId: ".$orderId. ", ";
	echo "MessageId: ".$messageId. ", ";
	echo "OrderStatus: ".$orderStatus;	 

	// this is when usd and eur account is used. In this case a second registrar module can be installed.
	// please ask manuel.lautenschlager@ascio.com for the code
	$path = pathinfo(__PATH__);
	$pathArr = split("/",$_SERVER['PHP_SELF']);
	$account = $pathArr[count($pathArr)-1] == "callbacks_usd.php" ? "ascio_usd" : "ascio";
	$cfg = getRegistrarConfigOptions($account);
	$request = new Request($cfg);
	$request->getCallbackData($orderStatus,$messageId,$orderId);
	echo "Callback received and processed by WHMCS";
} catch (Exception $e) {
	echo "Something unexpected happened: ";
	syslog(LOG_INFO, "Error processing callback: ".$e);
	var_dump($e);
}

?>