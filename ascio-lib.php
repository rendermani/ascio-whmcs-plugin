<?php
require_once("config.php");

Class SessionCache {
	var $m;
	function init() {
		$m = new Memcached();
		$m->addServer('localhost', 11211);
		return $m;
	}
	public static function get() {
		$m =  SessionCache::init();
		return $m->get('ascioSession');
	}
	public static function put($sessionId) {
		$m =  SessionCache::init();
		$m->delete('ascioSession');
		$m->add('ascioSession',$sessionId);
	}
	public static function clear() {
		$m =  SessionCache::init();
		$m->delete('ascioSession');
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
		$status =  mapResult($orderStatus);
	}
	// External WHMCS API: Set Status
	$postfields = array();
	$postfields["action"] = "updateclientdomain";
	$postfields["domain"] = $domainName;
	$postfields["status"] = $status;

	$id = callApi($postfields);
	// External WHMCS API: Send Mail
	$msgPart = "Domain order ". $id . ", ".$domainName;
	if ($orderStatus=="Completed") {
		$message =formatOK($msgPart);
	} else {
		$message = formatError($result->item->StatusList->CallbackStatus,$msgPart);
	}
	$postfields = array();
	$postfields["action"] = "sendemail";
	$postfields["customtype"] = "domain";
	$postfields["customsubject"] = $msgPart ." ". strtolower($status);
	$postfields["custommessage"] = $message;
	$postfields["id"] = $id;
	callApi($postfields);
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
	$postfields["action"] = "sendemail";
	$postfields["customtype"] = "domain";
	$postfields["customsubject"] = $domain->domain->DomainName . ": New AuthCode generated";
	$postfields["custommessage"] = $msg;
	$postfields["id"] = $order->OrderId;
	callApi($postfields);

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
	$admin = mapToContact($params,"Admin");
	$tech = mapToContact($params,"Admin");
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
	return array(
			'sessionId' => "set-it-later",
			'order' => $order
        );
}
function mapToContact($params,$type) {
	$contactName = array();
	$prefix = "";
	if($type == "Registrant") {
		$contactName["Name"] = $params["firstname"] . " " . $params["lastname"];
		$contactName["NexusCategory"] = $params["Nexus Category"];
		$contactName["RegistrantNumber"] = "55203780600585";
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
		'Fax' 			=> '');
			
	return array_merge($contactName,$contact);
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
function callApi( $params) {
	 $config = getAscioConfig();
	 $params = array_merge($params,getWHMCSCredentials());
	 $ch = curl_init();
	 curl_setopt($ch, CURLOPT_URL, $config["whmcs"]["api"]);
	 curl_setopt($ch, CURLOPT_POST, 1);
	 curl_setopt($ch, CURLOPT_TIMEOUT, 100);
	 curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	 curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	 $data = curl_exec($ch);
	 curl_close($ch);
	 $data = explode(";",$data);
	 foreach ($data AS $temp) {
	   $temp = explode("=",$temp);
	   if(count($temp)>1) $results[$temp[0]] = $temp[1];
	 }
	 if ($results["result"]=="success") {
	   # Result was OK!
	 } else {
	   # An error occured	 	
	   echo "The following error occured: ".$results["message"];
	 }
	 if($data[1]) {
	 	$id =  split ("=",$data[1]);
	 	return $id[1];
	 }
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
		"Documentation_Not_Approved" => "Cancelled",
		"Pending_Documentation" => "Pending",
		"Pending_End_User_Action" => "Pending",
		"Pending_Post_Processing" => "Pending",
		"Pending_NIC_Processing" => "Pending"
	);
	return $resultMap[$status];
}

function htmldump($variable, $height="9em") {
	echo "<pre style=\"border: 1px solid #000; height: {$height}; overflow: auto; margin: 0.5em;\">";
	var_dump($variable);
	echo "</pre>\n";
}
?>