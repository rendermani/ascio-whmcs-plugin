<?php

namespace ascio;

/**
 * .ie TLD Plugin
 * 
 * Required fields:
 * - Registrant.Type (from additionalfields["Registrant Type"])
 * - Registrant.Nr. (from additionalfields["Registrant Number"])
 * - TM.Name (from additionalfields["Trademark Name"])
 */
class ie extends Request {
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		
		if (isset($params["additionalfields"]["Registrant Type"])) {
			$contact["RegistrantType"] = $params["additionalfields"]["Registrant Type"];
		}
		
		if (isset($params["additionalfields"]["Registrant Number"])) {
			$contact["RegistrantNumber"] = $params["additionalfields"]["Registrant Number"];
		}
		
		return $contact;
	}
	
	protected function mapToTrademark($params) {
		if (isset($params["additionalfields"]["Trademark Name"]) && $params["additionalfields"]["Trademark Name"]) {
			return ["Name" => $params["additionalfields"]["Trademark Name"]];
		}
		return null;
	}
}
?>
