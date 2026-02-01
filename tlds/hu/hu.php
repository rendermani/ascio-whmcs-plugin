<?php

namespace ascio\v2\domains;

/**
 * .hu TLD Plugin
 * 
 * Required fields:
 * - Registrant.VAT (from additionalfields["VAT Number"])
 * - Registrant.Nr. (from additionalfields["Registrant Number"])
 * - TM.Name (from additionalfields["Trademark Name"])
 */
class hu extends Request {
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		
		if (isset($params["additionalfields"]["VAT Number"])) {
			$contact["VatNumber"] = $params["additionalfields"]["VAT Number"];
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
