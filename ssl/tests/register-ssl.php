<?php
namespace ascio\whmcs\ssl\test; 
require_once(__DIR__."/TestLib.php");

$whmcsUser = 1; 
$configOptions =  [
    'Registration Period (Years)' =>  '1',
    'Amount SANs' =>  0
    ];
$domain = "whmcs-auto-test".rand().".webrender.uk";

$testlib = new TestLib($whmcsUser,$domain);
$testlib->setPackageId(2);
$testlib->setCertificateType("positivessl");
$testlib->submitCsr("Dns",false);
$testlib->submitContacts();
echo "order-id: ".$testlib->orderId."\n";
echo "service-id: ".$testlib->serviceId."\n";
echo "http://localhost/whmcs/clientarea.php?action=productdetails&id=".$testlib->serviceId."\n";

//WhmcsLib::deleteOrder($testlib->orderId,$testlib->serviceId);
//WhmcsLib::deleteOrder(74,22);
//WhmcsLib::deleteOrder(75,23);
//WhmcsLib::deleteOrder(76,24);
