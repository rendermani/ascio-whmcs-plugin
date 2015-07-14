<?php
require_once("lib/Request.php");
require_once("lib/Tools.php");

function getCallbackData($orderStatus,$messageId,$orderId) {
	global $request;
	// get message
	$ascioParams = array(
		'sessionId' => 'mySessionId',
		'msgId' => $messageId
	);
	$result = $request->request("GetMessageQueue", $ascioParams,true);  
	$domainName = $result->item->DomainName;
	$order = $request->getOrder($orderId);
	$whmcsStatus = setWhmcsStatus($domainName,$orderStatus,$order->order->Type);
	if ($orderStatus=="Completed") {
		$status = "Active";
	} else {
		$status =  ($orderStatus);
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
		$message = Tools::formatOK($msgPart);
	} else {
		$message = Tools::formatError($result->item->StatusList->CallbackStatus,$msgPart);
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
	syslog(LOG_INFO,"WHMCS callback received: ".$domainName . ": ". $status);
	$request->sendAuthCode($order->order);
	$result = $request->request("AckMessage", $ascioParams,true); 
}
function setWhmcsStatus($domain,$ascioStatus,$orderType) {
	global $username,$password,$request,$whmcsUrl;
	if($ascioStatus==NULL) $ascioStatus = "failed";
		
	$statusMap = array (
		"completed" => "Active",
		"active" => "Active",
		"failed"	=> "Cancelled",
		"invalid"	=> "Cancelled",
		"documentation_not_approved" => "Cancelled",
		"pending_documentation" => "Pending",
		"pending_end_user_action" => "Pending",
		"pending_internal_processing" => "Pending",
		"pending" => "Pending",
		"pending_post_processing" => "Pending",
		"pending_nic_processing" => "Pending"
	);
	$whmcsStatus = $statusMap[strtolower($ascioStatus)];
	if ($orderType=="Transfer_Domain" && $whmcsStatus == "Pending") {
		$whmcsStatus = "Pending Transfer";
	}
	if($ascioStatus=="Completed" && $orderType =="Transfer_Away") {
		$whmcsStatus= "Cancelled";
	}
	$postfields = array();
	$postfields["action"] = "updateclientdomain";

	//$adminuser = "mani";
	$postfields["domain"] =  $domain;
	$postfields["status"] = $whmcsStatus;
	$results = callApi($postfields); 
	return $whmcsStatus;
}
function callApi( $params) {
	global $username,$password,$request,$whmcsUrl;
	 $params["username"] = $username; 
	 $params["password"] = md5($password);
	 $ch = curl_init();
	 curl_setopt($ch, CURLOPT_URL, $whmcsUrl);
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
	   $result =  "The following api-error occured: ".$results["message"];
	   echo $result;
	   syslog(LOG_INFO, $result);
	 }
	 if($data[1]) {
	 	$id =  split ("=",$data[1]);
	 	return $id[1];
	 }
}

$orderId = $_GET["OrderId"];
$messageId = $_GET["MessageId"];
$orderStatus = $_GET["OrderStatus"];
$domain = $_GET["Object"];


$whmcsUrl = "http://whmcs.ascio.info/includes/api.php";
$username = "your-whmcs-user";
$password = "your-whmcs-pw";
$cfg["Username"]="your-ascio-user";
$cfg["Password"]="your-ascio-pw";
$cfg["TestMode"]="on";


if(!($orderId && $messageId && $orderStatus)) throw new Exception("Please provide callback parameters", 1);


syslog("Callback to WHMCS downgraded");
syslog(LOG_INFO, print_r($_GET,1));


$request = new Request($cfg);
getCallbackData($orderStatus,$messageId,$orderId);
echo "Callback received";

?>