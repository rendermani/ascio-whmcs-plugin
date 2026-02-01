<?php
/**
 * .COM.AU (Australia) TLD Plugin for Ascio v3 API
 *
 * auDA (Australian Registry) requirements:
 * - Registrant ID Type and ID required
 * - Eligibility Type as Application Purpose
 * - Trademark fields for eligibility verification
 * - Transfers have 0 year period for 1 year registrations
 *
 * Also applies to: .net.au, .org.au, .edu.au, .gov.au, .id.au
 */

namespace ascio\v3\domains;

require_once(dirname(__FILE__) . "/../../../lib/RequestV3.php");

class com_au extends RequestV3 {

	/**
	 * Eligibility ID Type to code mapping
	 */
	private $eligibilityTypeMap = array(
		"Australian Company Number (ACN)" => "ACN",
		"ACT Business Number" => "ACT BN",
		"NSW Business Number" => "NSW BN",
		"NT Business Number" => "NT BN",
		"QLD Business Number" => "QLD BN",
		"SA Business Number" => "BN",
		"TAS Business Number" => "TAS BN",
		"VIC Business Number" => "VIC BN",
		"WA Business Number" => "WA BN",
		"Trademark (TM)" => "TM",
		"Other - Used to record an Incorporated Association number" => "OTHER",
		"Australian Business Number (ABN)" => "ABN"
	);

	/**
	 * Map registrant with AU-specific ID type and number
	 */
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);

		$idType = $params["additionalfields"]["Registrant ID Type"] ?? null;
		if($idType == "Business Registration Number") {
			$idType = "OTHER";
		}

		$contact["RegistrantType"] = $idType;
		$contact["RegistrantNumber"] = $params["additionalfields"]["Registrant ID"] ?? null;
		return $contact;
	}

	/**
	 * Register domain with AU-specific Eligibility Type
	 */
	public function registerDomain($params = false) {
		$params["Application Purpose"] = $params["additionalfields"]["Eligibility Type"] ?? null;
		return parent::registerDomain($params);
	}

	/**
	 * Transfer domain - AU transfers have special period rules
	 */
	public function transferDomain($params = false) {
		$regperiod = $params["regperiod"] ?? 1;
		if($regperiod == 1) {
			$params["regperiod"] = 0;
		} else if($regperiod > 1) {
			return array("error" => "Invalid RegPeriod. Allowed: 1");
		}
		return parent::transferDomain($params);
	}

	/**
	 * Map trademark with AU-specific eligibility information
	 */
	protected function mapToTrademark($params) {
		$tm = parent::mapToTrademark($params);

		// Include eligibility details if registering with abbreviation/acronym reason
		$eligibilityReason = $params["additionalfields"]["Eligibility Reason"] ?? '';
		if($eligibilityReason == "Domain name is an Exact Match Abbreviation or Acronym of your Entity or Trading Name.") {
			$tm = $tm ?? array();
			$tm["Name"] = $params["additionalfields"]["Eligibility Name"] ?? null;
			$tm["Number"] = $params["additionalfields"]["Eligibility ID"] ?? null;
			$tm["Type"] = $this->eligibilityTypeMap[$params["additionalfields"]["Eligibility ID Type"] ?? ''] ?? null;
		}

		return $tm;
	}
}
?>
