<?php

namespace ascio;

/**
 * .SI (Slovenia) TLD Plugin
 *
 * Required fields:
 * - Registrant.VAT (additionalfields["VAT Number"])
 * - Registrant.Nr. (additionalfields["Registrant Number"])
 */
class si extends Request {
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		$contact["VatNumber"] = $params["additionalfields"]["VAT Number"];
		$contact["RegistrantNumber"] = $params["additionalfields"]["Registrant Number"];
		return $contact;
	}
}
?>
