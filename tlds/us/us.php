<?php

namespace ascio\v2\domains;

/**
 * .US (United States) TLD Plugin
 *
 * Required fields:
 * - Domain.Purpose (additionalfields["Domain Purpose"])
 *
 * Purpose values: P1 (Business), P2 (Non-profit), P3 (Personal)
 */
class us extends Request {
	public function mapToOrder($params, $orderType) {
		$order = parent::mapToOrder($params, $orderType);
		$order['order']['Domain']['DomainPurpose'] = $params["additionalfields"]["Domain Purpose"];
		return $order;
	}
}
?>
