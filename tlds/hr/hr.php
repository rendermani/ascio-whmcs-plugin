<?php

namespace ascio;

/**
 * .hr TLD Plugin
 * 
 * Required fields:
 * - Registrant.VAT (from additionalfields["VAT Number"])
 */
class hr extends Request {
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		
		if (isset($params["additionalfields"]["VAT Number"])) {
			$contact["VatNumber"] = $params["additionalfields"]["VAT Number"];
		}
		
		return $contact;
	}
}
?>
