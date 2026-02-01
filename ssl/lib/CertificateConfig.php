<?php
namespace ascio\ssl;
require_once("Error.php");
use ascio\whmcs\ssl;
use ascio\whmcs\ssl\AscioException;

class CertificateConfig {
    private $defs;
    private $names;

    public function __construct()
    {
        $contents = file_get_contents(__DIR__."/../config/cert-def.json");
        foreach(json_decode($contents) as $key => $cert) {
            $this->defs[$key] = new CertConfig($cert);
        };
        foreach($this->defs as $id => $def) {
            $this->names[$def->name] = $id;
        }
    }
    public function get($idOrName) : CertConfig {
        if(isset($this->defs[$idOrName])) {
            return $this->defs[$idOrName];
        } else {
            if(isset($this->names[$idOrName])) {
                $id = $this->names[$idOrName]; 
                return $this->defs[$id];
            }
        }
        throw new AscioException("Certificate ".$idOrName . " not found",404);
    }
}
class CertConfig  {
    public $currencyId;
    public $currency; 
     /**
     * string $id The short name of the certificate
     */
    public $id;
    /**
     *  string $certificateId The original name of the certificate if SAN
     */
    public $certificateId; 

    /**
     * int $productId The ID of the product group
     */
    public $productId; 
    /**
     * int $productGroupId The ID of the product group
     */
    public $productGroupId;
     /**
     * int $productConfigOptionsId The SAN Definition from DB
     */
    public $productConfigOptionsId;
    /**
     * int $productConfigOptionsSubId The Option of the SAN. Always 1 (Quantity)
     */
    public $productConfigOptionsSubId;
    /**
     * int $productConfigGid Every SAN needs a group that is assigned to the Product
     */
    public $productConfigGid;
    /**
     * Prices $prices
     */
    private $prices;
    public $method; 
    /**
     * bool $san Is this a san product?
     */
    public $san;
    public $vendor;
    public $type;
    public $wildcard;
    public $multiDomain;
    /**
     * int $maxSans
     */
    public $maxSans=0;
     /**
     * int $freeSans
     */
    public $freeSans=0;
    /**
     * string $name Long name
     */
    public $name;

    public function __construct ($params) {
        $this->prices = new Prices();
        foreach($params as $key => $value ) {
            $this->$key = $value; 
        }
    }
    public function getDescription() {
        $description  = $this->getTypeDescription()."<br>"; 
        $description .= $this->multiDomain ? "Multi-Domain<br>" : ""; 
        $description .= $this->maxSans ? "Up to ".$this->maxSans ." SANs<br>" : ""; 
        $description .= $this->freeSans ? $this->freeSans ." SANs included<br>" : ""; 
        $description .= $this->wildcard ? "Wildcard<br>" : ""; 
        return $description;
    }
    public function getTypeDescription() {
        switch ($this->type) {
            case "DV" : $type = "Domain Verification"; break;
            case "OV" : $type = "Organisation Verification"; break;
            case "EV" : $type = "Extended Verification"; break;
        }
        return $type;
    }
    public function addProductGroup($type,$id) {
        $this->productGroups[$type] = $id;
    }
    public function getProductGroup($type) {
        return $this->productGroups[$type];
    }
    public function addPrice($regPeriod,$price) {
        $this->prices->add($regPeriod,$price);
    }
    public function getPrices() :  Prices {
        return $this->prices;
    }
    function __clone (){
        $this->prices = new Prices();
    }
}
class Prices {
    public $annually;
    public $biennially;
    public $triennially;
    public $annuallyOld;
    public $bienniallyOld;
    public $trienniallyOld;

    public function add( $regPeriod, $price) {
        switch ($regPeriod) {
            case 1 : $period = "annually"; break;
            case 2 : $period = "biennially"; break;
            case 3 : $period = "triennially"; break;
        }
        $old = $period."Old";
        $this->$period = $price;
        $this->$old = $price;        
    }
    public function get(): array {
        $prices =  [
            'monthly' => -1, 
            'quarterly' => -1, 
            'semiannually' => -1, 
            'annually' => $this->annually,
            'biennially' => $this->biennially,
            'triennially' => $this->triennially,           
        ];
        foreach($prices as $key => $period) {
            if($period == 0) {
                $prices[$key] =  -1;
            }
        }
        return $prices;
    }
    public function calculate($margin,$round) {
        if(!$round) {
            $round = 1; 
        }
        if(!$margin) {
            $margin = 0; 
        }
        $this->annually = $this->calculatePrice($this->annuallyOld,$margin,$round);
        $this->biennially = $this->calculatePrice($this->bienniallyOld,$margin,$round);
        $this->triennially = $this->calculatePrice($this->trienniallyOld,$margin,$round);
    }
    private function calculatePrice($price,$margin,$round) {
        if($price == -1) {
            return -1; 
        }
        return ceil (($price + ($price * $margin / 100))/$round)*$round;
    }
}