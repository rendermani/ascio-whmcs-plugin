<?php


class ParameterCapture {   
    public function __construct($params,$method,$orderType)
    {
        $this->setActive(true);
        if(!is_array($params)) {
            $params = array("domainname" => $params);
        }
        $this->params = $params;
        $this->setMethod($method,$orderType);
        $this->domainName = $params["domainname"];
    }
    public function capture () {
        if(!$this->active) return;
        $file = ParameterCapture::getFileName();
        if(!file_put_contents($file,json_encode($this->params,JSON_PRETTY_PRINT))) {
            return $file;
        };
    }
    public function get () {
        $file = ParameterCapture::getFileName();
        $fp = fopen($file,"r");
        $result = fread($fp,filesize($file));
        fclose($fp);
        if(!$result) {            
            throw new Error("Error reading file: ".$file); 
        } 
        $this->params = json_decode($result,true);
        return $this->params;
    }
    public function getFileName() {
        $domainName = $this->params["domainname"];
        $ot = $this->orderType ? "-" .trim($this->orderType) : "";
        return dirname(__FILE__)."/../test/params/".$this->method.$ot."-".$domainName.".json";
    } 
    public function testRequest() {      
        $this->setActive(false);  
        if($this->orderType == "Register_Domain") {
            $this->domainName  = "T".time()."-".$this->domainName;
        }
        
        $this->params["domainname"] = $this->domainName;
        
        $request = createRequest($this->params); 
        try {			
			$ascioParams = $request->mapToOrder($this->params, $this->orderType);
		} catch (AscioException $e) {
			return array("error" => $e->getMessage());
		}	
        return $request->request($this->method,$ascioParams);
    }
    public function setMethod($method,$orderType) {
        $this->method = $method;
        $this->orderType = $orderType;
    }
    public function setActive($active) {
        global $__ParameterCaptureActive; 
        $__ParameterCaptureActive = $active; 
    }
    public function isActive() {
        global $__ParameterCaptureActive; 
        return $__ParameterCaptureActive; 
    }
    public function setTld($tld) {
        $this->params["tld"] = $tld;
        $this->params["domainname"] = $this->params["sld"] .".".$tld;
        $this->domainName = $this->params["domainname"];        
    }
}


?>