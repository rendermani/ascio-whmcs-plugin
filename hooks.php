<?php
 require_once("lib/Request.php");
 function hook_set_domain_status($vars) {  
	if(strpos($vars["params"]["registrar"], "ascio") == -1) return;
	$request = new Request(array('Account'=> $vars["params"]["Username"],'Password' => $vars["params"]["Password"]));
	$domain = $vars["params"]["sld"].".".$vars["params"]["tld"];
	$type = $vars["params"]["regtype"] == "Transfer" ? "Transfer_Domain" : false;
	$domainObj = (Object) array ("DomainName" => $domain);
	$request->setStatus($domainObj,"Pending",$type);
 }
 
// hook_set_domain_status(array("a" => "abc"));
add_hook("AfterRegistrarRegistration",1,"hook_set_domain_status");
add_hook("AfterRegistrarTransfer",1,"hook_set_domain_status");

?>