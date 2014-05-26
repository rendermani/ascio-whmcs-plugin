<?
require_once("DnsService.php");
require_once("Tools.php");


class DnsZone {
	var $dnsService;
	var $name;
	var $records;
	var $owner;
	public function __construct  ($params,$name=false) {

		$this->dnsService = new DnsService($params["Username"],$params["Password"]);
		if($name) $this->name = $name;
		else $this->name = $params["sld"] . "." . $params["tld"];
		$this->owner = $params["Username"] . "-whmcs";		
	}
	public function get($params) {
	    # Put your code here to get the current DNS settings - the result should be an array of hostname, record type, and address
		$zone = new GetZone();
		$zone->zoneName = $this->name;
		$result = $this->dnsService->GetZone($zone);		
		$this->records = array();
		$usedTypes = ["A","CNAME","MX","AAAA","TXT"];
		foreach ($result->zone->Records->Record as $key => $record) {
			if(!in_array(get_class($record), $usedTypes)) continue;
			$this->records[] = $record;
		}		
		//Tools::dump($this->records,"DnsZone::get records"); 
		return $this->records;
	}
	public function update($params) {
		//Tools::dump($params,"DnsZone::update start");
		$oldRecords = $this->get($params);
		$newRecords = $params["dnsrecords"];
		for($i=0;$i < count($newRecords)-1; $i++) {		
			//source
			$newRecord = $newRecords[$i];
			$newRecordValues = $newRecord["hostname"] ."-".$newRecord["type"]."-".$newRecord["address"]."-".$newRecord["priority"];
			//target
			$record = $oldRecords[$i]; 
			$source = $this->removeZonename($record->Source);	
			$target = $this->removeZonename($record->Target);
			$recordValues =  $source ."-".get_class($record)."-".$target."-".$record->Priority;
			if($newRecordValues != $recordValues) {
				$result[] = $this->updateRecord($record,$newRecord);
			} 
		}
		$result[] = $this->createRecord($newRecords[count($newRecords)-1]);
			
		return $result;
	}
	private function updateRecord($record,$newRecord) {		
		if($newRecord["type"] != get_class($record)) {
			$this->replaceRecord($record,$newRecord);
		} elseif (!$newRecord["address"]) {
			$this->deleteRecord($record);
		}
		else {
			$record->Source 	= $this->addZonename($newRecord["hostname"]);
			$record->Target 	= $this->addZonename($newRecord["address"]);
			//if($newRecord["priority"]) $record->Priority = $newRecord["priority"];
		}
		//Tools::dump($record,"DnsZone::updateRecord start");
		$updateRecord = new UpdateRecord();
		$updateRecord->zoneName   = $this->name;
		$updateRecord->record = $record;
		$result = $this->dnsService->updateRecord($updateRecord);
		//Tools::dump($result,"DnsZone::updateRecord result");
		return $result;
	}
	private function replaceRecord($record,$newRecord) {
		//Tools::dump("start","DnsZone::updateRecord start");
		$this->deleteRecord($record);
		$result = $this->createRecord($newRecord);
		//Tools::dump("end","DnsZone::updateRecord result");
		return $result;
	}
	private function deleteRecord($record) {
		//Tools::dump($record,"DnsZone::deleteRecord start");
		$deleteRecord 		      = new DeleteRecord();
		$deleteRecord->recordId   = $record->Id;
		$result = $this->dnsService->deleteRecord($deleteRecord);
		//Tools::dump($result,"DnsZone::deleteRecord result");
		return $result;
	}
	private function createRecord($record) {
		//Tools::dump($record,"DnsZone::createRecord start");
		if(!$record["address"]) return ;
		$type =  $record["type"];
		$newRecord = new $type();	
		$newRecord->Source 	= $this->addZonename($record["hostname"]);
		$newRecord->Target 	= $this->addZonename($record["address"]);
		$newRecord->Priority 	= $record["priority"];
		$createRecord = new CreateRecord();
		$createRecord->zoneName = $this->name;
		$createRecord->record = $newRecord;
		$createRecord->owner = $this->owner;
		$result = $this->dnsService->createRecord($createRecord);
		//Tools::dump($createRecord,"DnsZone::createRecord request");
		/*Tools::dump($result,"DnsZone::createRecord result");*/

		return $result;
	}
	public function createUser($params) { 	
		//Tools::dump($params,"DnsZone::createUser result");
		$getUser = new GetUser();
		$getUser->userName = $this->owner;
		$result = $dns->getUser($getUser);
		if($result->GetUserResult->StatusCode == 414) {
			$user = new User;
			$user->Name = "WHMCS API";
			$user->UserName = $this->owner;
			$user->Password = $accountPw;
			$user->Role = "CustomerAdvanced";
			$user->Email ="ml@webrender.de";
			$createUser = new CreateUser();
			$createUser->user = $user; 
			$result = $dns->CreateUser($createUser);
			//Tools::dump($result,"DnsZone::createUser result");		
		} else {
			echo "user exists<br/>";
		}
		return $userName;
	}
	public function convertToWhmcs($result) {		
		$records = array();
		//Tools::dump($result,"convert to whmcs");
		foreach ($result as $key => $record) {
			$source = $this->removeZonename($record->Source);
			$target = $this->removeZonename($record->Target);
			if(get_class($record)=="MX") {
				$records[] = 		
				array( 
					"hostname" 	=> $source, 
					"type" 		=>  get_class($record),
					"address" 	=> $target, 
					"priority" 	=> $record->Priority 
				);
			} else {
				$records[] = 		
				array( 
					"hostname" 	=> $source, 
					"type" 		=>  get_class($record),
					"address" 	=> $target
				);
			}

		}
		return $records;
	}	
	private function removeZonename($record) {
		return str_replace(".".$this->name, "", $record);
	}
	private function addZonename($record) {
		//Tools::dump($record, "pos:".strpos($record, "."). " - ".$this->name);
		if(!strpos($record, ".")) return $record. ".".$this->name;
		else return $record;
	}
}
?>