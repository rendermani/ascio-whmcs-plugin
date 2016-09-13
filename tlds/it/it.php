<?php
class it extends Request {	
	public function transferDomain($params=false) {		
		$paramsNew = array(			
			"eppcode"   => $params["eppcode"],
			"regperiod"  =>  $params["regperiod"],
			"domainname" => $params["domainname"]);
		return parent::transferDomain($paramsNew); 
	}
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		$map = array(
			"Italian and foreign natural persons" 	=> "1",
			"Companies/one man companies" 			=> "2",
			"Freelance workers/professionals" 		=> "3",
			"non-profit organizations" 				=> "5",
			"public organizations" 					=> "4",
			"other subjects" 						=> "6",
			"non natural foreigners" 				=> "7"
		);
		// 7 is for all non-italian complanies. Fix invalid user-inputs

		if(($params["countrycode"] != "IT") && $contact["OrgName"]) {
			$contact["RegistrantType"] = "7";	
		} 
		else {
			$contact["RegistrantType"] 		= $map[$params["additionalfields"]["Legal Type"]];
		}
		$contact["RegistrantNumber"]  	= $params["additionalfields"]["Tax ID"];
		//var_dump($contact);
		//var_dump($params);
		
		return $contact;
	}
	protected function mapToTrademark($params) {		
		// If the country is non italian, state is not needed. Just set any country. Won't be in the Whois
		if($params["additionalfields"]["Legal Type"]== "Italian and foreign natural persons") {
			return array ("Country" => "DK");
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