<?php
/**
 * .NL (Netherlands) TLD Plugin for Ascio v3 API
 *
 * SIDN (Dutch Registry) requirements:
 * - RegistrantType: PERSOON (individual), BV (Dutch company), BGG (foreign/other)
 * - Organisation Number required for companies
 * - Contact Type set to BGG for admin/tech/billing
 */

namespace ascio;

require_once(dirname(__FILE__) . "/../../../lib/Request.php");

use ascio\AscioException as AscioException;

class nl extends Request {

	/**
	 * Map registrant with NL-specific type handling
	 */
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		$contact["RegistrantNumber"] = $params["additionalfields"]["Organisation Number"] ?? null;

		// Determine registrant type based on company and country
		if(($contact["OrgName"] ?? null) && $contact["CountryCode"] == "NL") {
			// Dutch company
			$contact["RegistrantType"] = "BV";
		} else if($contact["OrgName"] ?? null) {
			// Foreign company
			$contact["RegistrantType"] = "BGG";
		} else {
			// Individual
			$contact["RegistrantType"] = "PERSOON";
		}

		// Organisation Number required for companies
		if(($contact["OrgName"] ?? null) && !($contact["RegistrantNumber"] ?? null)) {
			throw new AscioException("Please enter a valid Organization Number");
		}

		return $contact;
	}

	/**
	 * Map admin contact - NL uses BGG type for contacts
	 */
	protected function mapToAdmin($params) {
		$contact = parent::mapToAdmin($params);
		$contact["Type"] = "BGG";
		return $contact;
	}

	/**
	 * Map tech contact - NL uses BGG type for contacts
	 */
	protected function mapToTech($params) {
		$contact = parent::mapToTech($params);
		$contact["Type"] = "BGG";
		return $contact;
	}

	/**
	 * Map billing contact - NL uses BGG type for contacts
	 */
	protected function mapToBilling($params) {
		$contact = parent::mapToBilling($params);
		$contact["Type"] = "BGG";
		return $contact;
	}
}
?>
