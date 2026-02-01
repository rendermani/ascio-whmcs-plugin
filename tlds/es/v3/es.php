<?php
/**
 * .ES (Spain) TLD Plugin for Ascio v3 API
 *
 * Red.es (Spanish Registry) requirements:
 * - RegistrantType codes: 1, 612, 877 for different entity types
 * - ID Form Number required
 * - Contact Type based on country
 * - Nameservers require IP addresses
 * - Transfers have 0 year period
 */

namespace ascio\v3\domains;

require_once(dirname(__FILE__) . "/../../../lib/RequestV3.php");

use ascio\Tools as Tools;

class es extends RequestV3 {

	/**
	 * Map registrant with ES-specific type codes and ID
	 */
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);

		// Determine registrant type based on country and company
		if(($params["country"] ?? null) == "ES" && ($params["companyname"] ?? null)) {
			// Company in Spain
			$contact["RegistrantType"] = 612;
		} elseif($params["companyname"] ?? null) {
			// Company outside Spain
			$contact["RegistrantType"] = 1;
		} elseif(($params["country"] ?? null) == "ES") {
			// Individual in Spain
			$contact["RegistrantType"] = 1;
		} else {
			// Individual outside Spain
			$contact["RegistrantType"] = 877;
		}

		$contact["RegistrantNumber"] = $params["additionalfields"]["ID Form Number"] ?? null;
		return $contact;
	}

	/**
	 * Map admin contact with ES-specific type and ID
	 */
	protected function mapToAdmin($params) {
		$contact = parent::mapToAdmin($params);
		if(($params["country"] ?? null) == "ES") {
			$contact["Type"] = 1;
		} else {
			$contact["Type"] = 0;
		}
		$contact["OrgName"] = null;
		$contact["OrganisationNumber"] = $params["additionalfields"]["ID Form Number"] ?? null;
		return $contact;
	}

	/**
	 * Map tech contact with ES-specific ID
	 */
	protected function mapToTech($params) {
		$contact = parent::mapToTech($params);
		$contact["OrganisationNumber"] = $params["additionalfields"]["ID Form Number"] ?? null;
		return $contact;
	}

	/**
	 * Map billing contact with ES-specific ID
	 */
	protected function mapToBilling($params) {
		$contact = parent::mapToBilling($params);
		$contact["OrganisationNumber"] = $params["additionalfields"]["ID Form Number"] ?? null;
		return $contact;
	}

	/**
	 * Transfer domain - ES transfers have 0 year period
	 */
	public function transferDomain($params = false) {
		$params["regperiod"] = 0;
		return parent::transferDomain($params);
	}

	/**
	 * Map nameservers with IP addresses (ES registry requirement)
	 */
	public function mapToNameservers($params) {
		return array(
			'NameServer1' => Array(
				'HostName' => $params["ns1"] ?? null,
				'IpAddress' => ($params["ns1"] ?? null) ? gethostbyname($params["ns1"]) : null
			),
			'NameServer2' => Array(
				'HostName' => $params["ns2"] ?? null,
				'IpAddress' => ($params["ns2"] ?? null) ? gethostbyname($params["ns2"]) : null
			),
			'NameServer3' => Array(
				'HostName' => $params["ns3"] ?? null,
				'IpAddress' => ($params["ns3"] ?? null) ? gethostbyname($params["ns3"]) : null
			),
			'NameServer4' => Array(
				'HostName' => $params["ns4"] ?? null,
				'IpAddress' => ($params["ns4"] ?? null) ? gethostbyname($params["ns4"]) : null
			)
		);
	}
}
?>
