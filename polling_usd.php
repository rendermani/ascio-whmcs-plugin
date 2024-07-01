<?php

use ascio\v2\domains\Request as Request;

set_time_limit ( 6000 );
require_once("../../../init.php");
require_once "../../../includes/registrarfunctions.php";
require_once("lib/Request.php");

$pathArr = explode("/",$_SERVER['PHP_SELF']);
$account = $pathArr[count($pathArr)-1] == "polling_usd.php" ? "ascio_usd" : "ascio";
$cfg = getRegistrarConfigOptions($account);
$request = new Request($cfg);
$result = $request->poll();
while ($result->item && $result->item->MsgId) {
	$item = $result->item;
	$request->getCallbackData($item->OrderStatus,$item->MsgId,$item->OrderId,null);
	syslog(LOG_INFO,"Acking: ".$result->item->MsgId);
	$result = $request->poll();
}
?>