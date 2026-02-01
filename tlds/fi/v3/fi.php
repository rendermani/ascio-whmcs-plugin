<?php
/**
 * .FI (Finland) TLD Plugin for Ascio v3 API
 *
 * FICORA/Traficom (Finnish Registry) requirements:
 * - Legal Type codes: 0-7 for different entity types
 * - Identification Number required
 */

namespace ascio\v3\domains;

require_once(dirname(__FILE__) . "/../../../lib/RequestV3.php");

class fi extends RequestV3 {

	/**
	 * Legal Type to FICORA code mapping
	 */
	private $legalTypeMap = array(
		"Individual" => "0",
		"Company" => "1",
		"Association" => "2",
		"Foundation/Institution" => "3",
		"Political party" => "4",
		"Municipality/Township" => "5",
		"State/Government" => "6",
		"Public corporation/community" => "7"
	);

	/**
	 * Map registrant with FI-specific Legal Type and Identification Number
	 */
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		$contact["RegistrantNumber"] = $params["additionalfields"]["Identification Number"] ?? null;
		$contact["RegistrantType"] = $this->legalTypeMap[$params["additionalfields"]["Legal Type"] ?? ''] ?? null;
		return $contact;
	}
}
?>
