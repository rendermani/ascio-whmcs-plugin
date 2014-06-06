<?php

class de extends Request {	
	public function transferDomain($params) {
		$params["regperiod"] = 0 ;
		parent::transferDomain($params);
	}
}
?>