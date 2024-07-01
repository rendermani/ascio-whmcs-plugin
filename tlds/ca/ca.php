<?php

namespace ascio\v2\domains;

class ca extends Request {	

	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		
		$map = array(
			"Corporation" => "CCO",
			"Canadian Citizen" => "CCT",
			"Permanent Resident of Canada" => "RES",
			"Government" => "GOV",
			"Canadian Educational Institution" => "EDU",
			"Canadian Unincorporated Association" => "ASS",
			"Canadian Hospital" => "HOP",
			"Partnership Registered in Canada" => "PRT",
			"Trade-mark registered in Canada" => "TDM",
			"Canadian Trade Union" => "TRD",
			"Canadian Political Party" => "PLT",
			"Canadian Library Archive or Museum" => "LAM",
			"Trust established in Canada" => "TRS",
			"Aboriginal Peoples" => "ABO",
			"Legal Representative of a Canadian Citizen" => "LGR",
			"Official mark registered in Canada" => "OMK"
		);
		$contact["RegistrantType"] = $map[$params["additionalfields"]["Legal Type"]];
		return $contact;
	}	
	protected function mapToTrademark($params) {
		$trademark = array();
		$trademark["Country"] = $params["additionalfields"]["Password / ID Card Number"];
		$trademark["Number"] = $params["additionalfields"]["Trademark Number"];
		$trademark["Name"] = $params["additionalfields"]["Trademark Name"];
		if($params["additionalfields"]["Trademark Name"]) {
			$trademark["Country"] =  $params["additionalfields"]["Trademark Country"];
		}
		return $trademark;
	}	
}
?>