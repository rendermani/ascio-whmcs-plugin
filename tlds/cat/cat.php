<?php

namespace ascio;

/**
 * .cat TLD Plugin (Catalan language and culture)
 *
 * Required fields:
 * - Domain.Purpose (additionalfields["Domain Purpose"])
 * - Domain.AuthInfo (additionalfields["Auth Code"])
 * - Registrant.Details (additionalfields["Registrant Details"])
 * - TM.Name (additionalfields["Trademark Name"])
 */
class cat extends Request {
	public function mapToOrder($params, $orderType) {
		$order = parent::mapToOrder($params, $orderType);

		if (isset($params["additionalfields"]["Domain Purpose"])) {
			$order["Order"]["Domain"]["DomainPurpose"] = $params["additionalfields"]["Domain Purpose"];
		}

		if (isset($params["additionalfields"]["Auth Code"])) {
			$order["Order"]["Domain"]["AuthInfo"] = $params["additionalfields"]["Auth Code"];
		}

		return $order;
	}

	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);

		if (isset($params["additionalfields"]["Registrant Details"])) {
			$contact["Details"] = $params["additionalfields"]["Registrant Details"];
		}

		return $contact;
	}

	protected function mapToTrademark($params) {
		if (isset($params["additionalfields"]["Trademark Name"]) && $params["additionalfields"]["Trademark Name"]) {
			return ["Name" => $params["additionalfields"]["Trademark Name"]];
		}
		return null;
	}
}
?>
