<?php

namespace ascio;

/**
 * .ee TLD Plugin (Estonia)
 *
 * Required fields:
 * - Registrant.Type (additionalfields["Registrant Type"])
 * - Registrant.Nr. (additionalfields["Registrant Number"])
 * - Admin.Type (additionalfields["Admin Type"])
 * - Admin.Nr. (additionalfields["Admin Number"])
 * - Tech.Type (additionalfields["Tech Type"])
 * - Tech.Nr. (additionalfields["Tech Number"])
 */
class ee extends Request {
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);

		if (isset($params["additionalfields"]["Registrant Type"])) {
			$contact["RegistrantType"] = $params["additionalfields"]["Registrant Type"];
		}

		if (isset($params["additionalfields"]["Registrant Number"])) {
			$contact["RegistrantNumber"] = $params["additionalfields"]["Registrant Number"];
		}

		return $contact;
	}

	protected function mapToAdmin($params) {
		$contact = parent::mapToAdmin($params);

		if (isset($params["additionalfields"]["Admin Type"])) {
			$contact["Type"] = $params["additionalfields"]["Admin Type"];
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

		if (isset($params["additionalfields"]["Tech Number"])) {
			$contact["OrganisationNumber"] = $params["additionalfields"]["Tech Number"];
		}

		return $contact;
	}
}
?>
