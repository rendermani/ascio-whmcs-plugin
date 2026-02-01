<?php

namespace ascio;

/**
 * .et TLD plugin
 *
 * Requires: Registrant.VAT
 */
class et extends Request {
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);

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
