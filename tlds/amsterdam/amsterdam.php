<?php

namespace ascio;

/**
 * .amsterdam TLD Plugin
 *
 * Required fields:
 * - Registrant.Type (additionalfields["Registrant Type"])
 * - Registrant.Nr. (additionalfields["Registrant Number"])
 * - Admin.Type (additionalfields["Admin Type"])
 * - Tech.Type (additionalfields["Tech Type"])
 */
class amsterdam extends Request {
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

		return $contact;
	}

	protected function mapToTech($params) {
		$contact = parent::mapToTech($params);

		if (isset($params["additionalfields"]["Tech Type"])) {
			$contact["Type"] = $params["additionalfields"]["Tech Type"];
		}

		return $contact;
	}
}
?>
