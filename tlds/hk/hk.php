<?php

namespace ascio;

/**
 * .hk TLD Plugin
 * 
 * Required fields:
 * - Registrant.Type (from additionalfields["Registrant Type"])
 * - Registrant.Nr. (from additionalfields["Registrant Number"])
 */
class hk extends Request {
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
}
?>
