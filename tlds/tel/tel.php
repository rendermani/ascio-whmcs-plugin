<?php

namespace ascio;

/**
 * .TEL TLD Plugin
 *
 * Required fields:
 * - Registrant.Details (additionalfields["Registrant Details"])
 */
class tel extends Request {
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		$contact["Details"] = $params["additionalfields"]["Registrant Details"];
		return $contact;
	}
}
?>
