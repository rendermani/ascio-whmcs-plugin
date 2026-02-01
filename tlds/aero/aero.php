<?php

namespace ascio;

/**
 * .aero TLD plugin
 *
 * Requires: Domain.AuthInfo (authorization code from additionalfields)
 */
class aero extends Request {
	public function mapToOrder($params, $orderType) {
		$order = parent::mapToOrder($params, $orderType);

		// Set AuthInfo from additional fields if provided
		if (isset($params["additionalfields"]["Auth Code"]) && !empty($params["additionalfields"]["Auth Code"])) {
			$order['Order']['Domain']['AuthInfo'] = $params["additionalfields"]["Auth Code"];
		}

		return $order;
	}
}
?>
