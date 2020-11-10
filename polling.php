<?php
set_time_limit ( 6000 );
require_once(realpath(dirname(__FILE__))."/../../../init.php");
require_once realpath(dirname(__FILE__))."/../../../includes/registrarfunctions.php";
require_once(realpath(dirname(__FILE__))."/lib/Request.php");

$pathArr = explode("/",$_SERVER['PHP_SELF']);
$account = $pathArr[count($pathArr)-1] == "polling_usd.php" ? "ascio_usd" : "ascio";
$cfg = getRegistrarConfigOptions($account);
$request = new Request($cfg);
$result = $request->poll();
while ($result->item && $result->item->MsgId) {
	echo "getMessage ".$result->item->MsgId."\n";
	$item = $result->item;
	$request->getCallbackData($item->OrderStatus,$item->MsgId,$item->OrderId,"Poll-Message");
	syslog(LOG_INFO,"Acking: ".$result->item->MsgId);
	$result = $request->poll();
}
?>