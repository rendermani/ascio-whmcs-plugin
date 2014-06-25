<?php

class ru extends Request {	
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		if($contact["OrgName"] && $params["additionalfields"]["INN number"] && $params["additionalfields"]["KPP number"]) {
			$contact["RegistrantNumber"] = $params["additionalfields"]["INN number"];
			$contact["VatNumber"] = $params["additionalfields"]["KPP number"];
			$contact["RegistrantType"] = "ORG";			
		}
		return $contact;
	}

}
?>