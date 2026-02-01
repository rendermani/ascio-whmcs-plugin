<?php
namespace ascio\whmcs\ssl\test; 
require_once(__DIR__."/TestLib.php");

$whmcsUser = 1; 
$configOptions =  [
    'Registration Period (Years)' =>  '1',
    'Amount SANs' =>  0
    ];
$cert = "positivemdcssl";
$domain = "whmcs-auto-test".rand().".webrender.uk";
$useSans = true; 
$testlib = new TestLib($whmcsUser,$domain);
$testlib->setCertificateType($cert );
$testlib->submitCsr("Dns",$useSans);
$result = $testlib->submitContacts();
var_dump($result);
echo "order-id: ".$testlib->orderId."\n";
echo "service-id: ".$testlib->serviceId."\n";
echo "http://localhost/whmcs/clientarea.php?action=productdetails&id=".$testlib->serviceId."\n";

