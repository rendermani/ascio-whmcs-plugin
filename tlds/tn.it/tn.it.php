<?php
class tn_it extends Request {	
	private $map =  array(
		"Italian and foreign natural persons" 	=> "1",
		"Companies/one man companies" 			=> "2",
		"Freelance workers/professionals" 		=> "3",
		"non-profit organizations" 				=> "5",
		"public organizations" 					=> "4",
		"other subjects" 						=> "6",
		"non natural foreigners" 				=> "7"
	);
	public function transferDomain($params=false) {		
		$params["options"] = "NewRegistrant";
		return parent::transferDomain($params); 
	}
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		if(($params["countrycode"] != "IT") && $contact["OrgName"]) {
			$contact["RegistrantType"] = "7";	
		} 
		else {
			$contact["RegistrantType"] 		= $this->map[$params["additionalfields"]["Legal Type"]];
		}
		$contact["RegistrantNumber"]  	= $params["additionalfields"]["Tax ID"];
		if($contact["RegistrantType"]==1) {
			unset($contact["OrgName"]);
		}
		return $contact;
	}
	protected function mapToAdmin($params) {
		$country = $params["country"];
		if($params["additionalfields"]["Legal Type"] == "Italian and foreign natural persons") {
			$contact = Array(
				'FirstName' 	=> $params["firstname"],
				'LastName' 		=> $params["lastname"],
				'Address1' 		=> $params["address1"],	
				'Address2' 		=> $params["address2"],
				'PostalCode' 	=> $params["postcode"],
				'City' 			=> $params["city"],
				'State' 		=> $params["state"],		
				'CountryCode' 	=> $params["country"],	
				'Email' 		=> $params["email"],
				'Phone'			=> Tools::fixPhone($params["fullphonenumber"],$country),
				'Fax' 			=> Tools::fixPhone($params["custom"]["Fax"],$country),
				'Type'			=> $this->map[$params["additionalfields"]["Legal Type"]],
				'OrganisationNumber' =>  $params["additionalfields"]["Tax ID"]
			);
			return $contact;
		} else {
			return parent::mapToAdmin($params);
		}	
	}
	
	protected function mapToTrademark($params) {		
		// If the country is non italian, state is not needed. Just set any country. Won't be in the Whois
		if($params["additionalfields"]["Legal Type"]== "Italian and foreign natural persons") {
			return array ("Country" => $params["additionalfields"]["Birth country"]);
		} 
	}	
	public function renewDomain($params) {
		$domain = parent::searchDomain($params);
		if($this->hasStatus($domain,"expiring")) {
			return parent::unexpireDomain($params);
		} else return array("error" => "Domain can't be renewed again.");		
	}
	
}
?>