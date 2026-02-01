<?php
/**
 * .PRO TLD Plugin for Ascio v3 API
 *
 * RegistryPro requirements:
 * - Profession field stored in RegistrantType and Details
 */

namespace ascio;

require_once(dirname(__FILE__) . "/../../../lib/Request.php");

class pro extends Request {

	/**
	 * Map registrant with PRO-specific profession fields
	 */
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		$profession = $params["additionalfields"]["Profession"] ?? null;
		$contact["RegistrantType"] = $profession;
		$contact["Details"] = $profession;
		return $contact;
	}
}
?>
