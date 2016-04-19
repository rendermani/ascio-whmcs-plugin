<?php

require_once("../../../../init.php");
class PriceImporter {
	private $account;
	private $password; 
	public function __construct($account,$password) {
		$this->account = $account;
		$this->password = $password;
	}
	public function getTld($tldName) {
		echo "start get tld\n";
		$opts = array('http' =>
				array(
						'method'  => 'GET',
						'header'  => "Content-Type: application/json\r\n".
						"Authorization: Basic ".base64_encode($this->account.":".$this->password)."\r\n",
						'timeout' => 60
				)
		);
		$context  = stream_context_create($opts);
		$url = 'https://tldkit.ascio.com/api/v1/TldKit/'.$tldName;
		$result = file_get_contents($url, false, $context);
		$tldData = json_decode($result);
		$tld = new Tld($tldData);
		return $tld;
	}
	
}

class Tld {
	public $name; 
	private $products = array(); 
	public function __construct($data) {
		$this->name = $data->Name;
		$this->getProducts($data);
		
	}
	private function getProducts($tldData) {
		foreach ($tldData->Products as $key => $productData) {
			$product = new Product($productData,$this);			
			if($product->isUsed()) {
				$this->products[] = $product;
			}
		}
	}
	public function updateWhmcs() {			
		$query = "SELECT * FROM  `tbldomainpricing` WHERE  `extension` = '.".$this->tld->name."'\n";
		$result = mysql_query($query);		
		$tld = mysql_fetch_assoc($result);
		echo $query . "\n";
		if(!$tld) {
			echo "create tld\n";
			$this->id = insert_query(array(
				"extension" => ".".$this->name,
				"autoreg  " => "ascio",
				"dnsmanagement" => "on",
				"eppcode" => "on"
			));
		} else {
			$this->id = $tld->id;
		}
		var_dump($this);
		die();
		foreach ($this->products as $key => $product) {
			$product->updateWhmcs();
		}
	}
}
class Product {	
	public $command; 
	public $period;
	public $price;
	public $lp;
	public $tld;
	private $objectType;

	public function __construct($data,$tld) {
		$this->command = $data->Command;
		$this->price = $data->Price;
		$this->period = $data->Period;
		$this->objectType = $data->ObjectType; 
		$this->tld = $tld; 
	}
	public function isUsed() {
		$usedTypes = array("REGISTER","RENEW","TRANSFER");
		if($this->objectType == "DOMAINNAME" && in_array($this->command, $usedTypes)) return true; 
	}
	public function updateWhmcs() {
		$query = "SELECT * FROM  `tblpricing` WHERE  `id` = ".$this->tld->id." and type='". $this->getWhmcsCommand()."'";
		$tld = mysql_query($query);
		echo mysql_error()."\n";
		echo $this->command .  ": ".$query."\n";
		//var_dump(mysql_fetch_assoc($tld))."\n";
	}
	private function getWhmcsCommand() {
		$map = array(
			"REGISTER" => "domainregister",
			"RENEW" => "domainrenew",
			"TRANSFER" => "domaintransfer"
		);
		return $map[$this->command];
	}	
	private function getAscioPeriod($period) {
		$priceMap = 	
			array(
				"msetupfee",
				"qsetupfee",
				"ssetupfee",
				"asetupfee",
				"bsetupfee",
				"tsetupfee",
				"monthly",
				"quarterly",
				"semiannually",
				"annually",
				"biennially",
				"triennially"
			);
		return $priceMap($period + 1);
	}

}

echo "start\r\n";

$priceImporter = new PriceImporter("xxxx", "xxxx");
$tld = $priceImporter->getTld("de");
$tld->updateWhmcs();


echo "end\r\n";