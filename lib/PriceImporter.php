<?php

use Illuminate\Database\Capsule\Manager as Capsule;

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

	public function __construct($data, $tld) {
		$this->command    = $data->Command;
		$this->price      = $data->Price;
		$this->period     = $data->Period;
		$this->currency   = $data->Currency == "EUR" ? 2 : 1;
		$this->objectType = $data->ObjectType;
		$this->tld        = $tld;
	}

	public function isUsed() {
		$usedTypes = array("REGISTER", "RENEW", "TRANSFER");
		if ($this->objectType == "DOMAINNAME" && in_array($this->command, $usedTypes) && $this->hasPrice()) return true;
	}

	public function getEndcustomerPrice() {
		$price    = $this->price + ($this->price * ($this->tld->margin / 100));
		$newPrice = ceil($price) - 0.1;
		return $newPrice;
	}

	public function hasPrice() {
		if ($this->price > 0) return true;
	}

	public function updateWhmcs() {
		if (!$this->isUsed()) return;
		$whmcsPeriod = $this->getWhmcsPeriod($this->period);
		$command     = $this->getWhmcsCommand();
		$endPrice    = $this->getEndcustomerPrice();

		try {
			$existing = Capsule::table('tblpricing')
				->where('relid', $this->tld->id)
				->where('type', $command)
				->where('currency', $this->currency)
				->first();

			if (!$existing) {
				Capsule::table('tblpricing')->insert(array(
					'type'       => $command,
					'currency'   => $this->currency,
					'relid'      => $this->tld->id,
					$whmcsPeriod => $endPrice
				));
			} else {
				Capsule::table('tblpricing')
					->where('relid', $this->tld->id)
					->where('type', $command)
					->where('currency', $this->currency)
					->update(array($whmcsPeriod => $endPrice));
			}
		} catch (\Exception $e) {
			echo "Error updating tblpricing: " . $e->getMessage() . "\n";
		}
		return true;
	}

	private function getWhmcsCommand() {
		$map = array(
			"REGISTER" => "domainregister",
			"RENEW"    => "domainrenew",
			"TRANSFER" => "domaintransfer"
		);
		return $map[$this->command];
	}

	private function getWhmcsPeriod($period) {
		if ($period == 0) $period = 1;
		return $this->whmcsPeriods[$period - 1];
	}
}

class Tld {
	public $name;
	public $id;
	public $margin;
	private $products = array();
	private $data;

	public function __construct($data, $margin) {
		$this->name   = $data->Name;
		$this->data   = $data;
		$this->margin = $margin;
	}

	private function getProducts() {
		if ($this->data->products && count($this->data->Products) > 0) {
			return $this->products;
		}
		$this->products = array();
		foreach ($this->data->Products as $key => $productData) {
			$product = new Product($productData, $this);
			if ($product->isUsed()) {
				$this->products[] = $product;
			}
		}
		return $this->products;
	}

	public function isActive() {
		if (count($this->getProducts()) > 0) return true;
	}

	public function updateWhmcs() {
		if (!$this->isActive()) return false;
		try {
			$tld = Capsule::table('tbldomainpricing')
				->where('extension', '.' . $this->name)
				->first();

			if (!$tld) {
				echo "create tld " . $this->name . "\n";
				$this->id = Capsule::table('tbldomainpricing')->insertGetId(array(
					'extension'    => '.' . $this->name,
					'autoreg'      => 'ascio',
					'dnsmanagement'=> 'on',
					'idprotection' => 'on',
					'eppcode'      => 'on'
				));
			} else {
				$this->id = $tld->id;
			}
		} catch (\Exception $e) {
			echo "Error updating tbldomainpricing: " . $e->getMessage() . "\n";
			return false;
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

	public function __construct($account, $password) {
		$this->account  = $account;
		$this->password = $password;
	}

	public function updateTld($tldName) {
		$context = $this->getRequestContext();
		$url     = 'https://tldkit.ascio.com/api/v1/TldKit/' . $tldName;
		$result  = file_get_contents($url, false, $context);
		$tldData = json_decode($result);
		$tld     = new Tld($tldData, $this->margin);
		if ($tld->updateWhmcs()) {
			echo "Update TLD " . $tldName . "\n";
		} else {
			echo "Skipping TLD " . $tldName . "\n";
		}
		return $tld;
	}

	public function updateTlds() {
		$context = $this->getRequestContext();
		$url     = 'https://tldkit.ascio.com/api/v1/TldKit/';
		$result  = file_get_contents($url, false, $context);
		$tlds    = json_decode($result);
		echo "Get TLD-List done\n";
		foreach ($tlds as $key => $tldName) {
			$this->updateTld($tldName);
		}
	}

	public function setMargin($margin) {
		$this->margin = $margin;
	}

	private function getRequestContext() {
		$opts = array('http' =>
			array(
				'method'  => 'GET',
				'header'  => "Content-Type: application/json\r\n" .
				             "Authorization: Basic " . base64_encode($this->account . ":" . $this->password) . "\r\n",
				'timeout' => 60
			)
		);
		return stream_context_create($opts);
	}
}

echo "start\r\n";
$priceImporter = new PriceImporter("account", "password");
$priceImporter->setMargin(10);
//$priceImporter->updateTld("ac");
$priceImporter->updateTlds();
echo "end\r\n";
