<?php

function login($params) {
	$session = array(
	             'Account'=> $params["Username"],
	             'Password' =>  $params["Password"]
	);
	return sendRequest('LogIn',array('session' => $session ),$params);
	 
};
function request($functionName, $ascioParams, $params, $outputResult=false)  {
	$result = login($params); 
	if(is_array($result)) return $result;
	$ascioParams["sessionId"] = $result->sessionId; 
	$result = sendRequest($functionName,$ascioParams,$params);
	if(is_array($result)) return $result;
	if($outputResult) return $result;
	return;
	//elseif($result->CreateOrderResult->Message) return array("error" => $result->CreateOrderResult->Message);
};
function sendRequest($functionName,$ascioParams, $params) {
		$ascioParams = cleanAscioParams($ascioParams);
		if ($params["TestMode"] == "on") {
			$wsdl = "https://awstest.ascio.com/2012/01/01/AscioService.wsdl";	
		} else {
			$wsdl = "https://aws.ascio.com/2012/01/01/AscioService.wsdl";	
		} 
        $client = new SoapClient($wsdl,array( "trace" => 1 ));
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
function getDomain($params) {
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
	$result = request("SearchDomain",$ascioParams,$params,true);
	if(is_array($result)) return $result;
	else return $result->domains->Domain;
}
function mapToOrder ($params,$orderType) {
	$registrant = mapToContact($params,"Registrant");
	$admin = mapToContact($params,"Admin");
	$tech = mapToContact($params,"Admin");
	$order = 
		array( 
		'Type' => $orderType, 
		'Domain' => array( 
			'DomainName' => $params["sld"] ."." . $params["tld"],
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
function callApi( $params,$apiParams) {	 
	 $params["username"] = $apiParams["Username"];
 	 $params["password"] = md5($apiParams["Password"]);
	 $ch = curl_init();
	 curl_setopt($ch, CURLOPT_URL, $apiParams["Url"]);
	 curl_setopt($ch, CURLOPT_POST, 1);
	 curl_setopt($ch, CURLOPT_TIMEOUT, 100);
	 curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	 curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	 $data = curl_exec($ch);
	 curl_close($ch);
	 
	 $data = explode(";",$data);
	 foreach ($data AS $temp) {
	   $temp = explode("=",$temp);
	   $results[$temp[0]] = $temp[1];
	 }

	 if ($results["result"]=="success") {
	   # Result was OK!
	 } else {
	   # An error occured
	 	
	   echo "The following error occured: ".$results["message"];
	 }
	 $id =  split ("=",$data[1]);
	 return $id[1];
 
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