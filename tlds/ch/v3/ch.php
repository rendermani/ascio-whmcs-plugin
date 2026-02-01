<?php
/**
 * .CH (Switzerland) TLD Plugin for Ascio v3 API
 *
 * SWITCH (Swiss Registry) requirements:
 * - Transfers have 0 year period
 * - No explicit renew - use unexpire after autorenew
 */

namespace ascio;

require_once(dirname(__FILE__) . "/../../../lib/Request.php");

class ch extends Request {

	/**
	 * Transfer domain - CH transfers have 0 year period
	 */
	public function transferDomain($params = false) {
		$params["regperiod"] = 0;
		return parent::transferDomain($params);
	}

	/**
	 * Renew domain - CH doesn't support explicit renew
	 */
	public function renewDomain($params) {
		$domain = parent::searchDomain($params);
		if($this->hasStatus($domain, "expiring")) {
			return parent::unexpireDomain($params);
		}
		return array("error" => "Domain can't be renewed again.");
	}
}
?>
