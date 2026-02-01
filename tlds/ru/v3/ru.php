<?php
/**
 * .RU (Russia) TLD Plugin for Ascio v3 API
 *
 * RIPN/TCI (Russian Registry) requirements:
 * - Registrant Type: ORG (organization) or PRS (person)
 * - Organizations: Taxpayer Number 1 and Territory-Linked Taxpayer Number 2
 * - Individuals: Passport info (number, issue date, issuer) and birthday
 * - NIC/D handle option
 */

namespace ascio\v3\domains;

require_once(dirname(__FILE__) . "/../../../lib/RequestV3.php");

use ascio\Tools as Tools;

class ru extends RequestV3 {

	/**
	 * Map registrant with RU-specific identity requirements
	 */
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);

		if(($params["additionalfields"]["Registrant Type"] ?? null) == "ORG") {
			// Organization registrant
			$contact["RegistrantNumber"] = $params["additionalfields"]["Russian Organizations Taxpayer Number 1"] ?? null;
			$contact["VatNumber"] = $params["additionalfields"]["Russian Organizations Territory-Linked Taxpayer Number 2"] ?? null;
			$contact["RegistrantType"] = "ORG";
		} else {
			// Individual registrant (PRS)
			// Passport info: number, issue date, issuer
			$passportNumber = $params["additionalfields"]["Individuals Passport Number"] ?? '';
			$passportDate = $params["additionalfields"]["Individuals Passport Issue Date"] ?? '';
			$passportIssuer = $params["additionalfields"]["Individuals Passport Issuer"] ?? '';
			$contact["RegistrantNumber"] = trim("$passportNumber, $passportDate, $passportIssuer", ", ");

			$contact["RegistrantDate"] = $params["additionalfields"]["Individuals Birthday"] ?? null;
			$contact["RegistrantType"] = "PRS";

			// For individuals, OrgName should be the person's name
			$contact["OrgName"] = $contact["Name"];
		}

		$contact["Options"] = $params["additionalfields"]["NIC/D handle"] ?? null;
		return $contact;
	}

	/**
	 * Map admin contact for RU registrations
	 * Uses registrant info for organization name
	 */
	protected function mapToAdmin($params) {
		$country = $params["country"] ?? null;

		// Determine company name based on registrant type
		if(($params["additionalfields"]["Registrant Type"] ?? null) == "ORG") {
			$company = $params["companyname"] ?? null;
		} else {
			$company = trim(($params["firstname"] ?? '') . " " . ($params["lastname"] ?? ''));
		}

		$contact = Array(
			'FirstName' => $params["firstname"] ?? null,
			'LastName' => $params["lastname"] ?? null,
			'OrgName' => $company,
			'Address1' => $params["address1"] ?? null,
			'Address2' => $params["address2"] ?? null,
			'PostalCode' => $params["postcode"] ?? null,
			'City' => $params["city"] ?? null,
			'State' => $params["state"] ?? null,
			'CountryCode' => $params["country"] ?? null,
			'Email' => $params["email"] ?? null,
			'Phone' => Tools::fixPhone($params["fullphonenumber"] ?? null, $country),
			'Fax' => Tools::fixPhone($params["custom"]["Fax"] ?? null, $country),
		);

		return $contact;
	}
}
?>
