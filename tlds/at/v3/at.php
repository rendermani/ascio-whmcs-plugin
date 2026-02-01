<?php
/**
 * .AT (Austria) TLD Plugin for Ascio v3 API
 *
 * NIC.AT (Austrian Registry) requirements:
 * - Transfers with 1 year period should use 0 year period
 */

namespace ascio;

require_once(dirname(__FILE__) . "/../../../lib/Request.php");

class at extends Request {

	/**
	 * Map order with AT-specific transfer period handling
	 */
	public function mapToOrder($params, $orderType) {
		// AT transfers with 1 year period should use 0
		if($orderType == "Transfer_Domain" && ($params["regperiod"] ?? null) == 1) {
			$params["regperiod"] = 0;
		}
		return parent::mapToOrder($params, $orderType);
	}
}
?>
