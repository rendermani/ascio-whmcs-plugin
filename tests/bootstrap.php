<?php
/**
 * PHPUnit Bootstrap File for Ascio WHMCS Plugin Tests
 *
 * Sets up WHMCS function mocks and autoloading for unit tests.
 * IMPORTANT: Class aliases MUST be set up BEFORE loading any classes that use them.
 */

define('WHMCS_UNIT_TEST', true);

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load mock classes FIRST (before any aliases)
require_once __DIR__ . '/Mocks/WhmcsFunctionsMock.php';
require_once __DIR__ . '/Mocks/SoapClientMock.php';
require_once __DIR__ . '/Mocks/CapsuleMock.php';
require_once __DIR__ . '/Mocks/WhmcsClassMocks.php';
require_once __DIR__ . '/Mocks/MockAscioClientV3.php';
require_once __DIR__ . '/Mocks/MockParamsV3.php';

// ============================================================================
// SET UP ALL CLASS ALIASES BEFORE LOADING ANY ASCIO CLASSES
// ============================================================================

// Mock WHMCS Database Capsule
if (!class_exists('WHMCS\Database\Capsule')) {
    class_alias(\Ascio\Tests\Mocks\CapsuleMock::class, 'WHMCS\Database\Capsule');
}

// Mock Illuminate Capsule for direct usage (used by AutoExpireService, etc.)
if (!class_exists('Illuminate\Database\Capsule\Manager')) {
    class_alias(\Ascio\Tests\Mocks\CapsuleMock::class, 'Illuminate\Database\Capsule\Manager');
}

// Mock WHMCS Domain classes
if (!class_exists('WHMCS\Carbon')) {
    class_alias(\Ascio\Tests\Mocks\CarbonMock::class, 'WHMCS\Carbon');
}

if (!class_exists('WHMCS\Domain\Registrar\Domain')) {
    class_alias(\Ascio\Tests\Mocks\DomainMock::class, 'WHMCS\Domain\Registrar\Domain');
}

if (!class_exists('WHMCS\Domains\DomainLookup\ResultsList')) {
    class_alias(\Ascio\Tests\Mocks\ResultsListMock::class, 'WHMCS\Domains\DomainLookup\ResultsList');
}

if (!class_exists('WHMCS\Domains\DomainLookup\SearchResult')) {
    class_alias(\Ascio\Tests\Mocks\SearchResultMock::class, 'WHMCS\Domains\DomainLookup\SearchResult');
}

if (!class_exists('WHMCS\Domain\TopLevel\ImportItem')) {
    class_alias(\Ascio\Tests\Mocks\ImportItemMock::class, 'WHMCS\Domain\TopLevel\ImportItem');
}

if (!class_exists('WHMCS\Results\ResultsList')) {
    class_alias(\Ascio\Tests\Mocks\PriceResultsListMock::class, 'WHMCS\Results\ResultsList');
}

// ============================================================================
// MOCK WHMCS GLOBAL FUNCTIONS
// ============================================================================

if (!function_exists('logActivity')) {
    function logActivity($message) {
        \Ascio\Tests\Mocks\WhmcsFunctionsMock::logActivity($message);
    }
}

if (!function_exists('logModuleCall')) {
    function logModuleCall($module, $action, $requestData, $responseData, $processedData = null, $replaceVars = []) {
        \Ascio\Tests\Mocks\WhmcsFunctionsMock::logModuleCall($module, $action, $requestData, $responseData, $processedData, $replaceVars);
    }
}

if (!function_exists('localAPI')) {
    function localAPI($command, $values = [], $adminUser = null) {
        return \Ascio\Tests\Mocks\WhmcsFunctionsMock::localAPI($command, $values, $adminUser ?? '');
    }
}

if (!function_exists('get_query_val')) {
    function get_query_val($table, $field, $where) {
        return \Ascio\Tests\Mocks\WhmcsFunctionsMock::get_query_val_enhanced($table, $field, $where);
    }
}

// Mock WHMCS legacy database functions
if (!function_exists('insert_query')) {
    function insert_query($table, $data) {
        return \Ascio\Tests\Mocks\WhmcsFunctionsMock::insert_query($table, $data);
    }
}

if (!function_exists('update_query')) {
    function update_query($table, $data, $where) {
        return \Ascio\Tests\Mocks\WhmcsFunctionsMock::update_query($table, $data, $where);
    }
}

if (!function_exists('select_query')) {
    function select_query($table, $fields, $where) {
        return \Ascio\Tests\Mocks\WhmcsFunctionsMock::select_query($table, $fields, $where);
    }
}

// Mock getRegistrarConfigOptions function for TldSync
if (!function_exists('getRegistrarConfigOptions')) {
    function getRegistrarConfigOptions($registrar) {
        return [
            'Username' => 'testuser',
            'Password' => 'testpass',
            'TestMode' => 'on'
        ];
    }
}

// Mock WHMCS hook registration function
if (!function_exists('add_hook')) {
    function add_hook($hookPoint, $priority, $function) {
        global $__registered_hooks;
        if (!isset($__registered_hooks)) {
            $__registered_hooks = [];
        }
        $__registered_hooks[] = [
            'hookPoint' => $hookPoint,
            'priority' => $priority,
            'function' => $function,
        ];
    }
}

// Mock legacy mysql functions (deprecated but still used in code)
if (!function_exists('mysql_query')) {
    function mysql_query($query) {
        return \Ascio\Tests\Mocks\WhmcsFunctionsMock::mysql_query($query);
    }
}

if (!function_exists('mysql_fetch_assoc')) {
    function mysql_fetch_assoc($result) {
        return \Ascio\Tests\Mocks\WhmcsFunctionsMock::mysql_fetch_assoc_enhanced($result);
    }
}

if (!function_exists('mysql_error')) {
    function mysql_error() {
        return \Ascio\Tests\Mocks\WhmcsFunctionsMock::mysql_error();
    }
}

if (!function_exists('mysql_insert_id')) {
    function mysql_insert_id() {
        return \Ascio\Tests\Mocks\WhmcsFunctionsMock::mysql_insert_id();
    }
}

// ============================================================================
// NOW LOAD ASCIO CLASSES (after aliases are set up)
// ============================================================================

// Load legacy Ascio classes that are not autoloaded via composer
// These classes don't follow PSR-4 naming conventions
if (!class_exists('AscioQueue')) {
    require_once __DIR__ . '/../lib/Queue.php';
}

// Load Ascio v3 service classes (if available)
// Check if the autoload function already exists to avoid redeclaration
if (!function_exists('autoload_ca5124cc493862de39cebdb26d543f92')) {
    $v3AutoloadPath = __DIR__ . '/../ssl/v3/service/autoload.php';
    if (file_exists($v3AutoloadPath)) {
        require_once $v3AutoloadPath;
    }
}

// Request class is autoloaded via composer (namespace ascio)

// ============================================================================
// LOAD PRICE IMPORTER CLASSES (without init.php dependency)
// ============================================================================

// Define classes from PriceImporter.php that are not autoloaded
// The original file requires init.php which we can't load in tests
if (!class_exists('Product')) {
    class Product {
        public $command;
        public $period;
        public $price;
        public $lp;
        public $tld;
        public $currency;
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
            $this->command = $data->Command;
            $this->price = $data->Price;
            $this->period = $data->Period;
            $this->currency = $data->Currency == "EUR" ? 2 : 1;
            $this->objectType = $data->ObjectType;
            $this->tld = $tld;
        }

        public function isUsed() {
            $usedTypes = array("REGISTER", "RENEW", "TRANSFER");
            if ($this->objectType == "DOMAINNAME" && in_array($this->command, $usedTypes) && $this->hasPrice()) return true;
        }

        public function getEndcustomerPrice() {
            $price = $this->price + ($this->price * ($this->tld->margin / 100));
            $newPrice = ceil($price) - 0.1;
            return $newPrice;
        }

        public function hasPrice() {
            if ($this->price > 0) {
                return true;
            }
        }

        public function updateWhmcs() {
            if (!$this->isUsed()) return;
            $filter = " WHERE  `relid` = " . $this->tld->id . " and type='" . $this->getWhmcsCommand() . "' and currency=" . $this->currency;
            $query = "SELECT * FROM  `tblpricing` " . $filter;
            $result = mysql_query($query);
            if (mysql_error()) return mysql_error() . "\n";
            $whmcsPeriod = $this->getWhmcsPeriod($this->period);
            $command = $this->getWhmcsCommand();

            if (!mysql_fetch_assoc($result)) {
                $query = "
                    insert into tblpricing (type,currency,relid," . $whmcsPeriod . ")
                    values
                    ('" . $command . "'," . $this->currency . "," . $this->tld->id . ",'" . $this->getEndcustomerPrice() . "')";
                mysql_query($query);
                if (mysql_error()) {
                    echo "Error inserting into tblpricing: " . mysql_error() . "\n" . $query;
                }
            } else {
                $query = "update tblpricing set " . $whmcsPeriod . " = " . $this->getEndcustomerPrice() . $filter;
                mysql_query($query);
                if (mysql_error()) {
                    echo "Error inserting into tblpricing: " . mysql_error();
                }
            }
            return true;
        }

        private function getWhmcsCommand() {
            $map = array(
                "REGISTER" => "domainregister",
                "RENEW" => "domainrenew",
                "TRANSFER" => "domaintransfer"
            );
            return $map[$this->command];
        }

        private function getWhmcsPeriod($period) {
            if ($period == 0) $period = 1;
            return $this->whmcsPeriods[$period - 1];
        }
    }
}

if (!class_exists('Tld')) {
    class Tld {
        public $name;
        public $id;
        public $margin;
        public $data;
        private $products = array();

        public function __construct($data, $margin) {
            $this->name = $data->Name;
            $this->data = $data;
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
            $query = "SELECT * FROM  `tbldomainpricing` WHERE  `extension` = '." . $this->name . "'\n";
            $result = mysql_query($query);
            $tld = mysql_fetch_assoc($result);
            if (!$tld) {
                echo "create tld " . $this->name . "\n";
                $query = "
                    insert into tbldomainpricing (extension,autoreg,dnsmanagement,idprotection,eppcode)
                    values
                    ('." . $this->name . "','ascio','on','on','on')";
                mysql_query($query);
                $this->id = mysql_insert_id();
                if (mysql_error()) {
                    echo "Error inserting TLD: " . mysql_error();
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
}

if (!class_exists('PriceImporter')) {
    class PriceImporter {
        private $account;
        private $password;
        private $margin;

        public function __construct($account, $password) {
            $this->account = $account;
            $this->password = $password;
        }

        public function updateTld($tldName) {
            $context = $this->getRequestContext();
            $url = 'https://tldkit.ascio.com/api/v1/TldKit/' . $tldName;
            $result = file_get_contents($url, false, $context);
            $tldData = json_decode($result);
            $tld = new Tld($tldData, $this->margin);
            if ($tld->updateWhmcs()) {
                echo "Update TLD " . $tldName . "\n";
            } else {
                echo "Skipping TLD " . $tldName . "\n";
            }
            return $tld;
        }

        public function updateTlds() {
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

        private function getRequestContext() {
            $opts = array('http' =>
                array(
                    'method' => 'GET',
                    'header' => "Content-Type: application/json\r\n" .
                        "Authorization: Basic " . base64_encode($this->account . ":" . $this->password) . "\r\n",
                    'timeout' => 60
                )
            );
            $context = stream_context_create($opts);
            return $context;
        }
    }
}
