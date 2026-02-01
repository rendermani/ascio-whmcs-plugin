<?php
/**
 * .CO.UK TLD Plugin for Ascio v3 API
 *
 * This is a variant of .UK that uses the same UK plugin logic.
 * Nominet (UK Registry) requirements apply.
 *
 * Note: The Request::create() factory method will fall back to uk/v3/uk.php
 * for co.uk domains if this file doesn't exist. This file is provided for
 * explicit handling if needed.
 */

namespace ascio;

require_once(dirname(__FILE__) . "/../../../lib/Request.php");

class co_uk extends Request {

	/**
	 * Legal Type to Nominet code mapping
	 */
	private $legalTypeMap = array(
		"Individual" => "IND",
		"UK Limited Company" => "LTD",
		"UK Public Limited Company" => "PLC",
		"UK Partnership" => "PTNR",
		"UK Limited Liability Partnership" => "LLP",
		"Sole Trader" => "STRA",
		"UK Registered Charity" => "RCHAR",
		"UK Entity (other)" => "OTHER",
		"Foreign Organization" => "FCORP",
		"Other foreign organizations" => "FOTHER"
	);

	/**
	 * Map registrant with UK-specific Legal Type and Company ID
	 */
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		$isCompany = !empty($contact["OrgName"]);

		$contact["RegistrantType"] = $this->legalTypeMap[$params["additionalfields"]["Legal Type"] ?? ''] ?? null;
		$contact["RegistrantNumber"] = $params["additionalfields"]["Company ID Number"] ?? null;

		// Foreign individual (non-UK, no company) uses FIND
		if(($contact["CountryCode"] != "GB") && !$isCompany) {
			$contact["RegistrantType"] = "FIND";
		}

		// Individual registrants shouldn't have OrgName
		if($contact["RegistrantType"] == "IND") {
			$contact["OrgName"] = null;
		}

		return $contact;
	}

	/**
	 * Transfer domain - UK transfers have 0 year period
	 */
	public function transferDomain($params = false) {
		$params["regperiod"] = 0;
		return parent::transferDomain($params);
	}
}
?>
