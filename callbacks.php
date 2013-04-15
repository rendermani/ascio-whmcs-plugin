<?
require("ascio-lib.php");

// Data from Callback
function getCallbackData($params,$result,$orderStatus,$messageId,$apiParams) {
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

	$id = callApi($postfields,$apiParams);
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
	callApi($postfields,$apiParams);
	// Ascio ACK Message
	$ascioParams = array(
		'sessionId' => 'mySessionId',
		'msgId' => $messageId
	);
	$result = request("AckMessage", $ascioParams,$params,true); 
}

$orderId = $_GET["OrderId"];
$messageId = $_GET["MessageId"];
$orderStatus = $_GET["OrderStatus"];

//
// customize this
//

$params["Username"]="ASCIO_User";
$params["Password"]="ASCIO_Password";
$params["TestMode"]="on";

$apiParams["Username"]= "WHMCS_User";
$apiParams["Password"]= "WHMCS_Password";

$apiParams["Url"]= "WHMCS_Password";

// end 

$ascioParams = array(
	'sessionId' => 'mySessionId',
	'msgId' => $messageId
);
$result = request("GetMessageQueue", $ascioParams,$params,true);  
getCallbackData($params,$result,$orderStatus,$messageId,$apiParams);

?>