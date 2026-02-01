<?php
/**
 * .ASIA TLD Plugin for Ascio v3 API
 *
 * DotAsia requirements:
 * - Registrant must be in Asia-Pacific region or use LocalPresence
 * - Legal Type, Identity Form, and Identity Number required
 * - DomainPurpose set to "Admin" for registrations
 * - LocalPresenceAdmin option for non-Asian registrants
 */

namespace ascio\v3\domains;

require_once(dirname(__FILE__) . "/../../../lib/RequestV3.php");

class asia extends RequestV3 {

	/**
	 * List of Asia-Pacific country codes
	 */
	private $asianCountries = array(
		"AE","AF","AM","AQ","AU","AZ","BD","BH","BN","BT","CC","CK","CN","CV","CX","CY","FJ","FM","GE","GU","HK",
		"HM","ID","IL","IN","IQ","IR","JO","JP","KG","KH","KI","KP","KR","KW","KZ","LA","LB","LK","MH","MM","MN",
		"MO","MV","MY","NF","NP","NR","NU","NZ","OM","PG","PH","PK","PS","PW","QA","SA","SB","SG","SY","TH","TJ",
		"TK","TL","TM","TO","TR","TV","TW","UZ","VN","VU","WS","YE"
	);

	/**
	 * Register domain with ASIA-specific options
	 * Non-Asian registrants require LocalPresence
	 */
	public function registerDomain($params = false) {
		$params = $this->setParams($params);
		$ascioParams = $this->mapToOrder($params, "Register_Domain");

		// Check if registrant is in Asia-Pacific region
		$registrantCountry = $ascioParams["Order"]["Domain"]["Registrant"]["CountryCode"] ?? null;
		if(!in_array($registrantCountry, $this->asianCountries)) {
			// Non-Asian registrant requires local presence service
			$ascioParams["Order"]["LocalPresence"] = "LocalPresenceAdmin";
		}

		// ASIA requires DomainPurpose
		$ascioParams["Order"]["Domain"]["DomainPurpose"] = "Admin";

		$result = $this->sendRequest("CreateOrder", $ascioParams);
		return $result;
	}

	/**
	 * Map registrant with ASIA-specific identity fields
	 */
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		$contact["RegistrantType"] = $params["additionalfields"]["Legal Type"] ?? null;
		$contact["Details"] = $params["additionalfields"]["Identity Form"] ?? null;
		$contact["RegistrantNumber"] = $params["additionalfields"]["Identity Number"] ?? null;
		return $contact;
	}
}
?>
