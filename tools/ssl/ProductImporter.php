<?php
namespace ascio\whmcs\ssl;

// Load from monorepo structure - SSL module is sibling to tools
require_once(__DIR__ . "/../../ssl/lib/CertificateConfig.php");
require_once(__DIR__ . "/../lib/Error.php");
use ascio\ssl as ssl; 
use ascio\whmcs\ssl\AscioException;
use ascio\ssl\CertConfig;
use Illuminate\Database\Capsule\Manager as Capsule;
use ascio\ssl\CertificateConfig;

class ProductImporter {
    /**
     * @var ssl\CertificateConfig $config
     */
    private $config; 
    private $data; 
    private $margin;
    private $roundstep; 
    private $productIds; 
    private $currencyId;
    private $currency;
    public function __construct()
    {
        $this->config = new ssl\CertificateConfig();        
    }
    public function readCSV($file) {        
        $contents = file_get_contents($file); 
        if(!isset($contents)) {
            throw new AscioException("File not found: ".$file,404);
            
        }
        return $this->parseCSV($contents);
    }
    public function parseCSV($contents,$method="Register") {
        $contents = str_replace('"','',$contents);
        $lines = explode("\n",$contents);
        foreach($lines as $nr => $line) {
            if($line=="" || $nr ==0) continue;  
            if(!strpos($line,"SSL")) continue;           
            $cert = $this->parseLine($line);           
        }             
    }
    private function parseLine($line) {
        /** 
         * @var CertConfig $cert
         * */
        $params = explode(";", $line);
        $longName =explode(", ", $params[1]);
        $name = $longName[1];        
        try {            
            $cert = clone $this->config->get($name);
        } catch (AscioException $e) {
            return false; 
        }
        $method = explode(" ",$longName[0])[0];        
        $san = strpos($longName[0],"SAN") == false ? false : true;
        if($san) {
            $id = $cert->id . "_san";                      
        } else {
            $id = $cert->id;
        }

        if($this->data[$method][$id]) {
            $cert = $this->data[$method][$id];
        } else {
            $cert->id = $id;
            $cert->san = $san;
            $cert->certificateId = $cert->id;
            $cert->method = $method; 
        }
              
        $period = (integer) explode(" ",$longName[2])[0];
        $price = (float) $params[9];
        $cert->getPrices()->add($period,$price);
        $cert->currency = $params[10];
        $this->currency = $cert->currency;
        $this->getCurrencyId();
        if(!isset($this->data[$cert->method])) {
            $this->data[$cert->method] = [];
        }     
        $this->data[$cert->method][$cert->id] = $cert;
        return $cert;
    }
    public function setProducts($products) {
        $this->productIds  = $products;
    }
    public function setMargin($margin) {
        $this->margin = $margin; 
    }
    public function setRoundStep($step) {
        $this->roundstep = $step; 
    }

    public function get($type=null,$method="Register") {
        return $this->data[$method];
    }
    public function getSans(CertConfig $cert) : CertConfig {
        $name = $cert->id."_san";
        $san =  $this->data["Register"][$name];
        if(!$san) {
            throw new AscioException("Sans for certificate '".$cert->id . "' not found",404);
        }
        return $san;
    }
    private function setSan(CertConfig $cert) {
        $name = $cert->id."_san";
        $this->data["Register"][$name] = $cert;                
    }
   
    public function preview($type=null,$method="Register") {
        $rows = "";
        $yes = '<span class="glyphicon glyphicon-ok" style="color:limegreen"> </span>';
        $no  = '<span class="glyphicon glyphicon-remove" style="color:darkred"> </span>';        

        foreach($this->get($type,$method) as $id => $cert) {
            /**
            * @var CertConfig $cert 
            */
            if($cert->san==true) {
                continue;
            }
            $sanInfo = '<span class="glyphicon glyphicon-exclamation-sign" style="color:darkred" title="No SAN-Prices in Pricelist for '.$cert->name.'. Please contact your account manager"> </span>';
            $wc  = $cert->wildcard ? $yes : $no;
            $md = $cert->multiDomain ? $yes : $no;
            $maxSans = $cert->multiDomain ?  $cert->maxSans : "";
            $freeSans = $cert->multiDomain ?  $cert->freeSans : "";
            $currency = $cert->currency  == "EUR" ? "€ " : "$ " ;
            $checked = (!isset($this->productIds) || in_array($cert->id,$this->productIds)) ? 'checked="checked"' : '';
            $checkbox = '<input type="checkbox" '.$checked.' style ="width:20px;height:20px" class="form-control cert-select" data-id="'.$cert->id.'"></input>';
            $cert->getPrices()->calculate($this->margin,$this->roundstep);
            if($cert->multiDomain) {
                try {
                    $prices = $this->getSans($cert)->getPrices();
                } catch (AscioException $e) {
                    $checkbox = $sanInfo;
                }
            }            
            $rows .= '
                <tr>
                    <th>'.$checkbox.'</th>
                    <th>'.$cert->name.'</th>
                    <td>'.$cert->type.'</td>
                    <td>'.$wc.'</td>
                    <td>'.$md.'</td>
                    <td>'.$currency.$cert->getPrices()->annually.'</td>
                    <td>'.$currency.$cert->getPrices()->annuallyOld.'</td>
                    <td>'.$maxSans.'</td>
                    <td>'.$freeSans.'</td>
                </tr>
            ';
        }
        return '
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th></th>
                        <th>Type</th>
                        <th>Wildcard</th>
                        <th>Multi-Domain</th>
                        <th>New Price</th>
                        <th>Old Price</th>
                        <th>Max SANs</th>
                        <th>Free SANs</th>                        
                    </tr>
                </thead>
                <tbody>'.$rows.'</tbody>
            </table>';
    }
    public function import() {
        $this->createSans();
        $this->createProducts();
        $this->createLinks();
    }
    private function createSans() {
        foreach($this->data["Register"] as $key => $cert) {
            /**
             * @var CertConfig $cert;
             */
            if(!$cert->san) continue;     
            if(!$this->productIds || in_array($cert->certificateId,$this->productIds)) {
                $this->createProductConfigGroups($cert);
                $this->createProductConfigOptions($cert);
                $this->createProductConfigOptionsSub($cert);
                $this->createSanPricing($cert);
            }            
            
        }
    }
    private function createProducts() {
        foreach($this->data["Register"] as $key => $cert) {
            if($cert->san) continue;                     
            if(!$this->productIds || in_array($cert->certificateId,$this->productIds)) {
                $this->createProductGroup($cert);
                $this->createProduct($cert);
            }

        }
    }
    private function createLinks() {
        foreach($this->data["Register"] as $key => $cert) {
            if($cert->san) continue;                     
            if(!$cert->multiDomain) continue;
            /**
             * @var CertConfig $san
             */
            if(!$this->productIds || in_array($cert->certificateId,$this->productIds)) {
                try {
                    $san = $this->getSans($cert);
                    if($san->productConfigGid) {
                        $this->createProductConfigLinks($cert->productId,$san->productConfigGid);
                    }
                } catch (AscioException $e) {
                    logModuleCall(
                        'ascio',
                        'GetSans',
                        "San-Prices for ".$cert->id." not found" ,			
                        "San-Prices for ".$cert->id." not found" ,		
                        json_encode("San-Prices for ".$cert->id." not found" )                     
                        );
                }
            }            
        }
    }
    private function createProductConfigLinks(int $pid,int $configGid) {
        $table =  Capsule::table("tblproductconfiglinks");
        $filter = [
            "gid" => $configGid,
            "pid" => $pid

        ];
        $existing = $table->where($filter)->first();
        if($existing) {
            return ;
        } 
        $table->insert($filter);
    }
    private function createProductConfigGroups(ssl\CertConfig $cert) {
        $table =  Capsule::table("tblproductconfiggroups");
        $groupName = $cert->name . " Parameters";
        $existing = $table->where(["name" => $groupName])->first();
        if($existing) {
            $id = $existing->id;
        } else {
            $id = $table->insertGetId(["name"  => $groupName,"description" => ""]);;
        }
        $cert->productConfigGid = $id;
        return $id; 
    }
    private function createProductConfigOptions(ssl\CertConfig $cert) {
        $table =  Capsule::table("tblproductconfigoptions");
        $name = "SANs ". $cert->name;   
        $filter = ["optionname" => $name];
        $existing = $table->where($filter)->first();
        $data = [
            "gid" => $cert->productConfigGid,
            "optionname"  => $name,
            "optiontype" => 4,
            "qtyminimum" => 0,
            "qtymaximum" => $cert->maxSans,
            "order"  => 0,
            "hidden" => 0
        ];
        if($existing) {
            $table->where($filter)->update($data);
            $id = $existing->id;
        } else {
            $id = $table->insertGetId($data);
        }
        
        $cert->productConfigOptionsId = $id;
        return $id; 
    }
    private function createProductConfigOptionsSub(ssl\CertConfig $cert) {
        $table =  Capsule::table("tblproductconfigoptionssub");
        $filter = ["configid" => $cert->productConfigOptionsId];
        $existing = $table->where($filter)->first();
        $data = [
            "configid"  => $cert->productConfigOptionsId,
            "optionname" => "1",
            "sortorder" => 0,
            "hidden" => 0
        ];
        if($existing) {
            $table->where($filter)->update($data);
            $id = $existing->id;
        } else {
            $id = $table->insertGetId($data);
        } 
        $cert->productConfigOptionsSubId = $id;      
        return $id; 
    }
    private function createSanPricing(ssl\CertConfig $cert) {
        $cert->getPrices()->calculate($this->margin,$this->roundstep);
        $currency = $this->currencyId;
        $cert->currencyId = $currency;
        $table =  Capsule::table("tblpricing");
        $filter = [
            "relid" => $cert->productConfigOptionsSubId,
            "type" => "configoptions",
            "currency" => $currency

        ];
        $existing = $table->where($filter)->first();        
        $data = [
            "msetupfee" => 0,
            "qsetupfee" => 0,
            "ssetupfee" => 0,
            "asetupfee" => 0,
            "bsetupfee" => 0,
            "tsetupfee" => 0
            
        ];
        $data = array_merge($cert->getPrices()->get(),$data,$filter);
        if($existing) {
            $table->where($filter)->update($data);
            $id = $existing->id;
        } else {
            $id = $table->insertGetId($data);
        }     
        return $id; 
    }    
    private function createProductGroup(ssl\CertConfig $cert) {
        $table =  Capsule::table("tblproductgroups");
        $name = $cert->type . " SSL-Certificates";
        $filter = [
            "name" => $name,
        ];
        $existing = $table->where($filter)->first();
        if($existing) {
            $id = $existing->id; 
        } else {
            $id = $table->insertGetId([
                "name"  => $name,
                "headline" => $name,
                "tagline" => "",
                "orderfrmtpl" => "",
                "disabledgateways" => "",
                "hidden" => 0               
            ]);
        } 
        $cert->productGroupId = $id;
        return $id; 
    }    
    private function getCurrencyId() {
        if($this->currencyId) return $this->currencyId;        
        $this->currencyId = Capsule::table("tblcurrencies")
        ->where("code",$this->currency)        
        ->value("id");
        return $this->currencyId;
    }
    private function createProduct(ssl\CertConfig $cert) {
        $cert->getPrices()->calculate($this->margin,$this->roundstep);
        $table =  Capsule::table("tblproducts");
        $filter = [
            "name" => $cert->name,
        ];
        $existing = $table->where($filter)->first();
        $currencyId = $this->getCurrencyId();
        if(!$currencyId) {
            throw new AscioException("Currency with the code '".$cert->currency."' not found in WHMCS. Please add the Currency in Settings->Payments->Currencies");
        }
        $data = [
            'type' => 'other',
            'gid' => $cert->productGroupId,
            'name' => $cert->name,
            'welcomeemail' => '0',
            'paytype' => 'recurring',           
            'description' => $cert->getDescription()
        ];
        if($existing) {
            $table->where($filter)->update($data);
            $tablePricing = Capsule::table("tblpricing"); 
            $cert->productId = $existing->id; 
            $filterPrice = [
                "relid"=>$cert->productId,
                "type" => "product",
                "currency" => $currencyId
            ]; 
            $tablePricing->where($filterPrice)
            ->update($cert->getPrices()->get());
            return $existing->id; 

            
        } else {                     
            $data["pricing"] = array($currencyId => $cert->getPrices()->get());
            $data["module"] =   'asciossl';
            $data["configoption1"] =   $cert->id;
            $results = localAPI("AddProduct", $data);
            $cert->productId = $results["pid"]; 
            return $results["pid"];
        }     
    }
}
