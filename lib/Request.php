<?php
use Illuminate\Database\Capsule\Manager as Capsule;
require_once("Tools.php");
define("ASCIO_WSDL_LIVE","https://aws.ascio.com/2012/01/01/AscioService.wsdl");
define("ASCIO_WSDL_TEST","https://aws.ascio.info/debug/v2-wsdl.xq?token=d258d53c-ccae-4f71-9000-4fab5339fe70");

Class SessionCache {
	public static function get($account) {
		try {
			$result = Capsule::select("select sessionId from mod_asciosession where account='$account'");
		} catch (Exception $e) {
			logActivity("Error session-cache query: ". $e);	
		}
		return  $result[0]->sessionId;
	}
	public static function put($sessionId,$account) {
		$query = "	INSERT INTO  mod_asciosession (account, sessionId) 
					VALUES('$account', '$sessionId') 
					ON DUPLICATE KEY UPDATE account='$account', sessionId='$sessionId'";
		mysql_query($query); 
		echo mysql_error();
		if(mysql_error()) {
			Tools::log("Error writing session: ".mysql_error());
		}		
	}
	public static function clear($account) {
		SessionCache::put("false",$account);
	}
}
Class DomainCache {
	public static function get($domainName) {
		GLOBAL $ascioDomainCache;
		if(!$ascioDomainCache) $ascioDomainCache = array();
		return $ascioDomainCache[$domainName];
	}
	public static function put($domain) {
		GLOBAL $ascioDomainCache;
		if(!$ascioDomainCache) $ascioDomainCache = array();
		$ascioDomainCache[$domain->DomainName];
		$ascioDomainCache[$domain->DomainName] = $domain;
	}
}
function createRequest($params) {
	$tld = $params["tld"];
	$filename = realpath(dirname(__FILE__))."/../tlds/$tld/$tld.php";
	$defExists = file_exists($filename);	
	if($tld && $defExists) {
		require_once($filename);
		$className = str_replace(".", "_", $tld);
		$tldRequest = new $className($params);
		return $tldRequest;
	} else {
		return new Request($params);
	}
}
Class Request {
	var $account;
	var $password; 
	var $params;
	var $domain;

	public function __construct($params) {
		$this->setParams($params);
	}
	private function login() {
		$session = array(
		             'Account'=> $this->account,
		             'Password' =>  $this->password
		);
		$result =  $this->sendRequest('LogIn',array('session' => $session ));
		logActivity("login-result: ".json_encode($result));
		SessionCache::put($result->sessionId,$this->account);
		return $result;
	}
	public function request($functionName, $ascioParams)  {	
		$sessionId = SessionCache::get($this->account);	
		if (!$sessionId || $sessionId == "false") {		
			$loginResult = $this->login(); 
			if(is_array($loginResult) && $loginResult["error"]) return $loginResult;
			$ascioParams["sessionId"] = $loginResult->sessionId; 				
			SessionCache::put($loginResult->sessionId, $this->account);
			return $loginResult;
		} else {		
			$ascioParams["sessionId"] = $sessionId; 
		}
		return $this->sendRequest($functionName,$ascioParams);
	}
	private function sendRequest($functionName,$ascioParams) {			
		if($ascioParams->order) {
			$orderType = " ".$ascioParams->order->Type ." "; 
		} else $orderType ="";
		logActivity("WHMCS Request:".$functionName .$orderType."(". $this->account .")" );
		$wsdl = $this->params["TestMode"]=="on" ? ASCIO_WSDL_TEST : ASCIO_WSDL_LIVE;
        $client = new SoapClient($wsdl,array( "trace" => 1));
        $result = $client->__call($functionName, array('parameters' => $ascioParams));    
		$resultName = $functionName . "Result";	
		$status = $result->$resultName;
		$result->status = $status;
		$ot = $orderType ? " [".$orderType."] " : ""; 
		logActivity("WHMCS ".$functionName  .$ot.$status->Values->string . " ResultCode:" . $status->ResultCode . " ResultMessage: ".$status->Message);
		if ( $status->ResultCode==200) {
			return $result;
		} else if( $status->ResultCode==554)  {
			$messages = "Temporary error. Please try later or contact your support.";
		} elseif ($status->ResultCode==401 && $functionName != "LogIn") {
			SessionCache::clear($this->account);
			logActivity("WHMCS Invalid Session, redoing Login");
			$this->login();
			return $this->request($functionName, $ascioParams);
		} elseif ($status->ResultCode==401) {
			die("401");
			return array('error' => $status->Message );     
		} else if (count($status->Values->string) > 1 ){
			$messages = join(", \r\n<br>",$status->Values->string);	
		}  else {
			$messages = $status->Values->string;
		}		
		$message = Tools::cleanString($messages);
		return array('error' => $message );     
	}
	public function getDomain($handle) {
		$ascioParams = array(
			'sessionId' => 'mySessionId',
			'domainHandle' => $handle
		);
		$result =  $this->request("GetDomain", $ascioParams); 
		if($result->error) return $result;
		else {	

			$status = !$result->domain->DomainName ? NULL : $result->domain->Status;
			
			$this->setDomainStatus($result->domain);
			DomainCache::put($result->domain);
			$this->setHandle($result->domain);
			return $result->domain;
		}
		return $result;
	}
	public function searchDomain($params) {
		$domain = DomainCache::get($this->domainName);
		if(isset($domain)) return $domain; 
		$handle = $this->getHandle("domain",Tools::getDomainId($this->domainName));
		if($handle) {	
			return  $this->getDomain($handle);		
		}	
		$criteria= array(
			'Mode' => 'Strict',
			'Withoutstates' => Array('string' => 'deleted'),
			'Clauses' => Array(
				'Clause' => Array(
					'Attribute' => 'DomainName', 
					'Value' => $this->domainName , 
					'Operator' => 'Is'
				)
			)
		);
		$ascioParams = array(
			'sessionId' => 'mySessionId',
			'criteria' => $criteria
		);
		$result =  $this->request("SearchDomain",$ascioParams);
		if($result->error) return $result;
		else {						
			$status = !$result->domains->Domain->DomainName ? NULL : $result->domains->Domain->Status;
			$this->setDomainStatus($result->domains->Domain);
			DomainCache::put($result->domains->Domain);
			$this->setHandle($result->domains->Domain);
			return $result->domains->Domain;
		}
	}
	public function ackMessage($messageId,$order,$domain) {
		$ascioParams = array(
			'sessionId' => 'mySessionId', 
			'msgId' => $messageId
		);	
		$result = $this->request("AckMessage", $ascioParams);		
		if(($order->order->Type=="Register_Domain" || $order->order->Type=="Transfer_Domain") && $order->order->Status=="Completed") {
			if($this->params["NameserverRegex"] =="") {
				$this->params["NameserverRegex"] = "/.*/";
			}			
			if(preg_match($this->params["NameserverRegex"],$domain->NameServers->NameServer1->HostName)){
				$this->autoCreateZone($domain->DomainName);
			};			
		}
	}
	public function getCallbackData($orderStatus,$messageId,$orderId,$type) {
		$ascioParams = array(
			'sessionId' => 'mySessionId',
			'msgId' => $messageId
		);		
		$result = $this->request("GetMessageQueue", $ascioParams);
		$order =  $this->getOrder($orderId);
		$domainName = $order->order->Domain->DomainName;
		$domainId   = Tools::getDomainId($domainName);
		if(!isset($domainId)) {
			 $ascioParams = array(
				'sessionId' => 'mySessionId', 
				'msgId' => $messageId
			);			
			if($orderStatus == "Completed" && ($orderType=="Register_Domain" || $orderType =="Transfer_Domain")) {
				$this->expireDommain($this->params);
			}
			$this->request("AckMessage", $ascioParams);
			return;	
		}
		$domain = $this->getDomain($order->order->Domain->DomainHandle);
		$orderType = $order->order->Type;
		$this->params["domainname"] = $domainName;
		// External WHMCS API: Set Status
		// External WHMCS API: Send Mail
		$msgPart = "Domain (". $domainId . "): ".$domainName;

		//$whmcsStatus = $this->setDomainStatus($domain);
		logActivity("WHMCS getCallbackData -> setOrderStatus");
		$whmcsStatus = $this->setOrderStatus($order);
		if ($orderStatus=="Completed") {
			if(
				$this->params["AutoExpire"] =="on" && 
				($order->order->Type =="Register_Domain" || $order->order->Type =="Transfer_Domain")) {
				sleep(5);
				$this->expireDomain($this->params);	
			}	
		} else {
			$msgPart = "Domain (". $domainId . "): ".$domainName;
			$errors =  Tools::formatError($result->item->StatusList->CallbackStatus,$msgPart);
		}	
		Tools::log($type." received from Ascio. Order: " .$order->order->Type. ", Domain: ".$domainName.", Order-Status: ".$orderStatus."\n ".$errors);
		Tools::addNote($domainName, $order->order->Type. ": ".$orderStatus . $errors);
		$this->ackMessage($messageId,$order,$domain);
		$this->sendStatus($order,$domainId,$orderStatus,$errors); 
		//$this->sendAuthCode($order->order,$domainId);
		$this->expireAfterRenew($order,$domain);				
	}
	protected function expireAfterRenew($order,$domain) {
		if($this->params["AutoExpire"] != "on") return;
		if(
			$order->order->Type=="Autorenew_Domain" && 
			$order->order->Status=="Completed" &&
			!$this->hasStatus($domain,"expiring")
			) {
			$this->expireDomain($this->params);	
		}
	}
	public function sendStatus($order,$domainId,$orderStatus,$errors) {
		if($this->params["DetailedOrderStatus"] != "on") return;
		if(!(
			$order->order->Type == "Register_Domain" ||
			$order->order->Type == "Transfer_Domain" ||
			$order->order->Type == "Nameserver_Update" ||
			$order->order->Type == "Delete_Domain" ||
			$order->order->Type == "Restore_Domain" ||
			$order->order->Type == "Queue_Domain" ||
			$order->order->Type == "Renew_Domain" ||
			$order->order->Type == "Unexpire_Domain" ||
			$order->order->Type == "Contact_Update" ||
			$order->order->Type == "Domain_Details_Update" ||
			$order->order->Type == "Update_AuthInfo" ||
			$order->order->Type == "Registrant_Details_Update" ||
			$order->order->Type == "Change_Locks" ||
			$order->order->Type == "Owner_Change" 
		)) return;
		if(
			$orderStatus == "Completed" || 
			$orderStatus == "Failed" || 
			$orderStatus == "Pending_End_User_Action" || 
			$orderStatus == "Pending_Documentation"
			) {
			$values = array();		
 			$values["messagename"] = "Ascio Status";
 			$values["customvars"] = array(
 				"orderType"=> str_replace("_"," ",$order->order->Type),
 				"status" => $orderStatus,
 				"errors" => $errors);
			$values["id"] = $domainId;
			$results = localAPI("sendemail",$values,Tools::getApiUser());
			return $results;
		}
	}
	public function sendAuthCode($order,$domainId) {
		if($order->Type != "Update_AuthInfo") return;
		$domain =  $this->getDomain($order->Domain->DomainHandle);
		$values = array();		
 		$values["messagename"] = "EPP Code";
 		$values["customvars"] = array("code"=> $domain->domain->AuthInfo);
		$values["id"] = $domainId;
		$results = localAPI("sendemail",$values,Tools::getApiUser());
		return $results;
	}
	public function autoCreateZone($domain) {
		$params = $this->setParams();		
		logActivity("WHMCS Creating DNS zone ".$domain);	
		if($this->params["AutoCreateDNS"]=="on") {
			$dns = $this->params["DNS_Default_Zone"];
			$mx1 = $this->params["DNS_Default_Mailserver"];
			$mx2 = $this->params["DNS_Default_Mailserver_2"];
			$zone = new DnsZone($params,$domain);
			$params["dnsrecords"] = array(
				array("hostname" => "@","type" => "A","address" => $dns),
				array("hostname" => "www","type" => "A","address" => $dns),
				array("hostname" => "mail","type" => "A","address" => $mx1),
				array("hostname" => "mail2","type" => "A","address" => $mx2),
				array("hostname" => "@", "type" => "MX","address" => "mail1", "priority" => 10),
				array("hostname" => "@", "type" => "MX","address" => "mail2","priority" => 20)
			);
			$result = $zone->update($params);
			Tools::log ("Created DNS zone: ".$domain."\n");
		}
	}
	public function setOrderStatus($result) {
		if($result->error) return;
		$type = $result->order->Type;
		$order = $result->order;
		$pending =  strpos($order->Status, "Pending") > -1 || strpos($order->Status, "NotSet") > -1;
		logActivity("WHMCS SetOrderStatus, order->Status: ".$order->Status);
		if($type == "Transfer_Domain" && $pending) {
			return $this->setStatus($order->Domain,"Pending Transfer");
		}

		if($type == "Register_Domain" || $type =="Transfer_Domain") {			
			if($pending) {		
				$this->setStatus($order->Domain,"Pending");
			} else $this->setDomainStatus($order->Domain);
		}
	}
	public function getDomainStatus($domain) {	
		if(!$domain) return "Cancelled";
		//logActivity("WHMCS getDomainStatus domain ".json_encode($domain));	
		if($this->hasStatus($domain,"deleted")) {
			logActivity("WHMCS Domain has Status deleted: ".$domain->Status);
			return "Cancelled";
		}
		if(
			$this->hasStatus($domain,"active") || 
			$this->hasStatus($domain,"expiring") || 
			$this->hasStatus($domain,"lock")) {			
			return "Active"; 
		}
		logActivity("WHMCS Invalid Status: ".$domain->Status);		
		return false;
		
	}
	public function setDomainStatus($domain) {		
		logActivity("WHMCS SetDomainStatus JSON: ".json_encode($domain));
		logActivity("WHMCS SetDomainStatus Status: ".$domain->Status);
		if($domain) {
			$this->setStatus($domain,$this->getDomainStatus($domain));				
		} else {
			$this->setStatus($domain,"Cancelled");	
		}		
	}
	public function setStatus($domain,$status) {

		if(!status) return false; 
		$values["domain"] =  $domain->DomainName ? $domain->DomainName : $this->params["domainname"]; 
		if(isset($domain->ExpDate) && $domain->ExpDate != "0001-01-01T00:00:00") {
			$expDate = $this->formatDate($domain->ExpDate);
			$creDate = $this->formatDate($domain->CreDate);
			$result = Capsule::select("select Threshold, Renew from tblasciotlds where Tld = '".$this->getTld($domain->DomainName)."'")[0];	
			$hasRenew = $result->Renew == 1 ? true : false; 	
			$threshold = $result->Threshold; 	
			$dueDate = DateTime::createFromFormat(DateTime::ATOM,$domain->ExpDate."-01:00");
			$dueDate->modify($threshold.' day');		
			// this is only if the renew command doesn't exist, and the domain is not expiring. 
			// in this case 1x paid means unexpire, then expire with the next autorenew. 			
			if(!$this->hasStatus($domain,"expiring") && $hasRenew==false) {
				$dueDate->modify('+1 year');	
			}
			if(!isset($this->params["Sync_Due_Date"]) || $this->params["Sync_Due_Date"]=="on") {
				logActivity("WHMCS sync due date");
				$values["nextduedate"] = $dueDate->format('Y-m-d');	
			} 
			if($expDate) {
				$values["expirydate"] = $expDate;	
				$values["registrationdate"] = $creDate;	 
			}
		}
		$values ["status"] = $status;
		logActivity("WHMCS setStatus: ".json_encode($values));
		$results = localAPI("updateclientdomain",$values,Tools::getApiUser()); 	
		logActivity("WHMCS setStatus result: ".json_encode($results));

		logActivity("Set new WHMCS status for ".$domain->DomainName. ": ".$status.", ".$expDate);
	}	
	protected function hasStatus($domain,$search) {
		return strpos($domain->Status, strtoupper($search)) > -1;
	}
	private function getTld($domainName) {
		$tokens = explode(".", $domainName);	
		array_pop($tokens);
		$result = str_replace(join(".",$tokens).".","", $domainName);
		return $result;
	}
	private function formatDate($xsDateTime) {
		if($domain->ExpDate == "0001-01-01T00:00:00") return false;
		$dateTokens = explode("T", $xsDateTime);
		if(count($dateTokens == 2)) {
			return str_replace("-", "", $dateTokens[0]);
		}
		return false; 
	}
	public function getOrder($orderId) {
		$ascioParams = array(
			'sessionId' => 'mySessionId',
			'orderId' => $orderId
		);
		$result =  $this->request("GetOrder", $ascioParams,true); 
		return $result;
	}

	public function poll() {
		$ascioParams = array(
			'sessionId' => 'mySessionId',
			'msgType' 	=> 'Message_to_Partner'
		);
		$result =  $this->request("PollMessage",$ascioParams,true);
		if(is_array($result)) return $result;
		else return $result;
	}
	public function ack($msgId) {
		$ascioParams = array(
			'sessionId' => 'mySessionId',
			'msgId' 	=> $msgId
		);
		$result =  $this->request("AckMessage",$ascioParams,true);
		if(is_array($result)) return $result;
		else return $result;  
	}
	public function registerDomain($params=false) {
		// register domains
		$params = $this->setParams($params);
		try {			
			$ascioParams = $this->mapToOrder($params,"Register_Domain");
		} catch (AscioException $e) {
			return array("error" => $e->getMessage());
		}		
		$result = $this->request("CreateOrder",$ascioParams);				
		return $result;
	}
	public function transferDomain ($params=false) {		
		$params = $this->setParams($params);
		try {
			$ascioParams = $this->mapToOrder($params,"Transfer_Domain");
			$datalessTlds = array("com","net","org","biz","info","us","cc","cn","com.cn","net.cn","org.cn","tv","it");
			if(in_array($params["tld"], $datalessTlds) && $this->params["DatalessTransfer"]=="on") {
				logActivity("WHMCS: Do dataless transfer");
				unset($ascioParams["order"]["Domain"]["Registrant"]);
				unset($ascioParams["order"]["Domain"]["AdminContact"]);
				unset($ascioParams["order"]["Domain"]["TechContact"]);
				unset($ascioParams["order"]["Domain"]["BillingContact"]);
				unset($ascioParams["order"]["Domain"]["NameServers"]);
				unset($ascioParams["order"]["Domain"]["DnsSecKeys"]);
				unset($ascioParams["order"]["Options"]);
			}
		} catch (AscioException $e) {
			return array("error" => $e->getMessage());
		}
		$result = $this->request("CreateOrder",$ascioParams);		
		$this->setOrderStatus($result,"Pending");
		return $result;
	}	
	public function updateDomain ($params=false) {			
		$params = $this->setParams($params);
		try {
			$ascioParams = $this->mapToOrder($params,"Domain_Details_Update");
			logModuleCall(
	            'asciodomains',
	            __FUNCTION__,
	            $params,
	            $ascioParams	            
        	);
		} catch (AscioException $e) {
			return array("error" => $e->getMessage());
		}
		$result = $this->request("CreateOrder",$ascioParams);		
		$this->setOrderStatus($result,"Pending");
		return $result;
	}
	public function updateContacts ($params=false) {
		$params = $this->setParams($params);
		$old = $this->searchDomain($params);
		$newRegistrant 	= $this->mapToContact2($params["contactdetails"]["Registrant"],"Registrant");
		$newAdmin 		= $this->mapToContact2($params["contactdetails"]["Admin"],"Contact");
		$newTech 		= $this->mapToContact2($params["contactdetails"]["Tech"],"Contact");
		$updateRegistrant = Tools::compareRegistrant($newRegistrant,$old->Registrant);
		$updateAdmin = Tools::compareContact($newAdmin,$old->AdminContact);
		$updateTech = Tools::compareContact($newTech,$old->TechContact);	

		if($updateRegistrant) {
			logActivity("WHMCS Update Registrant: ".$registrantResult);
			try {
				$ascioParams = $this->mapToOrder($params,$updateRegistrant);		
			} catch (AscioException $e) {
				return array("error" => $e->getMessage());
			}
			$ascioParams["order"]["Domain"]["Registrant"] = $newRegistrant;
			// Do the Adminchange within the owner-change
			if($updateAdmin && $updateRegistrant=="Owner_Change") {
				logActivity("WHMCS Owner_Change + Admin_Change");
				$ascioParams["order"]["Domain"]["AdminContact"] = $newAdmin;
			}
			$registrantResult = $this->request("CreateOrder",$ascioParams);		
		} 
		if($updateTech || $updateBilling || ($updateAdmin && $updateRegistrant!="Owner_Change")) {
			logActivity("WHMCS Contact_Update");
			try {
				$ascioParams = $this->mapToOrder($params,"Contact_Update");		
			} catch (AscioException $e) {
				return array("error" => $e->getMessage());
			}
			if($updateAdmin) {
				logActivity("WHMCS Update Tech");
				$ascioParams["order"]["Domain"]["AdminContact"] = $newAdmin;
			} else {
				$ascioParams["order"]["Domain"]["AdminContact"] = $old->AdminContact;
			}
			if($updateTech) {
				logActivity("WHMCS Update Tech");
				$ascioParams["order"]["Domain"]["TechContact"] = $newTech;
			} else {
				$ascioParams["order"]["Domain"]["TechContact"] = $old->TechContact;
			}
			$ascioParams["order"]["Domain"]["BillingContact"] = $old->BillingContact;
			$contactResult = $this->request("CreateOrder",$ascioParams);
		}
		return array_merge($registrantResult,$contactResult);
	}
	public function renewDomain($params) {
		$result = Capsule::select("select Threshold, Renew from tblasciotlds where Tld = '".$params["tld"]."'")[0];				
	    $hasRenew = $result->Renew == 1 ? true : false; 	
		$params = $this->setParams($params);
		if($result->Renew == 1) {
			try {
				$ascioParams = $this->mapToOrder($params,"Renew_Domain");
			} catch (AscioException $e) {
				return array("error" => $e->getMessage());
			}
		} else {
			$domain = $this->searchDomain($params);
			if($this->hasStatus($domain,"expiring")) {
					return $this->unexpireDomain($params);
			} else return array("error" => "Domain can't be renewed again.");	
		}	
		$result =  $this->request("CreateOrder",$ascioParams);
		return $result;
	}
	public function unexpireDomain($params) {
		$params = $this->setParams($params);
		try {
			$ascioParams = $this->mapToOrder($params,"Unexpire_Domain");
		} catch (AscioException $e) {
			return array("error" => $e->getMessage());
		}
		$result =  $this->request("CreateOrder",$ascioParams);
		return $result;
	}
	public function expireDomain($params) {
		$params = $this->setParams($params);
		try {
			$ascioParams = $this->mapToOrder($params,"Expire_Domain");
		} catch (AscioException $e) {
			return array("error" => $e->getMessage());
		}
		$result =  $this->request("CreateOrder",$ascioParams);
		return $result;
	}	
	function saveNameservers($params) {
		$params = $this->setParams($params);
		try {
			$ascioParams = $this->mapToOrder($params,"Nameserver_Update");
		} catch (AscioException $e) {
			return array("error" => $e->getMessage());
		}
		$result =  $this->request("CreateOrder",$ascioParams);
		return $result;
	}	
	public function saveRegistrarLock($params,$noRenewTld) {
		logActivity( "WHMCS saveRegistrarLock");

		$params = $this->setParams($params);
		$lockstatus = $params["lockenabled"]=="unlocked" ? "UnLock" : "Lock";
		$lockParams = $this->mapToOrder($params,"Change_Locks");
		$lockParams["order"]["Domain"]["TransferLock"] = $lockstatus;
		return $this->request("CreateOrder",$lockParams);
	}	
	public function getEPPCode($params) {
		$domain = $this->searchDomain($params); 
		return array("eppcode" => $domain->AuthInfo);
	}		
	public function updateEPPCode($params) {
		$params = $this->setParams($params);
	    try {
	    	$ascioParams = $this->mapToOrder($params,"Update_AuthInfo");
		} catch (AscioException $e) {
			return array("error" => $e->getMessage());
		}
		$result = $this->request("CreateOrder",$ascioParams,true);
		if($result->error) {
			return $result;
		} else {
			return array("eppcode" => $ascioParams->Order->Domain->AuthInfo);
		}
	}	
	protected function mapToRegistrant($params) {
		$result =  $this->mapToContact($params,"Registrant");
		$result["Name"] = $params["firstname"] . " " . $params["lastname"];
		$result["Name"] = $result["Name"] ==" " ? null : $result["Name"];
		$result["RegistrantType"] = $params["custom"]["RegistrantType"];
		$result["VatNumber"] = $params["custom"]["VatNumber"];
		$result["NexusCategory"] = $params["custom"]["NexusCategory"];
		$result["RegistrantNumber"] = $params["custom"]["RegistrantNumber"];
		$result["Details"] = $params["custom"]["Details"];
		return $result;
	}
	protected function addContactFields($params,$type) {
		$result =  $this->mapToContact($params,$type);
		$result["Type"] = $params["custom"]["Type"];
		$result["Details"] = $params["custom"]["Details"];
		$result["NexusCategory"] = $params["custom"]["NexusCategory"];
		$result["OrganisationNumber"] = $params["custom"]["OrganisationNumber"];
		return $result;
	}
	protected function mapToAdmin($params) {
		return $this->addContactFields($params,"Admin");
	}	
	protected function mapToTech($params) {
		return $this->addContactFields($params,"Admin");
	}	
	protected function mapToBilling($params) {
		return $this->addContactFields($params,"Admin");
	}
	protected function mapToTrademark($params) {
		return null; 
	}
	public function mapToOrder ($params,$orderType) {
		//	get custom-field names. Params only has IDs but the names are needed
		$fields = $customfields = $params["custom"] = array();
 		$result = mysql_query("select id,fieldname from tblcustomfields");
 		foreach ($params["customfields"] as $key => $value) {
 			$customFields[$value["id"]] = $value["value"];
 		}
 		while ($row = mysql_fetch_assoc($result)) {
 			$params["custom"][$row['fieldname']]=$customFields[$row['id']] ;
		}
		$params = $this->setParams($params);
		$domainName = $params["domainname"];

		$proxy = $params["Proxy_Lite"] == "on" ? "Privacy" : "Proxy";
		$domain = array( 
			'DomainName' => $domainName,
			'RegPeriod' =>  $params["original"]["regperiod"],
			'AuthInfo'	=> 	$params["eppcode"],
			'DomainPurpose' =>  $params["Application Purpose"],
			'Comment'		=>  $params["Comment"],
			'Registrant' 	=>  $this->mapToRegistrant($params),
			'AdminContact' 	=>  $this->mapToAdmin($params), 
			'TechContact' 	=>  $this->mapToTech($params), 
			'BillingContact'=>  $this->mapToBilling($params),
			'NameServers' 	=>  $this->mapToNameservers($params),
			'Trademark' 	=>  $this->mapToTrademark($params)
		);
		if(!($orderType == "Transfer_Domain" &! $domain["AdminContact"]["FirstName"])) {
			$domain["PrivacyProxy"] = array("Type" => $params["idprotection"] ? $proxy : "None");
		}
		$order = 
			array( 
			'Type' => $orderType, 
			'TransactionComment' => "WHMCS", 
			'Domain' => $domain,
			'Comments'	=>	$params["userid"],
			'Options' => $params["options"]
			); 
		return array(
				'sessionId' => "set-it-later",
				'order' => $order
	        );
	}
	// map contact from Ascio to WHMCS - admincompanyname
	public function mapToContact($params,$type) {
		$contactName = array();
		$errors = array();
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
		$country =  $params[$prefix . "country"];	
		try {
			$contact = Array(
				'OrgName' 		=>  $params[$prefix . "companyname"],
				'Address1' 		=>  $params[$prefix . "address1"],	
				'Address2' 		=>  $params[$prefix . "address2"],
				'PostalCode' 	=>  $params[$prefix . "postcode"],
				'City' 			=>  $params[$prefix . "city"],
				'State' 		=>  $params[$prefix . "state"],		
				'CountryCode' 	=>  $country,
				'Email' 		=>  $params[$prefix . "email"],
				'Phone'			=>  Tools::fixPhone($params[$prefix . "fullphonenumber"],$country),
				'Fax' 			=> 	Tools::fixPhone($params[$prefix . "custom"]["Fax"],$country)
			);
		} catch (AscioException $e) {
			throw new AscioException($type . ", ". $e->getMessage());			
		}	
		return array_merge($contactName,$contact);
	}
	// WHMCS has 2 contact structures. Flat and nested.
	// This function in converting from adminfirstname to Admin["First Name"]
	public function mapToContact2($params,$type) {
		$ascio = (object) array(
			'OrgName'  				=> $params["Organisation Name"],
			'Address1'  			=> $params["Address 1"],
			'Address2'  			=> $params["Address 2"],
			'PostalCode'  			=> $params["ZIP Code"],
			'City'  				=> $params["City"],
			'State'	  				=> $params["State"],
			'CountryCode'  			=> $params["Country"],
			'Email'  				=> $params["Email"],
			'Phone'  				=> Tools::fixPhone($params["Phone"],$params["Country"]), 
			// todo test!
			'Fax'  					=> Tools::fixPhone($params["custom"]["Fax"],$params["Country"]),
		);
		if($type=="Registrant") {
			$ascio->Name = $params["First Name"]. " ". $params["Last Name"];		
		} else {
			$ascio->FirstName 	= $params["First Name"];
			$ascio->LastName 	= $params["Last Name"];
		}
		return $ascio; 
	}
	public function mapToNameservers($params) {
		return array (
					'NameServer1' => Array('HostName' => $params["ns1"]), 
					'NameServer2' => Array('HostName' => $params["ns2"]),
					'NameServer3' => Array('HostName' => $params["ns3"]),
					'NameServer4' => Array('HostName' => $params["ns4"])
		);
	}
	public function setParams($params) {
		if($params) {
			$this->params = $params; 
			$this->domainName = $params["sld"] ."." . $params["tld"];			
			if($this->params["Username"]) $this->account = $this->params["Username"];
			if($this->params["Password"]) $this->password = $this->params["Password"];
		} 
		return $this->params;
	}
	public function getHandle($type,$whmcsId) {
		$result = get_query_val("tblasciohandles","ascio_id", array("type" => $type, "whmcs_id" => $whmcsId));
		$result = $result == ""  ? false : $result;
		return $result;
	}
	public function setHandle($domain) {
		$this->storeHandle("domain",Tools::getDomainId($domain->DomainName),$domain->DomainHandle,$domain->DomainName);
		/*
		$this->storeHandle("registrant",$domain->Registrant->Handle);
		$this->storeHandle("contact",$domain->AdminContact->Handle);
		$this->storeHandle("contact",$domain->TechContact->Handle);
		$this->storeHandle("contact",$domain->BillingContact->Handle);
		*/
	}
	public function storeHandle($type,$whmcsId, $ascioId,$domain) {
		$handle = $this->getHandle($type,$whmcsId);
		if(!$handle) {
			$query = array("type" => $type,"ascio_id" => $ascioId,"whmcs_id" => $whmcsId);
			$result = insert_query("tblasciohandles",$query,array("whmcs_id" => $whmcsId,"type" => $type,"domain" => $domain));
		} else {
			$query = array("ascio_id" => $ascioId);
			$result = update_query("tblasciohandles",$query,array("whmcs_id" => $whmcsId,"type" => $type));
		}		
		return $result; 
	}

}
?>
