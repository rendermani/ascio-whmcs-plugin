<?php

class nl extends Request {	
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		$contact["RegistrantNumber"] = $params["additionalfields"]["Organisation Number"];
		$contact["RegistrantType"] = "PERSOON";	
		if($contact["OrgName"] && $contact["CountryCode"] == "NL") {
			$contact["RegistrantType"] = "BV";	
		} else if($contact["OrgName"]) {
			$contact["RegistrantType"] = "BGG";	
		} else {
			$contact["RegistrantType"] = "PERSOON";	
		}
		if($contact["OrgName"] && ($contact["RegistrantNumber"]==""))	throw new AscioException("Please enter a valid Organization Number");
		return $contact;
	}
	protected function mapToAdmin($params) {
		$contact = parent::mapToAdmin($params);
		$contact["Type"] = "BGG";			
		return $contact;
	}
	protected function mapToTech($params) {
		$contact = parent::mapToTech($params);
		$contact["Type"] = "BGG";			
		return $contact;
	}
	protected function mapToBilling($params) {
		$contact = parent::mapToBilling($params);
		$contact["Type"] = "BGG";			
		return $contact;
	}
}
?>