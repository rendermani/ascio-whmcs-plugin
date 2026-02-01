<?php
/**
 * .US (United States) TLD Plugin for Ascio v3 API
 *
 * Neustar/Registry Services requirements:
 * - Domain Purpose (Nexus) required
 * - Purpose values: P1 (Business), P2 (Non-profit), P3 (Personal)
 * - Domain.DomainPurpose field in order
 */

namespace ascio\v3\domains;

require_once(dirname(__FILE__) . "/../../../lib/RequestV3.php");

class us extends RequestV3 {

	/**
	 * Map order with US-specific Domain Purpose (Nexus)
	 */
	public function mapToOrder($params, $orderType) {
		$order = parent::mapToOrder($params, $orderType);
		$order['Order']['Domain']['DomainPurpose'] = $params["additionalfields"]["Domain Purpose"] ?? null;
		return $order;
	}
}
?>
