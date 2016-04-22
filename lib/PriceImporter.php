<?php

require_once("../../../../init.php");
class Product {	
	public $command; 
	public $period;
	public $price;
	public $lp;
	public $tld;
	private $objectType;
	private $whmcsPeriods = array(
		"msetupfee",
		"qsetupfee",
		"ssetupfee",
		"asetupfee",
		"bsetupfee",
		"monthly",
		"quarterly",
		"semiannually",
		"annually",
		"biennially",
		"triennially"
	);

	public function __construct($data,$tld) {
		$this->command = $data->Command;
		$this->price = $data->Price;
		$this->period = $data->Period;
		$this->currency = $data->Currency == "EUR" ? 2 : 1;
		$this->objectType = $data->ObjectType; 
		$this->tld = $tld; 
	}
	public function isUsed() {
		$usedTypes = array("REGISTER","RENEW","TRANSFER");
		if($this->objectType == "DOMAINNAME" && in_array($this->command, $usedTypes) && $this->hasPrice()) return true; 
	}
	public function getEndcustomerPrice() {
		$price = $this->price + ($this->price * ($this->tld->margin /100 ));
		$newPrice = ceil($price) - 0.1; 
		return $newPrice;
	}
	public function hasPrice() {	
		if($this->price > 0) {
				return true; 
		}
	}
	public function updateWhmcs() {
		// does the product price already exist
		if(!$this->isUsed()) return; 
		//echo "getEndcustomerPrice: ".$this->getEndcustomerPrice(). ", price: ".$this->price."\n";
		$filter =  " WHERE  `relid` = ".$this->tld->id." and type='". $this->getWhmcsCommand()."' and currency=".$this->currency;
		$query = "SELECT * FROM  `tblpricing` " . $filter; 
		$result = mysql_query($query);
		if (mysql_error()) return mysql_error()."\n";
		$whmcsPeriod = $this->getWhmcsPeriod($this->period);
		$command 	 = $this->getWhmcsCommand();

		// insert new record 
		if(!mysql_fetch_assoc($result)) {
			$query = "
				insert into tblpricing (type,currency,relid,".$whmcsPeriod.") 
				values 
				('".$command."',".$this->currency.",".$this->tld->id.",'".$this->getEndcustomerPrice()."')";
			mysql_query($query);
			if(mysql_error()) {
				echo "Error inserting into tblpricing: ".mysql_error()."\n".$query;
			}
		} else {
			$query = "update tblpricing set ".$whmcsPeriod." = ".$this->getEndcustomerPrice().$filter;
			mysql_query($query);
			if(mysql_error()) {
				echo "Error inserting into tblpricing: ".mysql_error();
			}
			//echo $query; 
		}
		return true; 
	}
	private function getWhmcsCommand() {
		$map = array(
			"REGISTER"  => "domainregister",
			"RENEW" 	=> "domainrenew",
			"TRANSFER"  => "domaintransfer"
		);
		return $map[$this->command];
	}	
	private function getWhmcsPeriod($period) {
		if($period==0) $period=1; 
		return $this->whmcsPeriods[$period - 1];
	}
}
class Tld {
	public $name;
	public $id; 
	private $products = array(); 
	public function __construct($data,$margin) {
		$this->name = $data->Name;	
		$this->data = $data;		
		$this->margin = $margin; 
	}
	private function getProducts() {
		//$tldData = is_array($tldData) || $this->data;		

		if($this->data->products && count($this->data->Products) > 0) {
			return $this->products;
		}
		$this->products = array();
		foreach ($this->data->Products as $key => $productData) {
			$product = new Product($productData,$this);			
			if($product->isUsed()) {
				$this->products[] = $product;
			}
		}
		return $this->products;
	}
	public function isActive() {
		if(count($this->getProducts()) > 0) return true; 
	}
	public function updateWhmcs() {	
		if(!$this->isActive()) return false; 
		$query = "SELECT * FROM  `tbldomainpricing` WHERE  `extension` = '.".$this->name."'\n";
		$result = mysql_query($query);		
		$tld = mysql_fetch_assoc($result);
		if(!$tld) {
			echo "create tld ".$this->name."\n";			
			$query = "
				insert into tbldomainpricing (extension,autoreg,dnsmanagement,idprotection,eppcode) 
				values
				('.".$this->name."','ascio','on','on','on')";
			mysql_query($query);
			$this->id = mysql_insert_id();
			if(mysql_error()) {
				echo "Error inserting TLD: ".mysql_error();	
			}			
		} else {
			$this->id = $tld["id"];
		}
		$this->getProducts($this->data);
		foreach ($this->products as $key => $product) {
			$product->updateWhmcs(1);
			$product->updateWhmcs(2);
		}
		return true; 
	}
}
class PriceImporter {
	private $account;
	private $password; 
	private $margin;
	public function __construct($account,$password) {
		$this->account = $account;
		$this->password = $password;
	}
	public function updateTld($tldName) {		
		$context = $this->getRequestContext();
		$url = 'https://tldkit.ascio.com/api/v1/TldKit/'.$tldName;
		$result = file_get_contents($url, false, $context);
		$tldData = json_decode($result);
		$tld = new Tld($tldData,$this->margin);
		if($tld->updateWhmcs()) {
			echo "Update TLD ".$tldName."\n";		
		} else {
			echo "Skipping TLD ".$tldName."\n";	
		}
		return $tld;		
	}
	public function updateTlds($tldName) {
		$context = $this->getRequestContext();
		$url = 'https://tldkit.ascio.com/api/v1/TldKit/';
		$result = file_get_contents($url, false, $context);
		$tlds = json_decode($result);	
		echo "Get TLD-List done\n";
		foreach ($tlds as $key => $tldName) {
			$tld = $this->updateTld($tldName);
		}
	}
	public function setMargin($margin) {
		$this->margin = $margin; 
	}
	private function getRequestContext () {
		$opts = array('http' =>
					array(
							'method'  => 'GET',
							'header'  => "Content-Type: application/json\r\n".
							"Authorization: Basic ".base64_encode($this->account.":".$this->password)."\r\n",
							'timeout' => 60
					)
		);
		$context  = stream_context_create($opts);
		return $context;
	}
}

echo "start\r\n";
$priceImporter = new PriceImporter("account", "password");
$priceImporter->setMargin(10);
//$priceImporter->updateTld("ac");
$priceImporter->updateTlds("ac");
echo "end\r\n";