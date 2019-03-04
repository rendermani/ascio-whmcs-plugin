<?php
require_once("DnsService.php");
require_once("Tools.php");


class DnsZone {
	var $dnsService;
	var $name;
	var $records;
	var $owner;
	public function __construct  ($params,$name=false) {
		$this->dnsService = new DnsService($params["Username"],$params["Password"],$params["UserName"]);
		if($name) $this->name = $name;
		else $this->name = $params["sld"] . "." . $params["tld"];
		$this->owner = $params["Username"];		
	}
	public function get($params) {
	    # Put your code here to get the current DNS settings - the result should be an array of hostname, record type, and address
		$zone = new GetZone();
		$zone->zoneName = $this->name;
		$result = $this->dnsService->GetZone($zone);	
		if($result->GetZoneResult->StatusCode == 404) return false;	
		$this->records = array();
		$usedTypes = array("A","CNAME","MX","AAAA","TXT","WebForward");
		foreach ($result->zone->Records->Record as $key => $record) {
			if(!in_array(get_class($record), $usedTypes)) continue;
			$this->records[] = $record;
		}		
		return $this->records;
	}
	public function update($params) {
		$oldRecords = $this->get($params);
		if(!$oldRecords) {
			$result = $this->createZone($params);
		} 
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
				$result = $this->updateRecord($record,$newRecord);
			} 
		}
		$result = $this->createRecord($newRecords[count($newRecords)-1]);
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
			if($newRecord["priority"]) $record->Priority = $newRecord["priority"];
		}
		$updateRecord = new UpdateRecord();
		$updateRecord->zoneName   = $this->name;
		$updateRecord->record = $record;
		$result = $this->dnsService->updateRecord($updateRecord);
		if($result->StatusCode != 200 ) {
			Tools:log($result->StatusMessage);
		}
		return $result;
	}
	private function replaceRecord($record,$newRecord) {
		$this->deleteRecord($record);
		$result = $this->createRecord($newRecord);
		return $result;
	}
	private function deleteRecord($record) {
		$deleteRecord = new DeleteRecord();
		$deleteRecord->recordId   = $record->Id;
		$result = $this->dnsService->deleteRecord($deleteRecord);
		if($result->StatusCode != 200 ) {
			Tools:log($result->StatusMessage);
		}
		return $result;
	}
	private function createRecord($record) {
		if(!$record["address"]) return ;
		$type =  $record["type"] == "URL" ? "WebForward" : $record["type"];		
		$newRecord = new $type();	
		$newRecord->Source 	= $this->addZonename($record["hostname"]);
		$newRecord->Target 	= $this->addZonename($record["address"]);
		$newRecord->Priority 	= $record["priority"];
		if($record["type"] == "URL") {
			$newRecord->RedirectionType ="Permanent";		
		}
		$createRecord = new CreateRecord();
		$createRecord->zoneName = $this->name;
		$createRecord->record = $newRecord;
		$createRecord->owner = $this->owner;

		$result = $this->dnsService->createRecord($createRecord);	
		if($result->StatusCode != 200 ) {
				Tools:log($result->StatusMessage);
		}
		return $result;
	}
	private function createZone($records) {		
		$createZone = new CreateZone();
		$createZone->zoneName = $this->name;
		$createZone->owner 	  = $this->owner;
		$result = $this->dnsService->createZone($createZone);
		if($result->StatusCode != 200 ) {
			Tools:log($result->StatusMessage);
		}
		return $result;
		
	}
	public function createUser($params) { 	
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
		} 
		return $userName;
	}
	public function convertToWhmcs($result) {		
		$records = array();
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
			} elseif (get_class($record)=="WebForward") {
			  	$records[] = 		
					array( 
						"hostname" 	=> $source, 
						"type" 		=>  "URL" ,
						"address" 	=> $target
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
		if($record == "@") return $record; 
		if(!strpos($record, ".")) return $record. ".".$this->name;
		else return $record;
	}
}
?>