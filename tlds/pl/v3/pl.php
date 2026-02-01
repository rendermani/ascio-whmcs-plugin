<?php
/**
 * .PL (Poland) TLD Plugin for Ascio v3 API
 *
 * NASK (Polish Registry) requirements:
 * - Transfers have 0 year period
 */

namespace ascio;

require_once(dirname(__FILE__) . "/../../../lib/Request.php");

class pl extends Request {

	/**
	 * Transfer domain - PL transfers have 0 year period
	 */
	public function transferDomain($params = false) {
		$params["regperiod"] = 0;
		return parent::transferDomain($params);
	}
}
?>
