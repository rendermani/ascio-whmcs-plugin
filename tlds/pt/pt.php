<?php

namespace ascio\v2\domains;

/**
 * .pt TLD Plugin (Portugal)
 *
 * Required fields:
 * - Registrant.VAT (additionalfields["VAT Number"])
 * - Registrant.Nr. (additionalfields["Registrant Number"])
 * - Admin.Nr. (additionalfields["Admin Number"])
 * - Tech.Nr. (additionalfields["Tech Number"])
 */
class pt extends Request {
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

	protected function mapToAdmin($params) {
		$contact = parent::mapToAdmin($params);

		if (isset($params["additionalfields"]["Admin Number"])) {
			$contact["OrganisationNumber"] = $params["additionalfields"]["Admin Number"];
		}

		return $contact;
	}

	protected function mapToTech($params) {
		$contact = parent::mapToTech($params);

		if (isset($params["additionalfields"]["Tech Number"])) {
			$contact["OrganisationNumber"] = $params["additionalfields"]["Tech Number"];
		}

		return $contact;
	}
}
?>
