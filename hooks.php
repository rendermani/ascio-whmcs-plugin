<?php
 require_once(__DIR__."/lib/Request.php");
 function hook_set_domain_status($vars) {  
	if(strpos($vars["params"]["registrar"], "ascio") == false) return;	
	$request = new Request(array('Account'=> $vars["params"]["Username"],'Password' => $vars["params"]["Password"]));
	$domain = $vars["params"]["sld"].".".$vars["params"]["tld"];
	logActivity("Calling hook for domain ".$domain);
	$type = $vars["params"]["regtype"] == "Transfer" ? "Transfer_Domain" : false;
	$domainObj = (Object) array ("DomainName" => $domain);
	$request->setStatus($domainObj,"Pending",$type);
 }
 
add_hook("AfterRegistrarRegistration",1,"hook_set_domain_status");
add_hook("AfterRegistrarTransfer",1,"hook_set_domain_status");

?>