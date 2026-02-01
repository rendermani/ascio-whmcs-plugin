<?php

namespace ascio;

/**
 * .SU (Soviet Union legacy) TLD Plugin
 *
 * Required fields:
 * - Registrant.Type (additionalfields["Registrant Type"])
 * - Registrant.VAT (additionalfields["VAT Number"])
 * - Registrant.Nr. (additionalfields["Registrant Number"])
 */
class su extends Request {
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		$contact["RegistrantType"] = $params["additionalfields"]["Registrant Type"];
		$contact["VatNumber"] = $params["additionalfields"]["VAT Number"];
		$contact["RegistrantNumber"] = $params["additionalfields"]["Registrant Number"];
		return $contact;
	}
}
?>
