<?php

namespace ascio\v2\domains;

/**
 * .RS (Serbia) TLD Plugin
 *
 * Required fields:
 * - Registrant.Type (additionalfields["Registrant Type"])
 * - Registrant.Nr. (additionalfields["Registrant Number"])
 * - Admin.Nr. (additionalfields["Admin Number"])
 */
class rs extends Request {
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		$contact["RegistrantType"] = $params["additionalfields"]["Registrant Type"];
		$contact["RegistrantNumber"] = $params["additionalfields"]["Registrant Number"];
		return $contact;
	}

	protected function mapToAdmin($params) {
		$contact = parent::mapToAdmin($params);
		$contact["OrganisationNumber"] = $params["additionalfields"]["Admin Number"];
		return $contact;
	}
}
?>
