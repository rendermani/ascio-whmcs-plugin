<?php

namespace ascio\v2\domains;

class com_au extends Request {	
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);

		if($params["additionalfields"]["Registrant ID Type"]=="Business Registration Number") {
			$params["additionalfields"]["Registrant ID Type"]="OTHER";
		}
		$contact["RegistrantType"]   = $params["additionalfields"]["Registrant ID Type"];
		$contact["RegistrantNumber"] = $params["additionalfields"]["Registrant ID"];
		return $contact;
	}
	public function registerDomain ($params=false) {
		$params["Application Purpose"] = $params["additionalfields"]["Eligibility Type"];
		return parent::registerDomain($params); 
		
	}
	public function transferDomain($params=false) {
		if($params["regperiod"] == 1) $params["regperiod"] = 0 ;
		else if($params["regperiod "] > 1 ) return array("error" => "Invalid RegPeriod. Allowed: 1");
		return parent::transferDomain($params);
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
		return $tm;	
	}
}
?>