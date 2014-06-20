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
	}	
}

$additionaldomainfields[".co.uk"][] = array("Name" => "Legal Type", "LangVar" => "uktldlegaltype", "Type" => "dropdown", "Options" => "Individual,UK Limited Company,UK Public Limited Company,UK Partnership,UK Limited Liability Partnership,Sole Trader,UK Registered Charity,UK Entity (other),Foreign Organization,Other foreign organizations", "Default" => "Individual",);
$additionaldomainfields[".co.uk"][] = array("Name" => "Company ID Number", "LangVar" => "uktldcompanyid", "Type" => "text", "Size" => "30", "Default" => "", "Required" => false,);
$additionaldomainfields[".co.uk"][] = array("Name" => "Registrant Name", "LangVar" => "uktldregname", "Type" => "text", "Size" => "30", "Default" => "", "Required" => true,);

?>