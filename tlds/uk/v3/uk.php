<?php
/**
 * .UK (United Kingdom) TLD Plugin for Ascio v3 API
 *
 * Nominet (UK Registry) requirements:
 * - RegistrantType mapping from Legal Type to Nominet codes (IND, LTD, PLC, etc.)
 * - Company ID Number for non-individual registrants
 * - Foreign individuals use FIND code
 * - Transfers have 0 year registration period
 *
 * Also applies to: .co.uk, .org.uk, .ac.uk, .gov.uk, .me.uk, .net.uk, .sch.uk
 */

namespace ascio;

require_once(dirname(__FILE__) . "/../../../lib/Request.php");

class uk extends Request {

	/**
	 * Map Legal Type to Nominet registrant type codes
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
	 * Map registrant with UK-specific Legal Type and Company ID handling
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
