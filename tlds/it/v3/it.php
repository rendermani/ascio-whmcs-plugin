<?php
/**
 * .IT (Italy) TLD Plugin for Ascio v3 API
 *
 * NIC.IT (Italian Registry) requirements:
 * - Legal Type codes: 1-7 mapping different entity types
 * - Tax ID (Codice Fiscale) required
 * - Birth country stored in Trademark for natural persons
 * - Transfers require NewRegistrant option
 * - No explicit renew - use unexpire after autorenew
 *
 * Also applies to regional variants like .tn.it
 */

namespace ascio;

require_once(dirname(__FILE__) . "/../../../lib/Request.php");

use ascio\Tools as Tools;

class it extends Request {

	/**
	 * Legal Type to NIC.IT code mapping
	 */
	private $legalTypeMap = array(
		"Italian and foreign natural persons" => "1",
		"Companies/one man companies" => "2",
		"Freelance workers/professionals" => "3",
		"non-profit organizations" => "5",
		"public organizations" => "4",
		"other subjects" => "6",
		"non natural foreigners" => "7"
	);

	/**
	 * Transfer domain - IT requires NewRegistrant option
	 */
	public function transferDomain($params = false) {
		$params["options"] = "NewRegistrant";
		return parent::transferDomain($params);
	}

	/**
	 * Map registrant with IT-specific Legal Type and Tax ID
	 */
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);

		// Non-Italian companies use type 7
		if(($params["countrycode"] ?? $params["country"] ?? '') != "IT" && ($contact["OrgName"] ?? null)) {
			$contact["RegistrantType"] = "7";
		} else {
			$contact["RegistrantType"] = $this->legalTypeMap[$params["additionalfields"]["Legal Type"] ?? ''] ?? null;
		}

		$contact["RegistrantNumber"] = $params["additionalfields"]["Tax ID"] ?? null;

		// Natural persons (type 1) shouldn't have OrgName
		if($contact["RegistrantType"] == "1") {
			unset($contact["OrgName"]);
		}

		return $contact;
	}

	/**
	 * Map admin contact for natural persons
	 * Uses registrant data when Legal Type is natural person
	 */
	protected function mapToAdmin($params) {
		$country = $params["country"] ?? null;

		if(($params["additionalfields"]["Legal Type"] ?? '') == "Italian and foreign natural persons") {
			$contact = Array(
				'FirstName' => $params["firstname"] ?? null,
				'LastName' => $params["lastname"] ?? null,
				'Address1' => $params["address1"] ?? null,
				'Address2' => $params["address2"] ?? null,
				'PostalCode' => $params["postcode"] ?? null,
				'City' => $params["city"] ?? null,
				'State' => $params["state"] ?? null,
				'CountryCode' => $params["country"] ?? null,
				'Email' => $params["email"] ?? null,
				'Phone' => Tools::fixPhone($params["fullphonenumber"] ?? null, $country),
				'Fax' => Tools::fixPhone($params["custom"]["Fax"] ?? null, $country),
				'Type' => $this->legalTypeMap[$params["additionalfields"]["Legal Type"] ?? ''] ?? null,
				'OrganisationNumber' => $params["additionalfields"]["Tax ID"] ?? null
			);
			return $contact;
		}

		return parent::mapToAdmin($params);
	}

	/**
	 * Map trademark with birth country for natural persons
	 */
	protected function mapToTrademark($params) {
		if(($params["additionalfields"]["Legal Type"] ?? '') == "Italian and foreign natural persons") {
			$birthCountry = $params["additionalfields"]["Birth country"] ?? null;
			if(!$birthCountry) {
				$birthCountry = $params["countrycode"] ?? $params["country"] ?? null;
			}
			return array("Country" => $birthCountry);
		}
		return null;
	}

	/**
	 * Renew domain - IT doesn't support explicit renew
	 */
	public function renewDomain($params) {
		$domain = parent::searchDomain($params);
		if($this->hasStatus($domain, "expiring")) {
			return parent::unexpireDomain($params);
		}
		return array("error" => "Domain can't be renewed again.");
	}
}
?>
