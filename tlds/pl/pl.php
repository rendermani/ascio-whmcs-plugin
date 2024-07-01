<?php

namespace ascio\v2\domains;

class pl extends Request {	
	public function transferDomain($params=false) {
		$params["regperiod"] = 0 ; 
		parent::transferDomain($params);
	}
}
?>