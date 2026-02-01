<?php
/**
 * .FR (France) TLD Plugin for Ascio v3 API
 *
 * AFNIC (French Registry) requirements:
 * - Companies: RegistrantType = "company", RegistrantNumber = VAT number
 * - Individuals: RegistrantType = "Individual", birth info in Trademark fields
 * - Trademark stores birth city/country/date/postal code for individuals
 *
 * Also applies to French territories: .pm, .re, .tf, .wf, .yt
 */

namespace ascio\v3\domains;

require_once(dirname(__FILE__) . "/../../../lib/RequestV3.php");

class fr extends RequestV3 {

	/**
	 * Map trademark for individual registrants
	 * AFNIC uses trademark fields to store birth information for individuals
	 */
	protected function mapToTrademark($params) {
		// Registrant is not a company - store birth info in trademark
		if(!($params["companyname"] ?? null)) {
			$tm = array();
			$tm["Name"] = $params["City of birth (Individual)"] ?? null;
			$tm["Country"] = $params["Country of birth (Individual)"] ?? null;
			$tm["Date"] = $params["Date of birth (Individual)"] ?? null;
			$tm["Number"] = $params["Postal code of city of birth (Individual)"] ?? null;
			return $tm;
		}
		return null;
	}

	/**
	 * Map registrant with FR-specific type handling
	 */
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);

		// Registrant is a company
		if($params["companyname"] ?? null) {
			$contact["RegistrantType"] = "company";
			$contact["RegistrantNumber"] = $params["additionalfields"]["VAT (Company)"] ?? null;
		} else {
			// Individual registrant
			$contact["RegistrantType"] = "Individual";
		}

		return $contact;
	}
}
?>
