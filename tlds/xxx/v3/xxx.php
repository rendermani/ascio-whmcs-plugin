<?php
/**
 * .XXX TLD Plugin for Ascio v3 API
 *
 * ICM Registry requirements:
 * - Member of sponsored community determines registration options
 * - Options: "member" or "non-member"
 */

namespace ascio;

require_once(dirname(__FILE__) . "/../../../lib/Request.php");

class xxx extends Request {

	/**
	 * Register domain with XXX-specific community membership option
	 */
	public function registerDomain($params = false) {
		$params = $this->setParams($params);
		$ascioParams = $this->mapToOrder($params, "Register_Domain");

		// Set member/non-member option based on sponsored community membership
		if(($params["additionalfields"]["Member of sponsored community"] ?? null) == "on") {
			$ascioParams["Order"]["Options"] = "member";
		} else {
			$ascioParams["Order"]["Options"] = "non-member";
		}

		$result = $this->sendRequest("CreateOrder", $ascioParams);
		return $result;
	}
}
?>
