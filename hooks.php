<?php
 function hook_set_domain_status($vars) {  

 	// what is in $vars?	
 	//this should work. it works when I call the php directly.

	// here I try to set the data to Pending
    $domain = $vars["sld"] ."." . $vars["tld"];
	$command = "updateclientdomain";
	$adminuser = "manuel";
	$values["domain"] =  $params["sld"] ."." . $params["tld"];
	$values["status"] = "Pending";
	$results = localAPI($command,$values,$adminuser); 

	// log here 
	$apiParams["Username"]= "manuel";
	$apiParams["Password"]= "smurf";
	$apiParams["Url"]= "http://localhost/whmcs/includes/api.php";
	$postfields = array();
	$postfields["action"] = "logactivity";
	$postfields["description"] = "Set domain: ". $values["domain"];
	$result = callApi($postfields,$apiParams);
 }
 
// hook_set_domain_status(array("a" => "abc"));
add_hook("AfterRegistrarRegistration",1,"hook_set_domain_status");

?>