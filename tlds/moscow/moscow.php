<?php

namespace ascio\v2\domains;

/**
 * .moscow TLD Plugin
 *
 * Required fields:
 * - Registrant.VAT (additionalfields["VAT Number"])
 * - Registrant.Nr. (additionalfields["Registrant Number"])
 * - Registrant.Details (additionalfields["Registrant Details"])
 * - Admin.Type (additionalfields["Admin Type"])
 * - Admin.Details (additionalfields["Admin Details"])
 * - Admin.Nr. (additionalfields["Admin Number"])
 * - Tech.Type (additionalfields["Tech Type"])
 * - Tech.Details (additionalfields["Tech Details"])
 * - Tech.Nr. (additionalfields["Tech Number"])
 */
class moscow extends Request {
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);

		if (isset($params["additionalfields"]["VAT Number"])) {
			$contact["VatNumber"] = $params["additionalfields"]["VAT Number"];
		}

		if (isset($params["additionalfields"]["Registrant Number"])) {
			$contact["RegistrantNumber"] = $params["additionalfields"]["Registrant Number"];
		}

		if (isset($params["additionalfields"]["Registrant Details"])) {
			$contact["Details"] = $params["additionalfields"]["Registrant Details"];
		}

		return $contact;
	}

	protected function mapToAdmin($params) {
		$contact = parent::mapToAdmin($params);

		if (isset($params["additionalfields"]["Admin Type"])) {
			$contact["Type"] = $params["additionalfields"]["Admin Type"];
		}

		if (isset($params["additionalfields"]["Admin Details"])) {
			$contact["Details"] = $params["additionalfields"]["Admin Details"];
		}

		if (isset($params["additionalfields"]["Admin Number"])) {
			$contact["OrganisationNumber"] = $params["additionalfields"]["Admin Number"];
		}

		return $contact;
	}

	protected function mapToTech($params) {
		$contact = parent::mapToTech($params);

		if (isset($params["additionalfields"]["Tech Type"])) {
			$contact["Type"] = $params["additionalfields"]["Tech Type"];
		}

		if (isset($params["additionalfields"]["Tech Details"])) {
			$contact["Details"] = $params["additionalfields"]["Tech Details"];
		}

		if (isset($params["additionalfields"]["Tech Number"])) {
			$contact["OrganisationNumber"] = $params["additionalfields"]["Tech Number"];
		}

		return $contact;
	}
}
?>
