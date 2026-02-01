<?php
/**
 * .CA (Canada) TLD Plugin for Ascio v3 API
 *
 * CIRA (Canadian Internet Registration Authority) requirements:
 * - Legal Type mapped to CIRA codes (CCO, CCT, RES, GOV, EDU, etc.)
 * - Trademark fields for trademark registrations
 * - Canadian Citizen flag
 */

namespace ascio;

require_once(dirname(__FILE__) . "/../../../lib/Request.php");

class ca extends Request {

	/**
	 * Legal Type to CIRA code mapping
	 */
	private $legalTypeMap = array(
		"Corporation" => "CCO",
		"Canadian Citizen" => "CCT",
		"Permanent Resident of Canada" => "RES",
		"Government" => "GOV",
		"Canadian Educational Institution" => "EDU",
		"Canadian Unincorporated Association" => "ASS",
		"Canadian Hospital" => "HOP",
		"Partnership Registered in Canada" => "PRT",
		"Trade-mark registered in Canada" => "TDM",
		"Canadian Trade Union" => "TRD",
		"Canadian Political Party" => "PLT",
		"Canadian Library Archive or Museum" => "LAM",
		"Trust established in Canada" => "TRS",
		"Aboriginal Peoples" => "ABO",
		"Legal Representative of a Canadian Citizen" => "LGR",
		"Official mark registered in Canada" => "OMK"
	);

	/**
	 * Map registrant with CA-specific Legal Type codes
	 */
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		$contact["RegistrantType"] = $this->legalTypeMap[$params["additionalfields"]["Legal Type"] ?? ''] ?? null;
		return $contact;
	}

	/**
	 * Map trademark for CA registrations
	 * Includes Canadian Citizen flag, trademark number/name/country
	 */
	protected function mapToTrademark($params) {
		$trademark = array();
		$trademark["Country"] = ($params["additionalfields"]["Canadian Citizen"] ?? null) ? "CA" : null;
		$trademark["Number"] = $params["additionalfields"]["Trademark Number"] ?? null;
		$trademark["Name"] = $params["additionalfields"]["Trademark Name"] ?? null;

		// If trademark name is provided, use trademark country
		if($params["additionalfields"]["Trademark Name"] ?? null) {
			$trademark["Country"] = $params["additionalfields"]["Trademark Country"] ?? null;
		}

		return $trademark;
	}
}
?>
