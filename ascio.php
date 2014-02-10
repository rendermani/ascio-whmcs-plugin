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
require_once("ascio-lib.php");
require_once("tools.php");

function ascio_getConfigArray() {
	$configarray = array();
	return $configarray;
}

function ascio_GetNameservers($params) {
	$domain = ASCIO::searchDomain($params); 
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
	$ascioParams = Tools::mapToOrder($params, "Nameserver_Update");
	return ASCIO::request("CreateOrder", $ascioParams);
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
	$ascioParams = Tools::mapToOrder($params,"Change_Locks");
	$ascioParams->Order->Domain->TransferLock = $lockstatus;
	return ASCIO::request("CreateOrder", $ascioParams);
}

function ascio_RegisterDomain($params) {
	$ascioParams = Tools::mapToOrder($params,"Register_Domain");
	$result = ASCIO::request("CreateOrder",$ascioParams);
	if (!$result) {
	 	$values["domain"] =  $params["sld"] ."." . $params["tld"];
	 	$values["status"] = "Pending";
	 	ASCIO::callApi("updateclientdomain", $values);
	}
	return $result; 
}

function ascio_TransferDomain($params) {
	$ascioParams = Tools::mapToOrder($params,"Transfer_Domain");
	//$ascioParams->Order->Domain->AuthInfo = $params["transfersecret"];
	return ASCIO::request("CreateOrder", $ascioParams);
}

function ascio_RenewDomain($params) {
	$ascioParams = Tools::mapToOrder($params,"Renew_Domain");
	return ASCIO::request("CreateOrder", $ascioParams);
}

function ascio_ExpireDomain($params) {
	$ascioParams = Tools::mapToOrder($params,"Expire_Domain");
	return ASCIO::request("CreateOrder", $ascioParams);
}

function ascio_GetContactDetails($params) {
	$result = ASCIO::searchDomain($params);
	$name = Tools::splitName($result->Registrant->Name);

	# Put your code to get WHOIS data here
	# Data should be returned in an array as follows
	$values["Registrant"]["First Name"] = $name["first"];
	$values["Registrant"]["Last Name"]  = $name["last"];
	
	$values["Admin"]["First Name"] = $result->Admin->Firstname;
	$values["Admin"]["Last Name"] = $result->Admin->Lastname;
	$values["Tech"]["First Name"] = $result->Tech->Firstname;
	$values["Tech"]["Last Name"] = $result->Tech->Lastname;
	syslog(LOG_INFO, "GetContactDetails");
	return $values;
}

function ascio_SaveContactDetails($params) {
	$result = "";

	$old = ASCIO::searchDomain($params);
	$newRegistrant = Tools::mapContactToAscio($params["contactdetails"]["Registrant"],"Registrant");
	$newAdmin = Tools::mapContactToAscio($params["contactdetails"]["Admin"],"Contact");
	$newTech = Tools::mapContactToAscio($params["contactdetails"]["Tech"],"Contact");
	$updateRegistrant = Tools::compareRegistrant($newRegistrant, $old->Registrant);
	$updateAdmin = Tools::compareContact($newAdmin, $old->AdminContact);
	$updateTech = Tools::compareContact($newTech, $old->TechContact);	

	echo "<h2>$updateRegistrant</h2>";

	if($updateRegistrant) {
		syslog(LOG_INFO,"Update Registrant: ".$registrantResult);
		$ascioParams = Tools::mapToOrder($params, $updateRegistrant);		
		$ascioParams["order"]["Domain"]["Registrant"] = $newRegistrant;
		// Do the Adminchange within the owner-change
		if($updateAdmin && $updateRegistrant=="Owner_Change") {
			syslog(LOG_INFO,"Owner_Change + Admin_Change");
			$ascioParams["order"]["Domain"]["AdminContact"] = $newAdmin;
		}
		$registrantResult = ASCIO::request("CreateOrder", $ascioParams);		
	} 
	if($updateTech || $updateBilling || ($updateAdmin && $updateRegistrant != "Owner_Change")) {
		syslog(LOG_INFO,"Contact_Update");
		$ascioParams = Tools::mapToOrder($params,"Contact_Update");		
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
		$contactResult = ASCIO::request("CreateOrder", $ascioParams);
	}
}

function ascio_GetEPPCode($params) {
  $ascioParams = Tools::mapToOrder($params,"Update_AuthInfo");
  // todo: set AuthInfo before order;	
	$result = ASCIO::request("CreateOrder", $ascioParams, true);
	if(is_array($result)) {
		return $result;
	} else {
		return array("eppcode" => $ascioParams->Order->Domain->AuthInfo);
	}
}


function ascio_GetEmailForwarding($params) {
  $values["error"] = "Operation not allowed";
  return $values;
}

function ascio_SaveEmailForwarding($params) {
  $values["error"] = "Operation not allowed";
  return $values;
}

function ascio_GetDNS($params) {
  $values["error"] = "Operation not allowed";
  return $values;
}

function ascio_SaveDNS($params) {
  $values["error"] = "Operation not allowed";
  return $values;
}

function ascio_RegisterNameserver($params) {
  $values["error"] = "Operation not allowed";
  return $values;
}

function ascio_ModifyNameserver($params) {
  $values["error"] = "Operation not allowed";
  return $values;
}

function ascio_DeleteNameserver($params) {
  $values["error"] = "Operation not allowed";
  return $values;
}

?>
