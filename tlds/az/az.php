<?php

namespace ascio;

/**
 * .az TLD plugin
 *
 * Requires: Registrant.Type, Registrant.VAT
 */
class az extends Request {
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);

		// Set RegistrantType from additional fields
		if (isset($params["additionalfields"]["Registrant Type"]) && !empty($params["additionalfields"]["Registrant Type"])) {
			$contact["RegistrantType"] = $params["additionalfields"]["Registrant Type"];
		}

		// Set VatNumber from additional fields (try both field names)
		if (isset($params["additionalfields"]["VAT Number"]) && !empty($params["additionalfields"]["VAT Number"])) {
			$contact["VatNumber"] = $params["additionalfields"]["VAT Number"];
		} elseif (isset($params["additionalfields"]["Registrant VAT"]) && !empty($params["additionalfields"]["Registrant VAT"])) {
			$contact["VatNumber"] = $params["additionalfields"]["Registrant VAT"];
		}

		return $contact;
	}
}
?>
