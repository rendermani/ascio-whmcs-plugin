<?php

class dk extends Request {	
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		$contact["RegistrantNumber"] = $params["additionalfields"]["Registrant CVR nr."];
		if($contact["OrgName"]) {
			$contact["RegistrantType"] = "V";	
		} else {
			$contact["RegistrantType"] = "P";
		}
		return $contact;
	}
		protected function mapToAdmin($params) {
		$contact = parent::mapToAdmin($params);
		$contact["OrganisationNumber"] = $params["additionalfields"]["Administrator CVR nr."];
		if($contact["OrgName"]) {
			$contact["Type"] = "V";	
		} else {
			$contact["Type"] = "P";
		}
		return $contact;
	}
	public function renewDomain($params) {
		array("error" => "This TLD is renewed through DK-Hostmaster. Please contact the support for further questions.");		
	}
	public function expireDomain($params) {
		$params = $this->setParams($params);
		try {
			$ascioParams = parent::mapToOrder($params,"Expire_Domain");
			$ascioParams["order"]["Comments"]="Unconfirmed";
		} catch (AscioException $e) {
			return array("error" => $e->getMessage());
		}
		$result =  $this->request("CreateOrder",$ascioParams);
		return $result;
	}	

}
?>