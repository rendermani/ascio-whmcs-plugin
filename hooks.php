<?php

require_once("config.php");

function hook_set_domain_status($vars) {  

  // here I try to set the data to Pending
  $values = array();
  $values["domain"] =  $params["sld"] ."." . $params["tld"];
  $values["status"] = "Pending";
  $results = localAPI("updateclientdomain", $values, $getWHMCSCredentials());

  // log here 
  $values = array();
  $values["description"] = "Set domain: ". $values["domain"];
  $results = localAPI("logactivity", $values, $getWHMCSCredentials());
}
 
// hook_set_domain_status(array("a" => "abc"));
add_hook("AfterRegistrarRegistration", 1, "hook_set_domain_status");

?>