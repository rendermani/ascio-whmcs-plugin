<?php

namespace ascio\v2\domains;

/**
 * .nyc TLD Plugin
 * 
 * Required fields:
 * - Domain.Purpose (from additionalfields["Domain Purpose"])
 */
class nyc extends Request {
	public function mapToOrder($params, $orderType) {
		$order = parent::mapToOrder($params, $orderType);
		
		if (isset($params["additionalfields"]["Domain Purpose"])) {
			$order["order"]["Domain"]["DomainPurpose"] = $params["additionalfields"]["Domain Purpose"];
		}
		
		return $order;
	}
}
?>
