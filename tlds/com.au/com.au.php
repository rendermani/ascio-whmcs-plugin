<?php
class com_au extends Request {	
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);

		if($params["additionalfields"]["Registrant ID Type"]=="Business Registration Number") {
			$params["additionalfields"]["Registrant ID Type"]="OTHER";
		}
		$contact["RegistrantType"]   = $params["additionalfields"]["Registrant ID Type"];
		$contact["RegistrantNumber"] = $params["additionalfields"]["Registrant ID"];

		echo "<pre>";
		var_dump($params["additionalfields"]);
		var_dump($contact);
		echo "</pre>";
		return $contact;
	}
	public function registerDomain ($params=false) {
		$params["Application Purpose"] = $params["additionalfields"]["Eligibility Type"];
		return parent::registerDomain($params); 
		
	}
	protected function mapToTrademark ($params) {
		$typeMap = array(
			"Australian Company Number (ACN)" 	=> "ACN",
			"ACT Business Number" 				=> "ACT BN",
			"NSW Business Number" 				=> "NSW BN",
			"NT Business Number" 				=> "NT BN",
			"QLD Business Number" 				=> "QLD BN",
			"SA Business Number" 				=> "BN",
			"TAS Business Number" 				=> "TAS BN",
			"VIC Business Number" 				=> "VIC BN",
			"WA Business Number" 				=> "WA BN",
			"Trademark (TM)" 					=> "TM",
			"Other - Used to record an Incorporated Association number" => "OTHER",
			"Australian Business Number (ABN)" 	=> "ABN"
		);
		$tm = parent::mapToTrademark($params);
		if($params["additionalfields"]["Eligibility Reason"]=="Domain name is an Exact Match Abbreviation or Acronym of your Entity or Trading Name.") {
			$tm["Name"]   =  $params["additionalfields"]["Eligibility Name"];
			$tm["Number"] =  $params["additionalfields"]["Eligibility ID"];	
			$tm["Type"]	  =  $typeMap[ $params["additionalfields"]["Eligibility ID Type"]];
		}	
		echo "trademark";
		var_dump($tm);
		return $tm;	
	}
}
?>