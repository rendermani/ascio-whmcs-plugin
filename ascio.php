<?php
/*
*
* Ascio Web Service 
* http://aws.ascio.info
* Author: www.ascio.com - ml@webender.de
*
*/



//
//  WHMCS functions
//
require("ascio-lib.php");

function ascio_getConfigArray() {
	$configarray = array(
	 "Username" => array( "Type" => "text", "Size" => "20", "Description" => "Enter your username here", ),
	 "Password" => array( "Type" => "password", "Size" => "20", "Description" => "Enter your password here", ),
	 "TestMode" => array( "Type" => "yesno", ),
	);
	return $configarray;
}
function ascio_GetNameservers($params) {
	$domain = searchDomain($params); 
	if (is_array($domain)) return $domain;
	$ns = $domain->NameServers;
	# Put your code to get the nameservers here and return the values below
	$values["ns1"] = $ns->NameServer1->HostName;
	$values["ns2"] = $ns->NameServer2->HostName;
    	$values["ns3"] = $ns->NameServer3->HostName;
    	$values["ns4"] = $ns->NameServer4->HostName;
	return $values;
}
function ascio_SaveNameservers($params) {
	$ascioParams = mapToOrder($params,"Nameserver_Update");
	return request("CreateOrder",$ascioParams);
}

function ascio_GetRegistrarLock($params) {
	$username = $params["Username"];
	$password = $params["Password"];
	$testmode = $params["TestMode"];
	$tld = $params["tld"];
	$sld = $params["sld"];
	# Put your code to get the lock status here
	if ($lock=="1") {
		$lockstatus="locked";
	} else {
		$lockstatus="unlocked";
	}
	return $lockstatus;
}

function ascio_SaveRegistrarLock($params) {
	if ($params["lockenabled"]) {
		$lockstatus="Lock";
	} else {
		$lockstatus="Unlock";
	}
	$ascioParams = mapToOrder($params,"Change_Locks");
	$ascioParams->Order->Domain->TransferLock = $lockstatus;
	return request("CreateOrder",$ascioParams);


}

function ascio_GetEmailForwarding($params) {
	$username = $params["Username"];
	$password = $params["Password"];
	$testmode = $params["TestMode"];
	$tld = $params["tld"];
	$sld = $params["sld"];
	# Put your code to get email forwarding here - the result should be an array of prefixes and forward to emails (max 10)
	foreach ($result AS $value) {
		$values[$counter]["prefix"] = $value["prefix"];
		$values[$counter]["forwardto"] = $value["forwardto"];
	}
	return $values;
}

function ascio_SaveEmailForwarding($params) {
	$username = $params["Username"];
	$password = $params["Password"];
	$testmode = $params["TestMode"];
	$tld = $params["tld"];
	$sld = $params["sld"];
	foreach ($params["prefix"] AS $key=>$value) {
		$forwardarray[$key]["prefix"] =  $params["prefix"][$key];
		$forwardarray[$key]["forwardto"] =  $params["forwardto"][$key];
	}
	# Put your code to save email forwarders here
}

function ascio_GetDNS($params) {
    $username = $params["Username"];
	$password = $params["Password"];
	$testmode = $params["TestMode"];
	$tld = $params["tld"];
	$sld = $params["sld"];
    # Put your code here to get the current DNS settings - the result should be an array of hostname, record type, and address
    $hostrecords = array();
    $hostrecords[] = array( "hostname" => "ns1", "type" => "A", "address" => "192.168.0.1", );
    $hostrecords[] = array( "hostname" => "ns2", "type" => "A", "address" => "192.168.0.2", );
	return $hostrecords;

}

function ascio_SaveDNS($params) {
    $username = $params["Username"];
	$password = $params["Password"];
	$testmode = $params["TestMode"];
	$tld = $params["tld"];
	$sld = $params["sld"];
    # Loop through the submitted records
	foreach ($params["dnsrecords"] AS $key=>$values) {
		$hostname = $values["hostname"];
		$type = $values["type"];
		$address = $values["address"];
		# Add your code to update the record here
	}
    # If error, return the error message in the value below
	$values["error"] = $Enom->Values["Err1"];
	return $values;
}

function ascio_RegisterDomain($params) {
	$ascioParams = mapToOrder($params,"Register_Domain");
	$result = request("CreateOrder",$ascioParams);
	if (!$result) {
		$command = "updateclientdomain";
	 	$adminuser = "manuel";
	 	$values["domain"] =  $params["sld"] ."." . $params["tld"];
	 	$values["status"] = "Pending";
	 	localAPI($command,$values,$adminuser);
	}
	return $result; 
}

function ascio_TransferDomain($params) {
	$ascioParams = mapToOrder($params,"Transfer_Domain");
	//$ascioParams->Order->Domain->AuthInfo = $params["transfersecret"];
	return request("CreateOrder",$ascioParams);
}

function ascio_RenewDomain($params) {
	$ascioParams = mapToOrder($params,"Renew_Domain");
	return request("CreateOrder",$ascioParams);
}

function ascio_ExpireDomain($params) {
	$ascioParams = mapToOrder($params,"Expire_Domain");
	return request("CreateOrder",$ascioParams);
}

function ascio_GetContactDetails($params) {
	$result = searchDomain($params);
	$name = splitName($result->Registrant->Name);

	# Put your code to get WHOIS data here
	# Data should be returned in an array as follows
	$values["Registrant"]["First Name"] = $name["first"];
	$values["Registrant"]["Last Name"]  = $name["last"];
	
	$values["Admin"]["First Name"] 		= $result->Admin->Firstname;
	$values["Admin"]["Last Name"] 		= $result->Admin->Lastname;
	$values["Tech"]["First Name"] 		= $result->Tech->Firstname;
	$values["Tech"]["Last Name"] 		= $result->Tech->Lastname;
	syslog(LOG_INFO, "GetContactDetails");
	return $values;
}

function ascio_SaveContactDetails($params) {
	$result = "";

	$old = searchDomain($params);
	$newRegistrant 	= mapContactToAscio($params["contactdetails"]["Registrant"],"Registrant");
	$newAdmin 		= mapContactToAscio($params["contactdetails"]["Admin"],"Contact");
	$newTech 		= mapContactToAscio($params["contactdetails"]["Tech"],"Contact");
	$updateRegistrant = compareRegistrant($newRegistrant,$old->Registrant);
	$updateAdmin = compareContact($newAdmin,$old->AdminContact);
	$updateTech = compareContact($newTech,$old->TechContact);	

	echo "<h2>$updateRegistrant</h2>";

	if($updateRegistrant) {
		syslog(LOG_INFO,"Update Registrant: ".$registrantResult);
		$ascioParams = mapToOrder($params,$updateRegistrant);		
		$ascioParams["order"]["Domain"]["Registrant"] = $newRegistrant;
		// Do the Adminchange within the owner-change
		if($updateAdmin && $updateRegistrant=="Owner_Change") {
			syslog(LOG_INFO,"Owner_Change + Admin_Change");
			$ascioParams["order"]["Domain"]["AdminContact"] = $newAdmin;
		}
		$registrantResult = request("CreateOrder",$ascioParams);		
	} 
	if($updateTech || $updateBilling || ($updateAdmin && $updateRegistrant!="Owner_Change")) {
		syslog(LOG_INFO,"Contact_Update");
		$ascioParams = mapToOrder($params,"Contact_Update");		
		if($updateAdmin) {
			syslog(LOG_INFO,"Update Tech");
			$ascioParams["order"]["Domain"]["AdminContact"] = $newAdmin;
		} else {
			$ascioParams["order"]["Domain"]["AdminContact"] = $old->AdminContact;
		}
		if($updateTech) {
			syslog(LOG_INFO,"Update Tech");
			$ascioParams["order"]["Domain"]["AdminContact"] = $newTech;
		} else {
			$ascioParams["order"]["Domain"]["TechContact"] = $old->TechContact;
		}
		$ascioParams["order"]["Domain"]["BillingContact"] = $old->BillingContact;
		$contactResult = request("CreateOrder",$ascioParams);
	}
}

function ascio_GetEPPCode($params) {
    $ascioParams = mapToOrder($params,"Update_AuthInfo");
    // todo: set AuthInfo before order;	
	$result = request("CreateOrder",$ascioParams,true);
	if(is_array($result)) {
		return $result;
	} else {
		return array("eppcode" => $ascioParams->Order->Domain->AuthInfo);
	}
}

function ascio_RegisterNameserver($params) {
    $username = $params["Username"];
	$password = $params["Password"];
	$testmode = $params["TestMode"];
	$tld = $params["tld"];
	$sld = $params["sld"];
    $nameserver = $params["nameserver"];
    $ipaddress = $params["ipaddress"];
    # Put your code to register the nameserver here
    # If error, return the error message in the value below
    $values["error"] = $error;
    return $values;
}

function ascio_ModifyNameserver($params) {
    $username = $params["Username"];
	$password = $params["Password"];
	$testmode = $params["TestMode"];
	$tld = $params["tld"];
	$sld = $params["sld"];
    $nameserver = $params["nameserver"];
    $currentipaddress = $params["currentipaddress"];
    $newipaddress = $params["newipaddress"];
    # Put your code to update the nameserver here
    # If error, return the error message in the value below
    $values["error"] = $error;
    //Nameserver_Update
    return $values;
}

function ascio_DeleteNameserver($params) {
    $values["error"] = "Operation not allowed";
    return $values;
}

?>
