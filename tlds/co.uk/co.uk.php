<?php
class ok_uk extends Request {	
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
		// 7 is for all non-italian complanies. Fix invalid user-inputs

		$contact["RegistrantType"] 			= $map[$params["additionalfields"]["Legal Type"]];
		$contact["RegistrantNumber"] 		= $map[$params["additionalfields"]["Company ID Number"]];
		if(($contact["CountryCode"] != "GB") &! $isCompany) $contact["RegistrantType"] ="FIND";
		return $contact;
	}	
}
?>