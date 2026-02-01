<?php

namespace ascio\v2\domains;

/**
 * .ec TLD plugin
 *
 * Requires: Registrant.VAT, Registrant.Nr.
 */
class ec extends Request {
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);

		// Set VatNumber from additional fields (try both field names)
		if (isset($params["additionalfields"]["VAT Number"]) && !empty($params["additionalfields"]["VAT Number"])) {
			$contact["VatNumber"] = $params["additionalfields"]["VAT Number"];
		} elseif (isset($params["additionalfields"]["Registrant VAT"]) && !empty($params["additionalfields"]["Registrant VAT"])) {
			$contact["VatNumber"] = $params["additionalfields"]["Registrant VAT"];
		}

		// Set RegistrantNumber from additional fields
		if (isset($params["additionalfields"]["Registrant Number"]) && !empty($params["additionalfields"]["Registrant Number"])) {
			$contact["RegistrantNumber"] = $params["additionalfields"]["Registrant Number"];
		}

		return $contact;
	}
}
?>
