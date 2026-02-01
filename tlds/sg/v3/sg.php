<?php
/**
 * .SG (Singapore) TLD Plugin for Ascio v3 API
 *
 * SGNIC (Singapore Registry) requirements:
 * - Local Presence option for non-Singapore registrants
 * - Registrant ID required
 * - Admin ID for admin contact
 *
 * Also applies to: .com.sg, .org.sg, .edu.sg, .net.sg, .gov.sg
 */

namespace ascio;

require_once(dirname(__FILE__) . "/../../../lib/Request.php");

class sg extends Request {

	/**
	 * Map order with SG-specific Local Presence option
	 */
	public function mapToOrder($params, $orderType) {
		$ascioParams = parent::mapToOrder($params, $orderType);

		// Add Local Presence option if requested
		if(($params["additionalfields"]["Local Presence"] ?? null) == "on") {
			$ascioParams["Order"]["LocalPresence"] = "LocalPresenceAdmin";
		}

		return $ascioParams;
	}

	/**
	 * Map registrant with SG-specific Registrant ID
	 */
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		$contact["RegistrantNumber"] = $params["additionalfields"]["Registrant ID"] ?? null;
		return $contact;
	}

	/**
	 * Map admin contact with SG-specific Admin ID
	 */
	protected function mapToAdmin($params) {
		$contact = parent::mapToAdmin($params);
		$contact["OrganisationNumber"] = $params["additionalfields"]["Admin ID"] ?? null;
		return $contact;
	}
}
?>
