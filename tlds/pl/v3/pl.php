<?php
/**
 * .PL (Poland) TLD Plugin for Ascio v3 API
 *
 * NASK (Polish Registry) requirements:
 * - Transfers have 0 year period
 */

namespace ascio\v3\domains;

require_once(dirname(__FILE__) . "/../../../lib/RequestV3.php");

class pl extends RequestV3 {

	/**
	 * Transfer domain - PL transfers have 0 year period
	 */
	public function transferDomain($params = false) {
		$params["regperiod"] = 0;
		return parent::transferDomain($params);
	}
}
?>
