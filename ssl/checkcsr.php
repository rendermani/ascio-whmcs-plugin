<?php
require_once(realpath(dirname(__FILE__))."/../../../init.php");
require_once("lib/Ssl.php");

header('Content-Type: application/json');


use ascio\whmcs\ssl\Ssl;
use ascio\whmcs\ssl\Params;
use ascio\whmcs\ssl\Fqdn; 

$ssl = new Ssl(new Params());
$csr = openssl_csr_get_subject(trim($_POST["csr"]));
$fqdn = new Fqdn($csr["CN"]);
$result  = [    
    "domainroot" => $fqdn->getDomain(),
    "emailOptions" => $ssl->getApprovalAddresses($fqdn),
    "csr" => $csr
];
echo json_encode($result);
