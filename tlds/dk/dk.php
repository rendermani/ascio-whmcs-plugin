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
		array("error" => "This TLD cannot be renewed or expired. Please contact support for further information.");		
	}
	public function expireDomain($params) {
		array("error" => "This TLD cannot be renewed or expired. Please contact support for further information.");			
	}	

}
?>