<?php

namespace ascio;

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
			$order["Order"]["Domain"]["DomainPurpose"] = $params["additionalfields"]["Domain Purpose"];
		}
		
		return $order;
	}
}
?>
