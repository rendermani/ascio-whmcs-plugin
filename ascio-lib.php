<?php
require_once("config.php");

Class SessionCache {
	public static function get() {
		$filename = dirname(realpath ( __FILE__ ))."/ascio-session.txt";
		$fp = fopen($filename,"r");
		$contents = fread($fp, filesize($filename));
		fclose($fp);
		if(trim($contents) == "false") $contents = false;
		return $contents;
	}
	public static function put($sessionId) {
		$filename = dirname(realpath ( __FILE__ ))."/ascio-session.txt";
		$fp = fopen($filename,"w");		
		fwrite($fp,$sessionId);
		fclose($fp);
	}
	public static function clear() {
		SessionCache::put("false");
	}
}
function login($params) {
	$session = array(
	             'Account'=> $params["Username"],
	             'Password' =>  $params["Password"]
	);
	return sendRequest('LogIn',array('session' => $session ));
	 
};
function request($functionName, $ascioParams, $outputResult=false)  {	
	$sessionId = SessionCache::get();	
	if (!$sessionId) {		
		$result = login(getAscioCredentials()); 
		if(is_array($result)) return $result;
		$ascioParams["sessionId"] = $result->sessionId; 		
		SessionCache::put($result->sessionId);
	} else {		
		$ascioParams["sessionId"] = $sessionId; 
	}
	$result = sendRequest($functionName,$ascioParams);
	if(is_array($result) && strpos($result["error"],"Invalid Session") > -1) {
		SessionCache::clear();
		return request($functionName, $ascioParams, $outputResult);		
	} else {		
		if($outputResult) return $result;
		
	}	
	return;
};
function sendRequest($functionName,$ascioParams) {
		syslog(LOG_INFO, $functionName  );
		$ascioParams = cleanAscioParams($ascioParams);
        $client = new SoapClient(getAscioWsdl(),array( "trace" => 1 ));
        $result = $client->__call($functionName, array('parameters' => $ascioParams));        
		$resultName = $functionName . "Result";	
		$status = $result->$resultName;
		if ( $status->ResultCode==200) {
			return $result;
		} else {
			if (count($status->Values->string) > 1 ){
				$messages = join("<br/>\n",$status->Values->string);	
			} else {
				$messages = $status->Values->string;
			}
			return array('error' => $status->Message . "<br/>\n" .$messages);
		}     
};
function getDomain($handle) {
	$ascioParams = array(
		'sessionId' => 'mySessionId',
		'domainHandle' => $handle
	);
	$result = request("GetDomain", $ascioParams,true); 
	return $result;
}
function searchDomain($params) {
	$criteria= array(
		'Mode' => 'Strict',
		'Clauses' => Array(
			'Clause' => Array(
				'Attribute' => 'DomainName', 
				'Value' => $params["sld"] . "." . $params["tld"] , '
				Operator' => 'Is'
			)
		)
	);
	$ascioParams = array(
		'sessionId' => 'mySessionId',
		'criteria' => $criteria
	);
	$result = request("SearchDomain",$ascioParams,true);
	if(is_array($result)) return $result;
	else return $result->domains->Domain;
}
function splitName($name) {
	$spacePos = strpos($name," ");
	$out = array();
	$out["first"] = substr($name,0,$spacePos);
	$out["last"] = substr($name, $spacePos+1);
	return $out;
}
function getCallbackData($orderStatus,$messageId,$orderId) {
	global $config;
	// get message
	$ascioParams = array(
		'sessionId' => 'mySessionId',
		'msgId' => $messageId
	);
	$result = request("GetMessageQueue", $ascioParams,true);  
	$domainName = $result->item->DomainName;
	if ($orderStatus=="Completed") {
		$status = "Active";
	} else {
		$status =  ($orderStatus);
	}
	
	// External WHMCS API: Set Status
	$postfields = array();
	$postfields["domain"] = $domainName;
	$postfields["status"] = $status;
	$results = callApi("updateclientdomain", $postfields);
  $id = $results["domainid"];
	// External WHMCS API: Send Mail
	$msgPart = "Domain order ". $id . ", ".$domainName;
	if ($orderStatus=="Completed") {
		$message =formatOK($msgPart);
	} else {
		$message = formatError($result->item->StatusList->CallbackStatus,$msgPart);
	}
	$postfields = array();
	$postfields["customtype"] = "domain";
	$postfields["customsubject"] = $msgPart ." ". strtolower($status);
	$postfields["custommessage"] = $message;
	$postfields["id"] = $id;
	callApi("sendemail", $postfields);
	// Ascio ACK Message
	$ascioParams = array(
		'sessionId' => 'mySessionId',
		'msgId' => $messageId
	);
	syslog(LOG_INFO,$domainName . ": ". $status);
	$order = getOrder($orderId);
	sendAuthCode($order->order);
	$result = request("AckMessage", $ascioParams,true); 
}
function getOrder($orderId) {
	$ascioParams = array(
		'sessionId' => 'mySessionId',
		'orderId' => $orderId
	);
	$result = request("GetOrder", $ascioParams,true); 
	return $result;
}
function sendAuthCode($order) {
	if($order->Type != "Update_AuthInfo") return;
	$domain = getDomain($order->Domain->DomainHandle);
	syslog(LOG_INFO,"EPP Code: ". $domain->domain->AuthInfo);
	$msg = "New AuthCode generated for ".$domain->domain->DomainName . ": ".$domain->domain->AuthInfo;
	$postfields = array();
	$postfields["customtype"] = "domain";
	$postfields["customsubject"] = $domain->domain->DomainName . ": New AuthCode generated";
	$postfields["custommessage"] = $msg;
	$postfields["id"] = $order->OrderId;
	callApi("sendemail", $postfields);
}
function poll() {
	$params = getAscioCredentials();
	$ascioParams = array(
		'sessionId' => 'mySessionId',
		'msgType' 	=> 'Message_to_Partner'
	);
	$result = request("PollMessage",$ascioParams,true);
	if(is_array($result)) return $result;
	else return $result;
}
function ack($msgId) {
	$ascioParams = array(
		'sessionId' => 'mySessionId',
		'msgId' 	=> $msgId
	);
	$result = request("AckMessage",$ascioParams,true);
	if(is_array($result)) return $result;
	else return $result;
}
function mapToOrder ($params,$orderType) {
	$domainName = $params["sld"] ."." . $params["tld"];
	syslog(LOG_INFO,  $orderType . ": ".$domainName);
	$registrant = mapToContact($params,"Registrant");
	$order = 
		array( 
		'Type' => $orderType, 
		'Domain' => array( 
			'DomainName' => $domainName,
			'RegPeriod' =>  $params["regperiod"],
			'AuthInfo'	=> 	$params["eppcode"],
			'DomainPurpose' =>  $params["Application Purpose"],
			'Registrant' 	=> mapToContact($params,"Registrant"),
			'AdminContact' 	=> mapToContact($params,"Admin"), 
			'TechContact' 	=> mapToContact($params,"Admin"), 
			'BillingContact'=> mapToContact($params,"Admin"),
			'NameServers' 	=> mapToNameservers($params),
			'Comment'		=> $params["userid"]
			),
		'Comments'	=>	$params["userid"]
		); 
	//echo(nl2br(print_r($order,1)));
	return array(
			'sessionId' => "set-it-later",
			'order' => $order
        );
}
// map contact from Ascio to WHMCS - admincompanyname
function mapToContact($params,$type) {
	$contactName = array();
	$prefix = "";
	if($type == "Registrant") {
		$contactName["Name"] = $params["firstname"] . " " . $params["lastname"];
		//$contactName["NexusCategory"] = $params["Nexus Category"];
		//$contactName["RegistrantNumber"] = "55203780600585";
	} else {
		$prefix = strtolower($type);
		$contactName["FirstName"] = $params[$prefix . "firstname"];
		$contactName["LastName"] = $params[$prefix . "lastname"];
	}
	$contact = Array(
		'OrgName' 		=>  $params[$prefix . "companyname"],
		'Address1' 		=>  $params[$prefix . "address1"],	
		'Address2' 		=>  $params[$prefix . "address2"],
		'PostalCode' 	=>  $params[$prefix . "postcode"],
		'City' 			=>  $params[$prefix . "city"],
		'State' 		=>  $params[$prefix . "state"],		
		'CountryCode' 	=>  $params[$prefix . "country"],
		'Email' 		=>  $params[$prefix . "email"],
		'Phone'			=>  $params[$prefix . "phonenumber"],
		'Fax' 			=> 	$params[$prefix . "faxnumber"]);
			
	return array_merge($contactName,$contact);
}
// WHMCS has 2 contact structures. Flat and nested.
// This function in converting from adminfirstname to Admin["First Name"]
function mapContactToAscio($params,$type) {

	$ascio = (object) array(
		'OrgName'  				=> $params["Organisation Name"],
		'Address1'  			=> $params["Address 1"],
		'Address2'  			=> $params["Address 2"],
		'PostalCode'  			=> $params["ZIP Code"],
		'City'  				=> $params["City"],
		'State'	  				=> $params["State"],
		'CountryCode'  			=> $params["Country"],
		'Email'  				=> $params["Email"],
		'Phone'  				=> $params["Phone"],
		'Fax'  					=> $params["Fax"]
	);
	if($type=="Registrant") {
		$ascio->Name = $params["First Name"]. " ". $params["Last Name"];		
	} else {
		$ascio->FirstName 	= $params["First Name"];
		$ascio->LastName 	= $params["Last Name"];
	}
	return $ascio; 

}
function mapToNameservers($params) {
	return array (
				'NameServer1' => Array('HostName' => $params["ns1"]), 
				'NameServer2' => Array('HostName' => $params["ns2"]),
				'NameServer3' => Array('HostName' => $params["ns3"]),
				'NameServer4' => Array('HostName' => $params["ns4"])
	);
}
function cleanAscioParams($ascioParams) {
	foreach ($ascioParams as $key => $value) {
		if(is_array($value)) {
			$ascioParams[$key] = cleanAscioParams($value);			
		} elseif (strlen($value) > 0) {
			$ascioParams[$key] =$value;	
		}
	}
	return $ascioParams;
}
function callApi($command, $params) {
   $results = localAPI($command, $params, $getWHMCSCredentials());

	 if ($results["result"]=="success") {
	   # Result was OK!
	 } else {
	   # An error occured	 	
	   echo "The following error occured: ".$results["message"];
	 }
	 return $results;
}
function formatError($items,$message) {
	$html = "<h2>Following errors occurred in: ".$message."</h2><ul>";
	if (!is_array($items)) $items = array($items);
	foreach ($items as $nr => $item) {
		$html .= "<li style='list-style-type: disc; color: red;'>".$item->Message."</li>";
	}
	$html .= "</ul><p>Please change your settings and resubmit the order.</p>";
	return $html;	
}
function formatOK($message) {
	$html = "<h2>Order completed:".$message.":</h2>";
	return $html;	
}
function mapResult($status) {
	$resultMap = array (
		"Completed" => "Active",
		"Failed"	=> "Cancelled",
		"Invalid"	=> "Cancelled",
		"Documentation_Not_Approved" => "Cancelled",
		"Pending_Documentation" => "Pending",
		"Pending_End_User_Action" => "Pending",
		"Pending_Post_Processing" => "Pending",
		"Pending_NIC_Processing" => "Pending"
	);
	return $resultMap[$status];
}
function diffContact($newContact,$oldContact) {
	if($newContact->City == NULL) return array();
	$diffs  = array();	
	foreach (get_object_vars($newContact) as $key => $value) {
		$originalValue = replaceSpecialCharacters($oldContact->$key);
		if($value != $originalValue ) {
			$diffs[$key] = $value;
			//echo "$key:".$value . " != ". $originalValue  . "<br/>";
		} 		
	}	
	return $diffs;
}
function compareRegistrant($newContact,$oldContact) {
	$diffs = diffContact($newContact,$oldContact);
	if($diffs["Name"] || $diffs["OrgName"] || $diffs["RegistrantNumber"]) return "Owner_Change";
	elseif (count($diffs) > 0) return "Registrant_Details_Update";
	else return false; 
}
function compareContact($newContact,$oldContact) {
	$diffs = diffContact($newContact,$oldContact);
	if (count($diffs) > 0) return "Contact_Update";
	else return false;
}
function htmldump($variable, $height="9em") {
	echo "<pre style=\"border: 1px solid #000; height: {$height}; overflow: auto; margin: 0.5em;\">";
	var_dump($variable);
	echo "</pre>\n";
}
function replaceSpecialCharacters($string) {
	$string = str_replace("ü", "u", $string);
	$string = str_replace("ä", "a", $string);
	$string = str_replace("ö", "o", $string);
	$string = str_replace("ß", "s", $string);
	$string = str_replace("Ü", "U", $string);
	$string = str_replace("Ä", "A", $string);
	$string = str_replace("Ö", "O", $string);
	return $string; 
};

?>
