<?php
require_once(realpath(dirname(__FILE__))."/../../../init.php");
require_once("lib/Ssl.php");

header('Content-Type: application/json');

use ascio\whmcs\ssl\Fqdn;
use ascio\whmcs\ssl\Ssl; 
use ascio\whmcs\ssl\Params; 

$fqdn = new Fqdn($_POST["fqdn"]);
$ssl = new Ssl(new Params());
echo json_encode(["html"=>$ssl->getApprovalAddresses($fqdn)]);

