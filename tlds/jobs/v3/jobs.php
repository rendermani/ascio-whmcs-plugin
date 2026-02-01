<?php
/**
 * .JOBS TLD Plugin for Ascio v3 API
 *
 * Employ Media (Registry) requirements:
 * - Company Position as RegistrantType
 * - Business Nature as RegistrantNumber
 * - Website URL as DomainPurpose
 */

namespace ascio\v3\domains;

require_once(dirname(__FILE__) . "/../../../lib/RequestV3.php");

class jobs extends RequestV3 {

	/**
	 * Map registrant with JOBS-specific fields
	 */
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		$contact["RegistrantType"] = $params["additionalfields"]["Company position"] ?? null;
		$contact["RegistrantNumber"] = $params["additionalfields"]["Business Nature"] ?? null;
		return $contact;
	}

	/**
	 * Register domain with JOBS-specific Website field
	 */
	public function registerDomain($params = false) {
		$params = $this->setParams($params);
		$ascioParams = $this->mapToOrder($params, "Register_Domain");
		$ascioParams["Order"]["Domain"]["DomainPurpose"] = $params["additionalfields"]["Website"] ?? null;

		$result = $this->sendRequest("CreateOrder", $ascioParams);
		return $result;
	}
}
?>
