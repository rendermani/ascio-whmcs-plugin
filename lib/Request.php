<?php
use Illuminate\Database\Capsule\Manager as Capsule;
use ascio\whmcs\ssl as ssl; 
use WHMCS\Domain\Registrar\Domain;
require_once("Tools.php");
require_once("ParameterCapture.php");
define("ASCIO_WSDL_LIVE","https://aws.ascio.com/2012/01/01/AscioService.wsdl");
define("ASCIO_WSDL_TEST","https://aws.demo.ascio.com/2012/01/01/AscioService.wsdl");
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
		if(mysql_error()) {
			Tools::log("Error writing session: ".mysql_error());
		}		
	}
	public static function clear($account) {
		SessionCache::put("false",$account);
	}
}
Class DomainCache {
	public static function get($domainId) {
		GLOBAL $ascioDomainCache;
		if(!$ascioDomainCache) $ascioDomainCache = array();
		return $ascioDomainCache[$domainId];
	}
	public static function put($domain) {
		GLOBAL $ascioDomainCache;
		if(!$ascioDomainCache) $ascioDomainCache = array();
		$ascioDomainCache[$domain->domainId] = $domain;
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
	public $domainName;
	public function __construct($params) {
		$this->setParams($params);
	}
	private function login() {
		$session = array(
		             'Account'=> $this->account,
		             'Password' =>  $this->password
		);
		$result =  $this->sendRequest('LogIn',array('session' => $session ));
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
		} else {		
			$ascioParams["sessionId"] = $sessionId; 
		}
		return $this->sendRequest($functionName,$ascioParams);
	}
	private function sendRequest($functionName,$ascioParams) {			
		if(isset($ascioParams["order"])) {
			$orderType = " ".$ascioParams["order"]["Type"] .""; 
		} else $orderType ="";
		$wsdl = $this->params["TestMode"]=="on" ? ASCIO_WSDL_TEST : ASCIO_WSDL_LIVE;        
		$client = new SoapClient($wsdl,array( "cache_wsdl " => WSDL_CACHE_MEMORY ));
		$result = $client->__call($functionName, array('parameters' => $ascioParams));    
		$resultName = $functionName . "Result";	
		$status = $result->$resultName;
		$result->status = $status;
		$ot = $orderType ? " [".$orderType."] " : ""; 
		$parameterCapture = new ParameterCapture($this->params,$functionName,$orderType);
		$parameterCapture->capture();
		Tools::logModule($functionName,$ascioParams,$result);
		if ( $status->ResultCode == 200 ||$status->ResultCode == 201 || $status->ResultCode == 413 ) {			
			return $result;
		} else if( $status->ResultCode==554)  {
			$messages = "Temporary error. Please try later or contact your support.";
		} elseif ($status->ResultCode==401 && $functionName != "LogIn" ) {
			SessionCache::clear($this->account);
			$this->login();
			return $this->request($functionName, $ascioParams);
		} elseif ($status->ResultCode==401) {
			logActivity("Ascio registrar plugin settings - Login failed, invalid account or password: ".$this->account);
			die("Ascio registrar plugin settings - Login failed, invalid account or password: ".$this->account);
			return array('error' => $status->Message );     
		} else if (is_array($status->Values->string) && count($status->Values->string) > 1 ){
			$messages = join(", \r\n",$status->Values->string);	
		}  else {
			$messages = $status->Values->string;
		}		
		$message = Tools::cleanString($messages);
		return array('error' => $message );     
	}
	public function availabilityInfo($domain) {
		$ascioParams = array(
			'sessionId' => 'mySessionId',
			"domainName" => $domain,
			"quality" => "Live"
		);		
		$result =  $this->request("AvailabilityInfo", $ascioParams);
		if($result->AvailabilityInfoResult->ResultCode >= 500) {
			return  array('error' => $result->AvailabilityInfoResult->ResultMessage );
		} else {
			return $result;
		}
	}
	public function availabilityCheck($domain,$tlds) {
		$ascioParams = array(
			'sessionId' => 'mySessionId',
			"domains" => $domain,
			"tlds" => $tlds,
			"quality" => "SmartLive"
		);
		$result =  $this->request("AvailabilityCheck", $ascioParams);
		if($result->AvailabilityCheckResult->ResultCode >= 500) {
			return  array('error' => $result->AvailabilityCheckResult->ResultMessage );
		} else {
			return $result;
		}
	}
	public function getDomain($handle) {
		$ascioParams = array(
			'sessionId' => 'mySessionId',
			'domainHandle' => $handle
		);
		$result =  $this->request("GetDomain", $ascioParams); 
		if($result->error) {
			return $result;
		}
		else {	
			return $result->domain;
		}
	}
	public function searchDomain() {
		$domainId = $this->params["domainid"];
		$domain = DomainCache::get($domainId);
		if(isset($domain)) return $domain; 		
		$handle = $this->getHandle("domain",$domainId,$this->domainName);
		if($handle) {	
			$domain =   $this->getDomain($handle);	
			$domain->domainId = $domainId;
			$this->setDomainStatus($domain);			
			DomainCache::put($domain);
			$this->setHandle($domain);
			return $domain;	
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
			$result->domains->Domain->domainId = $domainId;
			$this->setDomainStatus($result->domains->Domain);
			DomainCache::put($result->domains->Domain);
			$this->setHandle($result->domains->Domain);
			return $result->domains->Domain;
		}
	}
	public function ackMessage($messageId,$order=null,$domain=null) {
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
	private function isAscioOrder($domainId,$domainName) {
		$result =  get_query_val("tbldomains","registrar", array("id" => $domainId,"domain" => $domainName));
		return $result == "ascio" || $result == "ascio_usd";
	}
	public function getCallbackData($orderStatus,$messageId,$orderId,$type=null) {
		$ascioParams = array(
			'sessionId' => 'mySessionId',
			'msgId' => $messageId
		);		
		$result = $this->request("GetMessageQueue", $ascioParams);	
		$this->domainName = $result->item->DomainName;
		$order =  $this->getOrder($orderId);
		if( $order->order->TransactionComment == "WHMCS SSL Module") {
			require_once(__DIR__. "/../../../servers/asciossl/lib/SslCallback.php");
			$env = $this->params["TestMode"]=="on" ? "testing" : "live" ;
			$params = new ssl\Params();
			$params->testmode = $this->params["TestMode"]=="on" ? true : false;
			$params->setAccount($this->account,$this->password);				
			$callback = new ssl\SslCallback($params,$orderId);
			$message = $result->item->Msg;
			$callback->process($orderId,$orderStatus,$messageId,$message);			
			return;
		}
		$domainName = $order->order->Domain->DomainName;
		$domainId = Tools::getDomainIdFromOrder($order->order);
		if(!$this->isAscioOrder($domainId,$domainName)) {
			$domainId = NULL;
		}
		$this->params["domainid"] = $domainId;
		// if the domain doesn't exist in WHMCS, the message is acked and nothing is done. 
		if(!isset($domainId)) {					
			if ($this->params["MultiBrand_Mode"] == "on") {
				sleep(5);
			} else {
				$ascioParams = array(
					'sessionId' => 'mySessionId', 
					'msgId' => $messageId
				);	
				$this->request("AckMessage", $ascioParams);
			}
			Tools::log("DomainId: " . $domainId." not found in the WHMCS-Database: " .$order->order->Type. ", Domain: ".$domainName.", Order-Status: ".$orderStatus."\n ".$errors);
			return;	
		}
		$domain = $this->getDomain($order->order->Domain->DomainHandle);
		$this->setDomainStatus($domain);
		$domain->domainId = $domainId;
		DomainCache::put($domain);
		$this->setHandle($domain);
		$this->params["domainName"] = $domainName;
		// External WHMCS API: Set Status
		// External WHMCS API: Send Mail
		$msgPart = "Domain (". $domainId . "): ".$domainName;
		logActivity("WHMCS getCallbackData -> setOrderStatus");
		$this->setOrderStatus($order);
		if ($orderStatus=="Completed") {
			if(
				$this->params["AutoExpire"] =="on" && 
				($order->order->Type =="Register_Domain" || $order->order->Type =="Transfer_Domain")) {
				sleep(5);
				$this->expireDomain($this->params);	
			}	
			$this->deleteOldHandle($domainId);
			$this->setHandle($domain);
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
	// for TLDs that have no Renew, the domain is expired after the auto-renew is completed. 
	// this prevents domains being autorenewed without payment. 
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
			$orderStatus == "Invalid" || 
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
		$params = $this->params;		
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
		if($type == "Transfer_Domain" && $pending) {
			return $this->setStatus($order->Domain,"Pending Transfer");
		}
		if($type == "Register_Domain" || $type =="Transfer_Domain") {			
			if($pending) {		
				$this->setStatus($order->Domain,"Pending");
			} else $this->setDomainStatus($order->Domain);
		}
	}
	public function getDomainRegistrarStatus($domain) {	
		if(!$domain) return Domain::STATUS_DELETED;
		if($this->hasStatus($domain,"deleted")) {
			logActivity("WHMCS Domain has Status deleted: ".$domain->Status);
			return Domain::STATUS_DELETED;
		}
		if(
			$this->hasStatus($domain,"active") || 
			$this->hasStatus($domain,"expiring") || 
			$this->hasStatus($domain,"lock")) {			
			return Domain::STATUS_ACTIVE; 
		}
		if(
			$this->hasStatus($domain,"pending")
		){	
			return Domain::STATUS_INACTIVE; 
		}
		logActivity("WHMCS Invalid Status: ".$domain->Status);		
		return false;		
	}
	public function getDomainStatus($domain) {	
		if(!$domain) return "Cancelled";
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
		if(
			$this->hasStatus($domain,"pending")
		){	
			return "Pending"; 
		}
		logActivity("WHMCS Invalid Status: ".$domain->Status);		
		return false;		
	}
	
	public function setDomainStatus($domain) {		
		if($domain) {
			if($this->getDomainStatus($domain)) {				
				$this->setStatus($domain,$this->getDomainStatus($domain));				
			}
		} else {
			$this->setStatus($domain,"Cancelled");	
		}		
	}
	public function setStatus($domain,$status) {
		if(!$status) return false; 
		$values["domainid"] =  $this->params["domainid"]; 
		if(isset($domain->ExpDate) && $domain->ExpDate != "0001-01-01T00:00:00") {
			$expDate = $this->formatDate($domain->ExpDate);
			$creDate = $this->formatDate($domain->CreDate);
			$result = Capsule::select("select Threshold, Renew from tblasciotlds where Tld = '".$this->getTld($domain->DomainName)."'")[0];	
			$hasRenew = $result->Renew == 1 ? true : false; 	
			$threshold = $result->Threshold; 	
			$dueDate = DateTime::createFromFormat(DateTime::ATOM,$domain->ExpDate."-01:00");
			$dueDate->modify($threshold.' day');		
			// this is only if the renew command doesn't exist, and the domain is not expiring. 
			// in this case payment means unexpire, and then expire again after the next autorenew is completed. 			
			// this +1 is set, as the due-date is aligned to the expire-date, and the expire-date doesn't changes for TLDs that have no renew. 
			// $this->hasStatus($domain,"expiring") : the domain is active, autorenew pending
			// $hasRenew==false : this TLD doesn't support renew. 
			if(!$this->hasStatus($domain,"expiring") && $hasRenew==false) {
				$dueDate->modify('+1 year');
			}
			// if the setting Sync_Due_Date is not set, or it is on, the Due-Date will be aligned to the 
			// ExpireDate + Threshold (which is negative in most cases)
			if(!isset($this->params["Sync_Due_Date"]) || $this->params["Sync_Due_Date"]=="on") {
				logActivity("WHMCS sync due date");
				$values["nextduedate"] = $dueDate->format('Y-m-d');	
			} 
			// set the expire-date and the registration-date from the API 
			if($expDate) {
				$values["expirydate"] = $expDate;	
				$values["registrationdate"] = $creDate;	 
			}
		}
		$values ["status"] = $status;
		logActivity("WHMCS setStatus: ".json_encode($values));
		$results = localAPI("updateclientdomain",$values,Tools::getApiUser()); 	
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
		if($xsDateTime == "0001-01-01T00:00:00") return false;
		$dateTokens = explode("T", $xsDateTime);
		if(count($dateTokens) == 2) {
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
		$premiumDomainsEnabled = (bool) $params['premiumEnabled'];
		$premiumDomainsCost = $params['premiumCost'];		
		$params = $this->setParams($params);
		try {			
			$ascioParams = $this->mapToOrder($params,"Register_Domain");
			if ($premiumDomainsEnabled && $premiumDomainsCost) {
				$ascioParams['order']['AgreedPrice'] = $premiumDomainsCost;
			}
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
			logActivity("WHMCS Update Registrant");
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
		if($updateTech || ($updateAdmin && $updateRegistrant!="Owner_Change")) {
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
	public function doRegistrantVerification($email) {
		$ascioParams = [
			"sessionId" => "set-it-later",
			"value" => $email
		];
		return $this->request("DoRegistrantVerification",$ascioParams);
	}	
	public function getRegistrantVerificationInfo($email) {
		$ascioParams = [
			"sessionId" => "set-it-later",
			"value" => $email
		];
		return $this->request("GetRegistrantVerificationInfo",$ascioParams);
	}	
	public function renewDomain($params) {
		// lookup the renew settings for the tld. 
		$result = Capsule::select("select Threshold, Renew from tblasciotlds where Tld = '".$params["tld"]."'")[0];				
		$params = $this->setParams($params);
		// if the registry supports the renew command, the normal renew api method is called
		if($result->Renew == 1) {
			try {
				$ascioParams = $this->mapToOrder($params,"Renew_Domain");
			} catch (AscioException $e) {
				return array("error" => $e->getMessage());
			}
		} 
		// the domain doesn't support renew. Domain is unexpired, and expired again after
		// the domain was autorenewed: $this->expireAfterRenew()
		else {
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
	public function saveRegistrarLock() {
		$lockstatus = $this->params["lockenabled"]=="unlocked" ? "UnLock" : "Lock";
		$lockParams = $this->mapToOrder($this->params,"Change_Locks");
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
		if ($params["customfields"]) {
			$result = mysql_query("select id,fieldname from tblcustomfields");
			foreach ($params["customfields"] as $key => $value) {
				$customFields[$value["id"]] = $value["value"];
			}
			while ($row = mysql_fetch_assoc($result)) {
				$params["custom"][$row['fieldname']]=$customFields[$row['id']] ;
		   }
		 }		
		$params = $this->setParams($params);
		$domainName = $this->domainName;
		$proxy = $params["Proxy_Lite"] == "on" ? "Privacy" : "Proxy";
		$domain = array( 
			'DomainName' => $domainName,
			'RegPeriod' =>  $params["regperiod"],
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
		if(in_array($orderType, ["Transfer_Domain","Register_Domain", "Domain_Details_Update"])) {
			$domain["PrivacyProxy"] = array("Type" => $params["idprotection"] ? $proxy : "None");
		}
		$order = 
			array( 
			'Type' => $orderType, 
			'TransactionComment' => json_encode(array("application" => "WHMCS","domainId" => $params["domainid"],"userId" => $params["userid"],"objectType" => "Domain")), 
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
				'OrgName' 		=>  trim($params[$prefix . "companyname"]),
				'Address1' 		=>  trim($params[$prefix . "address1"]),
				'Address2' 		=>  trim($params[$prefix . "address2"]),
				'PostalCode' 	=>  trim($params[$prefix . "postcode"]),
				'City' 			=>  trim($params[$prefix . "city"]),
				'State' 		=>  trim($params[$prefix . "state"]),
				'CountryCode' 	=>  $country,
				'Email' 		=>  trim($params[$prefix . "email"]),
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
		//Todo: Remove fixPhone		
		$ascio = (object) array(
			'OrgName'  				=> trim($params["Organisation Name"]),
			'Address1'  			=> trim($params["Address 1"]),
			'Address2'  			=> trim($params["Address 2"]),
			'PostalCode'  			=> trim($params["ZIP Code"]),
			'City'  				=> trim($params["City"]),
			'State'	  				=> trim($params["State"]),
			'CountryCode'  			=> trim($params["Country"]),
			'Email'  				=> trim($params["Email"]),
			'Phone'  				=> Tools::fixPhone($params["Phone"],$params["Country"]), 
			'Fax'  					=> Tools::fixPhone($params["custom"]["Fax"],$params["Country"]),
		);
		if($type=="Registrant") {
			$ascio->Name = trim($params["First Name"]. " ". $params["Last Name"]);		
		} else {
			$ascio->FirstName 	= trim($params["First Name"]);
			$ascio->LastName 	= trim($params["Last Name"]);
		}
		return $ascio; 
	}
	public function mapGetContactDetailContact($values, $contact, $type) {
		if($contact) {
			$values [$type]["First Name"] = $contact ->FirstName;
			$values [$type]["Last Name"]  = $contact ->LastName;
			$values [$type] ["Company Name"] = $contact ->OrgName;
			$values [$type] ["Email"] = $contact ->Email;
			$values [$type] ["Phone Number"] = $contact ->Phone;
			$values [$type] ["Fax Number"] = $contact ->Fax;
			$values [$type] ["Address1"] = $contact ->Address1;
			$values [$type] ["Address2"] = $contact ->Address2;
			$values [$type] ["State"] = $contact ->State;
			$values [$type] ["Postcode"] = $contact ->PostalCode;
			$values [$type] ["City"] = $contact ->City;
			$values [$type] ["Country Code"] = $contact ->CountryCode;
		}
		return $values;
	}
	public function mapGetContactDetailRegistrant($values, $registrant) {
		$name = Tools::splitName($registrant->Name);
		$values ["Registrant"]["First Name"] = $name["first"];
		$values ["Registrant"]["Last Name"]  = $name["last"];
		$values ["Registrant"] ["Company Name"] = $registrant->OrgName;
		$values ["Registrant"] ["Email"] = $registrant->Email;
		$values ["Registrant"] ["Phone Number"] = $registrant->Phone;
		$values ["Registrant"] ["Fax Number"] = $registrant->Fax;
		$values ["Registrant"] ["Address1"] = $registrant->Address1;
		$values ["Registrant"] ["Address2"] = $registrant->Address2;
		$values ["Registrant"] ["State"] = $registrant->State;
		$values ["Registrant"] ["Postcode"] = $registrant->PostalCode;
		$values ["Registrant"] ["City"] = $registrant->City;
		$values ["Registrant"] ["Country Code"] = $registrant->CountryCode;
		return $values;
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
			if($this->params["Username"]) $this->account = $this->params["Username"];
			if($this->params["Password"]) $this->password = $this->params["Password"];
			if(isset( $params["domainObj"]) && isset( $params["sld"])) {
				$this->domainName = $params["domainObj"]->getIdnSecondLevel().".".$params["domainObj"]->getTopLevel();		
			} else {
				$this->domainName = $params["domainName"];
			}
		} 
		return $this->params;
	}
	public function getHandle($type,$whmcsId,$domainName) {
		$handleId = Capsule::table('tblasciohandles')
			->where([
				"type" => $type, 
				"whmcs_id" => $whmcsId,
				"domain" => $domainName
			])
			->value('ascio_id');
		if(!$handleId) {
			// to stay compatible with older version. Will be updated with the first search-domain. 
			$handleId = Capsule::table('tblasciohandles')
				->where([
					"type" => $type, 
					"whmcs_id" => $whmcsId
				])
				->value('ascio_id');				
		};		
		return $handleId;
	}
	public function getHandlesByDomain($domainName) {
		$result = Capsule::select('select tblasciohandles.ascio_id from tblasciohandles inner join tbldomains on tblasciohandles.whmcs_id = tbldomains.id where domain="'.$domainName.'"');
		return $result;
	}
	public function setHandle($domain) {		
		$this->storeHandle("domain",$this->params["domainid"],$domain->DomainHandle,$domain->DomainName);
		/*
		$this->storeHandle("registrant",$domain->Registrant->Handle);
		$this->storeHandle("contact",$domain->AdminContact->Handle);
		$this->storeHandle("contact",$domain->TechContact->Handle);
		$this->storeHandle("contact",$domain->BillingContact->Handle);
		*/
	}
	public function storeHandle($type,$whmcsId, $ascioId,$domain) {
		if(!isset($ascioId)) return; 		
		$handle = $this->getHandle($type,$whmcsId,$domain);
		if(!$handle) {		
			Capsule::table('tblasciohandles')
				->insert([
					"ascio_id" => $ascioId,
					"whmcs_id" => $whmcsId,
					"type" => $type,
					"domain" => $domain]);		
		} else {			
			Capsule::table('tblasciohandles')
				->where('ascio_id',$ascioId)
				->update([
					"whmcs_id" => $whmcsId,
					"type" => $type,
					"domain" => $domain]);			
		}		
	}
	public function deleteOldHandles($domainName) {
		foreach($this->getHandlesByDomain($domainName) as $key => $ascioId)  {
				Capsule::table('tblasciohandles')->where('ascio_id','=',$ascioId)->delete();
				logActivity("deleteHandle ". $ascioId . " - domain:".$domainName );	
		}			
	}
	public function deleteOldHandle($whmcsDomainId) {
		Capsule::table('tblasciohandles')->where('whmcs_id','=',$whmcsDomainId)->delete();			
	}
}
?>