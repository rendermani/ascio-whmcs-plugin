<?php
namespace ascio\whmcs\ssl;
require_once("Error.php");


use Illuminate\Database\Capsule\Manager as Capsule;
class Fqdn  {
    protected $name;
    protected $nrTldParts;
    public $tokens;
    public $tld;
    public $isWildCard = false;
    function __construct($name) 
    {
        if(strpos($name,"*.") !== false) {
            $name = str_replace("*.","",$name);
            $this->isWildCard = true; 
        }
        $this->name = $name;
        $this->getTld(); 
    }
    private function getTld() {    
        $this->tokens = explode(".",$this->name);

        $count = count($this->tokens);
        if($count == 0) throw new AscioSystemError("Invalid domain name: ".$this->name,500,null,$this->name);
        if($count > 2) {            
            $longTld = $this->tokens[$count-2] .".".end($this->tokens);
            $result = Capsule::Table("tblasciotlds")
                ->where("Tld",$longTld)
                ->value("Tld");
                       
            if(isset($result[0])) {
                $this->nrTldParts = 2;
                $this->tld = $longTld;                
            } else { 
                $this->nrTldParts = 1;           
                $this->tld = end($this->tokens);
            }
        }
        $this->tld = end($this->tokens);
        return $this->tld;
    }
    public function getDomain () {
        if(count($this->tokens)==2) return $this->name;
        $start = count($this->tokens) - $this->nrTldParts - 1;
        $end =  $this->nrTldParts + 1;        
        return join(".",array_slice($this->tokens,$start,3));
    }
    public function getFqdn() {
        return $this->name;
    }
    public function getCommonName() {
        $wildcard = $this->isWildCard ? "*." : "";
        return $wildcard . $this->getFqdn();
    }
    public function getSslAuth() {
        $prefix = $this->isWildCard ? "" : "_dnsauth." ;
        return $prefix .$this->getFqdn();
    }

}