<?php

class jobs extends Request {	
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		$contact["RegistrantType"]	 = $params["additionalfields"]["Company position"];
		$contact["RegistrantNumber"] =  $params["additionalfields"]["Business Nature"];
		return $contact;
	}
	public function registerDomain($params=false) {		
		$params = $this->setParams($params);
		$ascioParams = $this->mapToOrder($params,"Register_Domain");
		$ascioParams["order"]["Domain"]["DomainPurpose"] = $params["additionalfields"]["Website"];
		$result = $this->request("CreateOrder",$ascioParams);
		if (!$result) {
			$this->setWhmcsStatus($domainName,"Pending","Register_Domain");
		}
		return $result;
	}
}
?>