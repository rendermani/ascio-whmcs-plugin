<?php

class es extends Request {	
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		//company in spain
		if($params["country"]=="ES" && $params["companyname"]) {
			$contact["RegistrantType"]  = 612;
		} 
		// company outside spain
		elseif( $params["companyname"]) {
			$contact["RegistrantType"]  = 1;
		} 
		// individual in spain
		elseif ($params["country"] =="ES") {
			$contact["RegistrantType"]  = 1;
		} 
		// individual outside span
		else $contact["RegistrantType"]  = 877;
		$contact["RegistrantNumber"] =  $params["additionalfields"]["ID Form Number"];
		return $contact;
	}
	protected function mapToAdmin($params) {
		$contact = parent::mapToAdmin($params);
		if($params["country"]=="ES") {
			$contact["Type"]=1;			
		} else {
			$contact["Type"]=0;
		}
		$contact["OrgName"] = null;
		$contact["OrganisationNumber"] = $params["additionalfields"]["ID Form Number"];
		return $contact;
	}
	public function transferDomain($params=false) {
		$params["original"]["regperiod"] = 0 ; 
		parent::transferDomain($params);
	}
}
?>
