<?php
class ca extends Request {	
	public function mapToOrder($params,$orderType) {
		$ascioParams =parent::mapToOrder($params,$orderType);
		$regCompany = $ascioParams["order"]["Domain"]["Registrant"]["OrgName"];
		$adminCompany = $ascioParams["order"]["Domain"]["AdminContact"]["OrgName"];;
		if($regCompany != $adminCompany ) {
			throw new AscioException('Owner company and Admin company must be the same: '.$regCompany." is not ".$adminCompany);
		}
		return $ascioParams;

	}
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		$map = array(
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
		$contact["RegistrantType"] = $map[$params["additionalfields"]["Legal Type"]];
		return $contact;
	}	
	protected function mapToTrademark($params) {
		$trademark = array();
		$trademark["Country"] = $params["additionalfields"]["Canadian ID number"];
		$trademark["Number"] = $params["additionalfields"]["Canadian ID number"];
		return $trademark;
	}	
}
?>