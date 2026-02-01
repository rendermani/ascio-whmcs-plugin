<?php
/**
 * .PRO TLD Plugin for Ascio v3 API
 *
 * RegistryPro requirements:
 * - Profession field stored in RegistrantType and Details
 */

namespace ascio\v3\domains;

require_once(dirname(__FILE__) . "/../../../lib/RequestV3.php");

class pro extends RequestV3 {

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
