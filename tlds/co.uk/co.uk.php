<?php

namespace ascio\v2\domains;

class co_uk extends Request {	
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		$map = array(
			"Individual" => "IND",
			"UK Limited Company"  => "LTD",
			"UK Public Limited Company"  => "PLC",
			"UK Partnership"  => "PTNR",
			"UK Limited Liability Partnership"  => "LLP",
			"Sole Trader"  => "STRA",
			"UK Registered Charity"  => "RCHAR",
			"UK Entity (other)"  => "OTHER",
			"Foreign Organization"  => "FCORP",
			"Other foreign organizations"  => "FOTHER"
		);
		$isCompany = isset($contact["OrgName"]);		
		$contact["RegistrantType"] 			= $map[$params["additionalfields"]["Legal Type"]];
		$contact["RegistrantNumber"] 		= $params["additionalfields"]["Company ID Number"];
		if(($contact["CountryCode"] != "GB") &! $isCompany) $contact["RegistrantType"] ="FIND";
		if($contact["RegistrantType"] == "IND") {
			$contact["OrgName"] = null; 
		}
		return $contact;
	}
	public function transferDomain($params=false) {
		$params["regperiod"] = 0 ;
		return parent::transferDomain($params);
	}		
}
?>