<?php
class PriceImporter {
	private $account;
	private $password; 
	public function __construct($account,$password) {
		$this->account = $account;
		$this->password = $password;
	}
	public function getTld($tldName) {
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
		$result = file_get_contents($url, false, $context, -1, 40000);
		$tldData = json_decode($result);
		$tld = new Tld($tldData);
		return $tld;
	}
	
}

class Tld {
	private $products; 
	public function __construct($data) {
		$this->getProducts($data);
	}
	private function getProducts($tldData) {
		foreach ($tldData->Products as $key => $productData) {
			$product = new Product($productData);			
			if($product->isUsed()) var_dump($product);
		}
	}
}
class Product {
	public $command; 
	public $period;
	public $price;
	public $lp;
	private $objectType;
	
	public function __construct($data) {
		$this->command = $data->Command;
		$this->price = $data->Price;
		$this->period = $data->Period;
		$this->objectType = $data->ObjectType; 
	}
	public function isUsed() {
		$usedTypes = array("REGISTER","RENEW","TRANSFER");
		if($this->objectType == "DOMAINNAME" && in_array($this->command, $usedTypes)) return true; 
	}
}

echo "start\r\n";

$priceImporter = new PriceImporter("mlautenschlager", "dPiwb,.9988");
$tld = $priceImporter->getTld("com");
var_dump($tld);


echo "end\r\n";