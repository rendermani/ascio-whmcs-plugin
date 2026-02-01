<?php
/**
 * .NU (Niue) TLD Plugin for Ascio v3 API
 *
 * IIS.NU (managed by Swedish IIS) requirements:
 * - Identification Number and VAT for registrant
 * - Organisation Number for admin/tech/billing contacts
 * - Can use default registrar admin or client details
 * - Transfers have 0 year period
 */

namespace ascio;

require_once(dirname(__FILE__) . "/../../../lib/Request.php");

use Illuminate\Database\Capsule\Manager as Capsule;

class nu extends Request {

	/**
	 * Map registrant with NU-specific identification and VAT
	 */
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		$contact["RegistrantNumber"] = $params["additionalfields"]["Identification Number"] ?? null;
		$contact["VatNumber"] = $params["additionalfields"]["VAT"] ?? null;
		return $contact;
	}

	/**
	 * Map admin contact with NU-specific Organisation Number
	 * Uses registrar default or client details based on WHMCS config
	 */
	protected function mapToAdmin($params) {
		$contact = parent::mapToAdmin($params);

		// Check if using default registrar admin contact
		$isDefaultContactResult = Capsule::table('tblconfiguration')
			->where('setting', '=', 'RegistrarAdminUseClientDetails')
			->first();

		if($isDefaultContactResult && $isDefaultContactResult->value == "on") {
			// Use client's identification number
			$contact["OrganisationNumber"] = $params["additionalfields"]["Identification Number"] ?? null;
		} else {
			// Use registrar admin organisation number from config
			$registrarAdminNumber = Capsule::table('tblconfiguration')
				->where('setting', '=', 'RegistrarAdminOrganizationNumber')
				->first();
			$contact["OrganisationNumber"] = $registrarAdminNumber->value ?? null;
		}

		return $contact;
	}

	/**
	 * Map billing contact - uses same logic as admin
	 */
	protected function mapToBilling($params) {
		return $this->mapToAdmin($params);
	}

	/**
	 * Map tech contact - uses same logic as admin
	 */
	protected function mapToTech($params) {
		return $this->mapToAdmin($params);
	}

	/**
	 * Transfer domain - NU transfers have 0 year period
	 */
	public function transferDomain($params = false) {
		$params["regperiod"] = 0;
		return parent::transferDomain($params);
	}
}
?>
