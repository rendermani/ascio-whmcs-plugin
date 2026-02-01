<?php
namespace ascio\whmcs\ssl;
require("CertificateConfig.php");
use Illuminate\Database\Capsule\Manager as Capsule;
use ascio\ssl\CertificateConfig;

class Sans {
    private $serviceId;
    private $nrOfSans; 
    /**
     * @var Ssl $ssl
     */
    private $ssl;
    /**
     * @var array $data
     */ 
    public $data;  
    /**
     * @var boolean $hasDbData was readDb already executed
     */
    public $hasDbData = false;
    public function __construct(Ssl $ssl)
    {
        $this->ssl = $ssl;
    }
    public function getArray() {
        $out = [];
        foreach($this->data as $key => $san) {
            $out[] = $san["name"];
        }
        return $out;
    }
    public function readDb()
    {
        $result = Capsule::table("mod_asciossl_sans")
        ->where("service_id",$this->ssl->serviceId)
        ->get();
        if(isset($result[0])) {
            $this->data = []; 
            foreach($result as $key => $value) {
                $value->verification_type = $this->ssl->verificationType;
                $value->dns_name = str_replace($this->ssl->fqdn->getFqdn(), $value->name, $this->ssl->dnsName);
                $value->dns_value = $this->ssl->dnsValue;
                $this->data[$key] = (array) $value;
            }    
            $this->hasDbData = true;         
        }     
        return $result;       
    }
    public function writeDb($data = false)
    {
        if(!$data) {
            $data = $this->data;
        }
        if(! ($data && $data[0])) {
           return;
        }
        $result = Capsule::table("mod_asciossl_sans")
        ->where("service_id",$this->ssl->serviceId)
        ->delete();        
        $result = Capsule::table("mod_asciossl_sans")
        ->where("service_id",$this->ssl->serviceId)
        ->insert($data);       
    }
    public function writeApprovalDomains(string $name, $mx_fqdn, $mx_domain) 
    {
        Capsule::table("mod_asciossl_sans")
        ->where("name",$name)
        ->update(["mx_fqdn" => $fqdn,"mx_domain" => $mx_domain]);
    }
    public function fromForm()
    {
        global $_POST;
        if(!isset($_POST["San"])) return;
        $data = [];       
        foreach($_POST["San"] as $key=>$san) {                            
            if(isset($san) && $san !== "") {
                $fqdn = new Fqdn($san);
                $data[$key] = [];
                $data[$key]["name"] = $san;
                $data[$key]["email"] = $_POST["SanEmail"][$key] ? $_POST["SanEmail"][$key] : "admin@".$fqdn->getDomain();
                $data[$key]["service_id"] = $this->ssl->serviceId;
            }
        }
        $this->data = $data;
        $this->hasDbData = true;
        return $data;
    }
    public function toForm()
    {
        return ["sans" => $this->data];
    }
    public function getSansIncluded () : int {
        $config = new CertificateConfig();
        $cert = $config->get($this->ssl->certificateType);
        return $cert->freeSans;        
    }
    public function getApprovalAddresses() {
        $addresses = [];
        foreach($this->data as $key => $san) {
            if($this->ssl->verificationType =="Email") {
                $addresses[] = $san["email"];
            } else {
                $addresses[] = "admin@".$san["name"];
            }
            
        }
        if($addresses[0]) return ",".join($addresses,",");
    }
    public function toHtml() : array {
        $out = "";  
        $paidSans = $this->ssl->params->paidSans;     
        $nrSans = $paidSans + $this->getSansIncluded();
        for($key = 0; $key < $nrSans; $key++) {
            $san = (object) $this->data[$key];
            if($this->ssl->verificationType=="Email" || !isset($this->ssl->verificationType) ) {
                $email = '
                    <div class="form-group">
                        <select value="'.$san->email.'" name="SanEmail['.$key.']" id="SanEmail_'.$key.'" class="form-control san-email" style="display:block" ></select>                    
                        <label class="control-label san-no-email" id="SanLabel_'.$key.'" style="display:none">'.$this->ssl->verificationType.'-Verification</label>
                    </div>';       
            } else {
                $email = '
                    <div class="form-group">
                        <select value="'.$san->email.'" name="SanEmail['.$key.']" id="SanEmail_'.$key.'" class="form-control san-email" style="display:none" ></select>                    
                        <label class="control-label san-no-email" id="SanLabel_'.$key.'" style="display:block">'.$this->ssl->verificationType.'-Verification</label>
                    </div>';
            }
            $out .= '
            <div class="row">
            <div class="col-sm-1 sans sannr"><label class="control-label">'.($key + 1).'</label></div>
                <div class="col-sm-5">            
                    <div class="form-group">
                        <input type="text" data-nr="'.$key.'" value="'.$san->name.'" class="san san-input form-control" name="San['.$key.']" id="San_'.$key.'" ></input>                    
                    </div>               
                </div>
                <div class="col-sm-6 sans">            
                '.$email.'        
            </div>
            </div>
            
            ';
        }
        if($nrSans > 0) {
            $out = '
                <h3>SANs - Additional names</h3>
                <div class="row">
                    <div class="col-sm-1 sanlabel"><label class="control-label ">SAN</label></div>
                    <div class="col-sm-5"><label class="control-label">Name</label></div>
                    <div class="col-sm-6"><label class="control-label">Verification</label></div>
                </div>                
                ' . $out;
            return ["sans" => $out];
        } else return [];

        
    }   

}