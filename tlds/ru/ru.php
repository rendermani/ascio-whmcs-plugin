<?php

class ru extends Request {	
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);		
		
		if($params["additionalfields"]["Registrant Type"] == "ORG") {
			$contact["RegistrantNumber"] = $params["additionalfields"]["Russian Organizations Taxpayer Number 1"];
			$contact["VatNumber"] = $params["additionalfields"]["Russian Organizations Territory-Linked Taxpayer Number 2"];
			$contact["RegistrantType"] = "ORG";
		} else {
			$contact["RegistrantNumber"] = 	$params["additionalfields"]["Individuals Passport Number"] . ", " . $params["additionalfields"]["Individuals Passport Issue Date"] . ", " . $params["additionalfields"]["Individuals Passport Issuer"];
			$contact["RegistrantDate"] = $params["additionalfields"]["Individuals Birthday"];
			$contact["RegistrantType"] = "PRS";	
			$contact["OrgName"] = $contact["Name"];
		}
		$contact["Options"] = $params["additionalfields"]["NIC/D handle"];
		return $contact;
	}
	protected function mapToAdmin($params) {
		if($params["additionalfields"]["Registrant Type"] == "ORG") { 
			$company = $params["companyname"];
		} else {
			$company = $params["firstname"]. " ".$params["lastname"];
		}
		$registrant = $this->mapToRegistrant($params);
		$contact = Array(
			'FirstName' 	=> $params["firstname"],
			'LastName' 		=> $params["lastname"],
			'OrgName' 		=> $company,
			'Address1' 		=> $params["address1"],	
			'Address2' 		=> $params["address2"],
			'PostalCode' 	=> $params["postcode"],
			'City' 			=> $params["city"],
			'State' 		=> $params["state"],		
			'CountryCode' 	=> $params["country"],	
			'Email' 		=> $params["email"],
			'Phone'			=> Tools::fixPhone($params["fullphonenumber"],$country),
			'Fax' 			=> Tools::fixPhone($params["custom"]["Fax"],$country),
		);
		return $contact;
	}
	public function transferDomain($params=false) {
		$params["regperiod"] = 0 ;
		return parent::transferDomain($params);
	}	

}
?>