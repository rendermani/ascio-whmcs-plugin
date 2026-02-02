<?php
/**
 * .DK (Denmark) TLD Plugin for Ascio v3 API
 *
 * DK-Hostmaster (Danish Registry) requirements:
 * - Registrant CVR number required
 * - RegistrantType: V (company) or P (person)
 * - DK-Hostmaster-ID in Details field
 * - Renewals handled through DK-Hostmaster directly
 * - Expire uses "Unconfirmed" comment
 */

namespace ascio;

require_once(dirname(__FILE__) . "/../../../lib/Request.php");

use ascio\AscioException as AscioException;

class dk extends Request {

	/**
	 * Map registrant with DK-specific CVR and type
	 */
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		$contact["RegistrantNumber"] = $params["additionalfields"]["Registrant CVR nr."] ?? null;

		// V = company (Virksomhed), P = person (Privatperson)
		if($contact["OrgName"] ?? null) {
			$contact["RegistrantType"] = "V";
		} else {
			$contact["RegistrantType"] = "P";
		}

		$contact["Details"] = $params["additionalfields"]["Registrant DK-Hostmaster-ID"] ?? null;
		return $contact;
	}

	/**
	 * Map admin contact with DK-specific CVR and type
	 */
	protected function mapToAdmin($params) {
		$contact = parent::mapToAdmin($params);
		$contact["OrganisationNumber"] = $params["additionalfields"]["Administrator CVR nr."] ?? null;

		// V = company, P = person
		if($contact["OrgName"] ?? null) {
			$contact["Type"] = "V";
		} else {
			$contact["Type"] = "P";
		}

		return $contact;
	}

	/**
	 * Renew domain - DK renewals are handled by DK-Hostmaster
	 */
	public function renewDomain($params) {
		return array("error" => "This TLD is renewed through DK-Hostmaster. Please contact the support for further questions.");
	}

	/**
	 * Expire domain with DK-specific "Unconfirmed" comment
	 */
	public function expireDomain($params) {
		$params = $this->setParams($params);
		try {
			$ascioParams = parent::mapToOrder($params, "Expire");
			$ascioParams["Order"]["Comments"] = "Unconfirmed";
		} catch (AscioException $e) {
			return array("error" => $e->getMessage());
		}
		return $this->sendRequest("CreateOrder", $ascioParams);
	}
}
?>
