<?php
/**
 * Health check for the cluster, please enter your cluster urls. 
 * I a domain of the last poll-message was not found on any servers, the result will be 404 - message. 
 * This script can be added to a monitoring tool. It shouldn't be run too often. Daily or hourly should be good. 
 * Also a threshold can be used in the monitoring tool, like Alert when 5 attempts. 
 */

require_once(realpath(dirname(__FILE__))."/../../../init.php");
require_once realpath(dirname(__FILE__))."/../../../includes/registrarfunctions.php";
require_once(realpath(dirname(__FILE__))."/lib/Request.php");


$cfg = getRegistrarConfigOptions("ascio");
$request = new Request($cfg);
$pollResult = $request->poll();
if($pollResult->item) {
    $orderId = $pollResult->item->OrderId;
} else {
    echo "OK\n";
    return; 
}

$order = $request->getOrder($orderId);
$order->order->TransactionComment = null;
$domainId = Tools::getDomainIdFromOrder($order->order);

if($domainId) {
    echo "OK\n";
} else {
    $error = "Domain ".$pollResult->item->DomainName. " not found in the WHMCS-Database";
    header("HTTP/1.0 404 ".$error);
    echo $error."\n";
     
}