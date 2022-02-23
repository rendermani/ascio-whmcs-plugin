<?php
use WHMCS\Database\Capsule;
require_once(__DIR__."/../../../../init.php");
require_once(__DIR__."/../../../../includes/registrarfunctions.php");
require_once(__DIR__."/../lib/Request.php");

// this script syncs all domains from the registrar API

$cfg = getRegistrarConfigOptions("ascio");
foreach (Capsule::table('tbldomains')->get() as $domain) {
    echo $domain->domain . PHP_EOL;
    $cfg["domainid"] = $domain->id;
    $cfg["domainname"] = $domain->domain;    
    $request = new Request($cfg);
    $request->searchDomain();
}
