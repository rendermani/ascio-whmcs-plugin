<?php
use Illuminate\Database\Capsule\Manager as Capsule;
class nu extends Request {	
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		$contact["RegistrantNumber"] = $params["additionalfields"]["Identification Number"];
		$contact["VatNumber"] = $params["additionalfields"]["VAT"];
		return $contact;
	}
	protected function mapToAdmin($params) {
		$contact = parent::mapToAdmin($params);
		$isDefaulContactResult =  Capsule::table('tblconfiguration')
			->where('setting', '=', 'RegistrarAdminUseClientDetails')
			->get();
		if($isDefaulContact[0]->value=="on") {
			$contact["OrganisationNumber"] = $params["additionalfields"]["Identification Number"];			
		} else {
			$registrarAdminNumber =  Capsule::table('tblconfiguration')
			->where('setting', '=', 'RegistrarAdminOrganizationNumber')
			->get();
			$contact["OrganisationNumber"] = $registrarAdminNumber[0]->value;
		}
		return $contact; 
	}
	protected function mapToBilling($params) {
		return $this->mapToAdmin($params);
	}
		protected function mapToTech($params) {
		return $this->mapToAdmin($params);
	}
}
?>