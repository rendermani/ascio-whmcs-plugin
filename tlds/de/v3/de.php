<?php
/**
 * .DE (Germany) TLD Plugin for Ascio v3 API
 *
 * German domain requirements:
 * - Registrant RegistrantNumber (Tax ID) is optional but recommended for companies
 * - Fax is required for Admin/Tech contacts (falls back to Phone if not provided)
 * - Domains don't support explicit renew - use unexpire after autorenew
 * - EPP code generation uses specific character set
 */

namespace ascio\v3\domains;

require_once(dirname(__FILE__) . "/../../../lib/RequestV3.php");

use ascio\Tools as Tools;

class de extends RequestV3 {

	/**
	 * Transfer domain - DE transfers work same as v2
	 */
	public function transferDomain($params = false) {
		return parent::transferDomain($params);
	}

	/**
	 * Update EPP code with DE-specific character requirements
	 * DE registry requires specific character set for auth codes
	 */
	public function updateEPPCode($params) {
		$characters = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789+-/*";
		$params["eppcode"] = Tools::generateEppCode(12, $characters);
		parent::updateEPPCode($params);
		return $params;
	}

	/**
	 * Map registrant with DE-specific Tax ID handling
	 */
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		$regNr1 = $params["additionalfields"]["Tax ID"] ?? null;
		$regNr2 = $contact["custom"]["RegistrantNumber"] ?? null;
		$contact["RegistrantNumber"] = $regNr1 ?: $regNr2;
		return $contact;
	}

	/**
	 * Map admin contact - DE requires Fax (use Phone as fallback)
	 */
	protected function mapToAdmin($params) {
		$contact = parent::mapToAdmin($params);
		if(!($contact["custom"]["Fax"] ?? null)) {
			$contact["Fax"] = $contact["Phone"];
		}
		return $contact;
	}

	/**
	 * Map tech contact - DE requires Fax (use Phone as fallback)
	 */
	protected function mapToTech($params) {
		$contact = parent::mapToTech($params);
		if(!($contact["custom"]["Fax"] ?? null)) {
			$contact["Fax"] = $contact["Phone"];
		}
		return $contact;
	}

	/**
	 * Renew domain - DE doesn't support explicit renew
	 * Use unexpire for domains in expiring state
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
