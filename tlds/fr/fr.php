<?php

namespace ascio\v2\domains;

class fr extends Request {	
	protected function mapToTrademark($params) {
		//Registrant is not a company
		if(!$params["companyname"]) {
			$tm = array();	
			$tm["Name"] 	= $params["City of birth (Individual)"];
			$tm["Country"] 	= $params["Country of birth (Individual)"];
			$tm["Date"] 	= $params["Date of birth (Individual)"];
			$tm["Number"]	 = $params["Postal code of city of birth (Individual)"];
		}
		return $tm; 
	}
	protected function mapToRegistrant($params) {		
		$contact = parent::mapToRegistrant($params);
		//Registrant is a company
		if($params["companyname"]) {
			$contact["RegistrantType"] 		= "company";
			$contact["RegistrantNumber"] 	= $params["additionalfields"]["VAT (Company)"];
		} else {
			$contact["RegistrantType"] 		= "Individual";
		}		
		return $contact;
	}

}
?>