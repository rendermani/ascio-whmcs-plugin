<?php
 function hook_set_domain_status($vars) {  

 	// what is in $vars?	
	
 	//this should work. it works when I call the php directly.
 	$debug = print_r($vars,true);

	$fp = fopen("/home/webmaste/public_html/clients/modules/registrars/ascio/debug_manuel_vars.txt", "w");
	fwrite($fp,$debug);
	fclose($fp);

	// here I try to set the data to Pending
    $domain = $vars["sld"] ."." . $vars["tld"];
	$command = "updateclientdomain";
	$adminuser = "manuel";
	$values["domain"] =  $params["sld"] ."." . $params["tld"];
	$values["status"] = "Pending";
	$results = localAPI($command,$values,$adminuser); 
 }
 
// hook_set_domain_status(array("a" => "abc"));
add_hook("AfterRegistrarRegistration",1,"hook_set_domain_status");

?>