<?php
set_time_limit ( 6000 );
require_once("../../../init.php");
require_once "../../../includes/registrarfunctions.php";
require_once("lib/Request.php");

$pathArr = split("/",$_SERVER['PHP_SELF']);
$account = $pathArr[count($pathArr)-1] == "polling_usd.php" ? "ascio_usd" : "ascio";
$cfg = getRegistrarConfigOptions($account);
$request = new Request($cfg);
$result = $request->poll();
while ($result->item && $result->item->MsgId) {
	echo "getMessage ".$result->item->MsgId."\n";
	$item = $result->item;
	$request->getCallbackData($item->OrderStatus,$item->MsgId,$item->OrderId);
	syslog(LOG_INFO,"Acking: ".$result->item->MsgId);
	$result = $request->poll();
}
?>