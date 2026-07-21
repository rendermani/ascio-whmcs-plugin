<?php
/**
 * Ascio v3 API Request Handler for WHMCS Domain Module
 *
 * Uses the Ascio v3 Web Service API with SOAP header authentication.
 * - No session management needed (uses SOAP header authentication)
 * - Uses v3 service classes from ssl/v3/service/
 * - Order structure follows v3 API conventions
 */
namespace ascio;

use Illuminate\Database\Capsule\Manager as Capsule;
use WHMCS\Domain\Registrar\Domain;
use ascio\ParameterCapture as ParameterCapture;
use ascio\Tools as Tools;
use ascio\AscioException as AscioException;

// Load required dependencies
require_once(__DIR__ . "/Tools.php");
require_once(__DIR__ . "/ParameterCapture.php");
require_once(__DIR__ . "/DomainHistory.php");

// Load v3 service classes autoloader
// Try multiple paths to support both local dev and Docker environments
// Check if already loaded (the autoload function has a unique hash-based name)
if (!function_exists('autoload_ca5124cc493862de39cebdb26d543f92')) {
    $v3ServicePaths = [
        __DIR__ . "/../../ssl/v3/service/autoload.php",                           // Local dev: ascio/domains/lib -> ascio/ssl/v3
        __DIR__ . "/../ssl/v3/service/autoload.php",                              // Monorepo: ascio/domains/lib -> ascio/domains/ssl/v3
        __DIR__ . "/../../../servers/asciossl/v3/service/autoload.php",           // Docker: modules/registrars/ascio/lib -> modules/servers/asciossl/v3
        __DIR__ . "/../../../../modules/servers/asciossl/v3/service/autoload.php", // Alternative Docker path
    ];
    $v3ServiceLoaded = false;
    foreach ($v3ServicePaths as $path) {
        if (file_exists($path)) {
            require_once($path);
            $v3ServiceLoaded = true;
            break;
        }
    }
    if (!$v3ServiceLoaded) {
        throw new \Exception("Could not find v3 service classes. Tried paths: " . implode(", ", $v3ServicePaths));
    }
}

// Import v3 service classes
use ascio\v3\AscioService;
use ascio\v3\CreateOrder;
use ascio\v3\ValidateOrder;
use ascio\v3\GetOrder;
use ascio\v3\GetOrders;
use ascio\v3\GetOrderRequest;
use ascio\v3\GetOrdersRequest;
use ascio\v3\PollQueue;
use ascio\v3\PollQueueRequest;
use ascio\v3\AckQueueMessage;
use ascio\v3\AckQueueMessageRequest;
use ascio\v3\GetQueueMessage;
use ascio\v3\GetQueueMessageRequest;
use ascio\v3\MessageType;
use ascio\v3\ObjectType;
use ascio\v3\OrderType;

// Define v3 WSDL endpoints only if not already defined
if (!defined("ASCIO_V3_WSDL_LIVE")) {
	define("ASCIO_V3_WSDL_LIVE","https://aws.ascio.com/v3/aws.wsdl");
}
if (!defined("ASCIO_V3_WSDL_TEST")) {
	define("ASCIO_V3_WSDL_TEST","https://aws.demo.ascio.com/v3/aws.wsdl");
}

/**
 * v3 API Exception for domain-specific errors
 */
if (!class_exists('ascio\AscioApiException')) {
class AscioApiException extends \Exception {
	protected $resultCode;
	protected $errors;

	public function __construct($message, $resultCode = 0, $errors = []) {
		parent::__construct($message, $resultCode);
		$this->resultCode = $resultCode;
		$this->errors = $errors;
	}

	public function getResultCode() {
		return $this->resultCode;
	}

	public function getErrors() {
		return $this->errors;
	}
}
}

// Note: v3 API uses SOAP header authentication, no session management needed

/**
 * Domain cache - caches domain objects to avoid repeated API calls
 */
if (!class_exists('ascio\DomainCache')) {
Class DomainCache {
	public static function get($domainId) {
		global $ascioDomainCache;
		if(!$ascioDomainCache) $ascioDomainCache = array();
		return $ascioDomainCache[$domainId] ?? null;
	}
	public static function put($domain) {
		global $ascioDomainCache;
		if(!$ascioDomainCache) $ascioDomainCache = array();
		$domainId = $domain->domainId ?? $domain->DomainId ?? null;
		if ($domainId) {
			$ascioDomainCache[$domainId] = $domain;
		}
	}
}
}

/**
 * Ascio v3 API Request class
 * Provides all domain registration operations using the v3 API
 */
if (!class_exists('ascio\Request')) {
Class Request {
	/** @var string */
	public $account;

	/** @var string */
	public $password;

	/** @var array */
	protected $params;

	/** @var object */
	protected $domain;

	/** @var string */
	public $domainName;

	/** @var AscioService */
	protected $client;

	/** @var bool */
	protected $testMode = false;

	/**
	 * Constructor
	 * @param array $params WHMCS module params
	 */
	public function __construct($params) {
		$this->setParams($params);
	}

	/**
	 * Factory method to create TLD-specific request handlers
	 * Checks for v3 TLD plugins first, falls back to base Request
	 *
	 * @param array $params WHMCS module params
	 * @return Request
	 */
	public static function create($params) {
		$tld = $params["tld"] ?? null;
		$tldDir = realpath(dirname(__FILE__))."/../tlds/";

		// Try TLD-specific handler (in tld/v3/ subdirectory)
		if ($tld) {
			$tldFilename = $tldDir . "$tld/v3/$tld.php";
			if(file_exists($tldFilename)) {
				require_once($tldFilename);
				$className = "\\ascio\\" . str_replace(".", "_", $tld);
				if (class_exists($className)) {
					return new $className($params);
				}
			}

			// Try direct tld/{tld}.php path (legacy)
			$directFilename = $tldDir . "$tld/$tld.php";
			if(file_exists($directFilename)) {
				require_once($directFilename);
				$className = "\\ascio\\" . str_replace(".", "_", $tld);
				if (class_exists($className)) {
					return new $className($params);
				}
			}
		}

		// Try parent TLD handler (e.g., "ag.it" -> "it", "co.uk" -> "uk")
		$parentTld = self::getParentTld($tld);
		if($parentTld) {
			// Check v3 subdirectory first
			$parentFilename = $tldDir . "$parentTld/v3/$parentTld.php";
			if(file_exists($parentFilename)) {
				require_once($parentFilename);
				$className = "\\ascio\\" . str_replace(".", "_", $parentTld);
				if (class_exists($className)) {
					return new $className($params);
				}
			}

			// Check direct path
			$parentDirectFilename = $tldDir . "$parentTld/$parentTld.php";
			if(file_exists($parentDirectFilename)) {
				require_once($parentDirectFilename);
				$className = "\\ascio\\" . str_replace(".", "_", $parentTld);
				if (class_exists($className)) {
					return new $className($params);
				}
			}
		}

		return new Request($params);
	}

	/**
	 * Get the parent TLD for sub-TLDs
	 * Examples: "ag.it" -> "it", "co.uk" -> "uk", "com.sg" -> "sg"
	 */
	private static function getParentTld($tld) {
		if(!$tld) {
			return null;
		}

		// Known parent TLD mappings
		$parentMappings = [
			// UK variants
			'co.uk' => 'uk',
			'org.uk' => 'uk',
			'ac.uk' => 'uk',
			'gov.uk' => 'uk',
			'me.uk' => 'uk',
			'net.uk' => 'uk',
			'sch.uk' => 'uk',
			// Singapore variants
			'com.sg' => 'sg',
			'edu.sg' => 'sg',
			'org.sg' => 'sg',
			'net.sg' => 'sg',
			'gov.sg' => 'sg',
			// Australia variants
			'com.au' => 'au',
			'net.au' => 'au',
			'org.au' => 'au',
			'edu.au' => 'au',
			'gov.au' => 'au',
			'id.au' => 'au',
			// AFNIC TLDs (French territories - inherit from .fr)
			'pm' => 'fr',
			're' => 'fr',
			'tf' => 'fr',
			'wf' => 'fr',
			'yt' => 'fr',
		];

		// Check explicit mappings first
		if(isset($parentMappings[$tld])) {
			return $parentMappings[$tld];
		}

		// For multi-part TLDs, extract the last part
		if(strpos($tld, '.') !== false) {
			$parts = explode('.', $tld);
			if(count($parts) >= 2) {
				return $parts[count($parts) - 1];
			}
		}

		return null;
	}

	/**
	 * Set module parameters and extract credentials
	 *
	 * @param array $params WHMCS module params
	 * @return array
	 */
	public function setParams($params) {
		if($params) {
			$this->params = $params;
			if(!empty($this->params["Username"])) $this->account = $this->params["Username"];
			if(!empty($this->params["Password"])) $this->password = $this->params["Password"];
			$this->testMode = ($this->params["TestMode"] ?? "") === "on";

			if(isset($params["domainObj"]) && isset($params["sld"])) {
				$this->domainName = $params["domainObj"]->getIdnSecondLevel().".".$params["domainObj"]->getTopLevel();
			} else {
				$this->domainName = $params["domainname"] ?? $params["domainName"] ?? null;
			}
		}
		return $this->params;
	}

	/**
	 * Get the v3 SOAP client with authentication headers
	 *
	 * @return AscioService
	 */
	protected function getClient() {
		if ($this->client === null) {
			$wsdl = $this->testMode ? ASCIO_V3_WSDL_TEST : ASCIO_V3_WSDL_LIVE;
			$options = [
				'cache_wsdl' => WSDL_CACHE_MEMORY,
				'trace' => 1,
				'exceptions' => true,
			];

			$this->client = new AscioService($options, $wsdl);

			// Set SOAP header authentication
			$credentials = [
				'Account' => $this->account,
				'Password' => $this->password
			];
			$header = new \SoapHeader(
				"http://www.ascio.com/2013/02",
				"SecurityHeaderDetails",
				$credentials,
				false
			);
			$this->client->__setSoapHeaders($header);
		}

		return $this->client;
	}

	/**
	 * Create the SOAP client for a request.
	 *
	 * Isolated in its own method so tests can override it (subclass) and inject
	 * a mock client, exercising the full callback/order lifecycle without a
	 * network call. Production behaviour is unchanged.
	 */
	protected function makeSoapClient($wsdl) {
		return new \SoapClient($wsdl, array("cache_wsdl" => WSDL_CACHE_MEMORY, "trace" => 1));
	}

	/**
	 * Send SOAP request to Ascio v3 API
	 * v3 uses SOAP headers for authentication instead of session-based auth
	 */
	protected function sendRequest($functionName, $ascioParams) {
		if(isset($ascioParams["order"])) {
			$orderType = " ".$ascioParams["order"]["Type"] ??"";
		} else {
			$orderType = "";
		}

		$wsdl = $this->params["TestMode"]=="on" ? ASCIO_V3_WSDL_TEST : ASCIO_V3_WSDL_LIVE;
		$client = $this->makeSoapClient($wsdl);
		$credentials = ["Account" => $this->account, "Password" => $this->password];
		$header = new \SoapHeader("http://www.ascio.com/2013/02", "SecurityHeaderDetails", $credentials, false);
		$client->__setSoapHeaders($header);

		// Simulation mode: replace CreateOrder with ValidateOrder for testing
		if ($functionName === 'CreateOrder' && $this->isSimulationMode()) {
			$functionName = 'ValidateOrder';
			logActivity("Ascio v3: Simulation mode - using ValidateOrder instead of CreateOrder");
		}

		// For CreateOrder/ValidateOrder, wrap the request as DomainOrderRequest via SoapVar
		// so PHP SoapClient serializes the polymorphic type correctly (xsi:type="DomainOrderRequest")
		if (in_array($functionName, ['CreateOrder', 'ValidateOrder'])) {
			// mapToOrder returns ['Order' => $order] - unwrap and convert to objects for SoapVar
			$orderData = $ascioParams['Order'] ?? $ascioParams;
			$requestObj = $this->arrayToObject($orderData);
			$soapRequest = new \SoapVar($requestObj, SOAP_ENC_OBJECT, "DomainOrderRequest", "http://www.ascio.com/2013/02");
			$callParams = array('parameters' => ["request" => $soapRequest]);
		} else {
			$callParams = array('parameters' => ["request" => $ascioParams]);
		}

		$response = $client->__soapCall($functionName, $callParams);
		$resultName = $functionName . "Result";
		$result = $response->$resultName;

		$parameterCapture = new ParameterCapture($this->params, $functionName, $orderType);
		$parameterCapture->capture();
		Tools::logModule($functionName, $ascioParams, $result);

		if ($result->ResultCode == 200 || $result->ResultCode == 201 || $result->ResultCode == 413) {
			return $result;
		} else if ($result->ResultCode == 554) {
			$messages = "Temporary error. Please try later or contact your support.";
		} elseif ($result->ResultCode == 401) {
			logActivity("Ascio v3 registrar plugin settings - Login failed, invalid account or password: ".$this->account);
			return array('error' => $result->ResultMessage ?? 'Login failed: invalid account or password');
		} else if (isset($result->Errors->string) && is_array($result->Errors->string) && count($result->Errors->string) > 1) {
			$messages = join(", \r\n", $result->Errors->string);
		} else {
			$messages = $result->Errors->string ?? $result->ResultMessage ?? 'Unknown error';
		}

		$message = Tools::cleanString($messages);
		return array('error' => $message);
	}

	/**
	 * Recursively convert nested arrays to stdClass objects for SoapVar serialization
	 */
	private function arrayToObject($data) {
		if (is_array($data)) {
			$obj = new \stdClass();
			foreach ($data as $key => $value) {
				$obj->$key = $this->arrayToObject($value);
			}
			return $obj;
		}
		return $data;
	}

	/**
	 * Check if simulation mode is enabled
	 */
	protected function isSimulationMode(): bool {
		if (getenv('ASCIO_SIMULATE') === '1' || getenv('ASCIO_SIMULATE') === 'true') {
			return true;
		}
		if (isset($this->params['Simulate']) && $this->params['Simulate'] === 'on') {
			return true;
		}
		return false;
	}

	/**
	 * Get prices from Ascio v3 API
	 */
	public function getPrices($ascioParams) {
		return $this->sendRequest("GetPrices", $ascioParams);
	}

	/**
	 * Check domain availability (v3 API)
	 */
	public function availabilityInfo($domain) {
		$ascioParams = array(
			'DomainName' => $domain,
			'Quality' => 'Live'
		);
		$result = $this->sendRequest("AvailabilityInfo", $ascioParams);
		if(isset($result->ResultCode) && $result->ResultCode >= 500) {
			return array('error' => $result->ResultMessage);
		}
		return $result;
	}

	/**
	 * Bulk availability check (v3 API)
	 */
	public function availabilityCheck($domain, $tlds) {
		$ascioParams = array(
			'Domains' => $domain,
			'Tlds' => $tlds,
			'Quality' => 'SmartLive'
		);
		$result = $this->sendRequest("AvailabilityCheck", $ascioParams);
		if(isset($result->ResultCode) && $result->ResultCode >= 500) {
			return array('error' => $result->ResultMessage);
		}
		return $result;
	}

	/**
	 * Poll the message queue for pending notifications (v3 API)
	 */
	public function poll() {
		$ascioParams = [
			'MsgType' => 'Message_to_Partner'
		];
		$result = $this->sendRequest("PollQueue", $ascioParams);
		if (is_array($result)) {
			return $result;
		}
		return $result;
	}

	/**
	 * Get a specific queue message by message ID (v3 API)
	 */
	public function getQueueMessage($messageId) {
		$ascioParams = [
			'MsgId' => $messageId
		];
		return $this->sendRequest("GetQueueMessage", $ascioParams);
	}

	/**
	 * Acknowledge a queue message (v3 API)
	 * Alias: ackQueueMessage
	 */
	public function ackQueueMessage($messageId) {
		$ascioParams = [
			'MsgId' => $messageId
		];
		return $this->sendRequest("AckQueueMessage", $ascioParams);
	}

	/**
	 * Acknowledge a queue message (v3 API)
	 * Alias for ackQueueMessage for v2 API compatibility
	 *
	 * @param int $msgId Message ID to acknowledge
	 * @return object|array
	 */
	public function ack($msgId) {
		return $this->ackQueueMessage($msgId);
	}

	/**
	 * Acknowledge message with order and domain context
	 * Used for callback processing
	 *
	 * @param int $messageId
	 * @param object|null $order
	 * @param object|null $domain
	 */
	public function ackMessage($messageId, $order = null, $domain = null) {
		$result = $this->ack($messageId);

		// Auto-create DNS zone if configured and order completed
		if ($order && $domain) {
			$orderType = $order->order->Type ?? $order->Order->Type ?? '';
			$orderStatus = $order->order->Status ?? $order->Order->Status ?? '';

			if (($orderType === "Register" || $orderType === "Transfer")
				&& $orderStatus === "Completed") {
				$nsRegex = $this->params["NameserverRegex"] ?? "/.*/";
				$ns1 = $domain->NameServers->NameServer1->HostName ?? '';

				if (preg_match($nsRegex, $ns1)) {
					$this->autoCreateZone($domain->DomainName ?? $this->domainName);
				}
			}
		}

		return $result;
	}

	/**
	 * Get order details by order ID (v3 API)
	 */
	public function getOrder($orderId) {
		$ascioParams = [
			'OrderId' => $orderId
		];
		$result = $this->sendRequest("GetOrder", $ascioParams);
		if (is_array($result)) {
			return $result;
		}
		return $result;
	}

	/**
	 * Get domain details by domain handle (v3 API)
	 */
	public function getDomain($handle) {
		$ascioParams = [
			'DomainHandle' => $handle
		];
		$result = $this->sendRequest("GetDomain", $ascioParams);
		if (is_array($result)) {
			return $result;
		}
		if (isset($result->Domain)) {
			return $result->Domain;
		}
		return $result;
	}

	/**
	 * Search for domain by name (v3 API)
	 */
	public function searchDomain() {
		$domainId = $this->params["domainid"];
		$domain = DomainCache::get($domainId);
		if(isset($domain)) return $domain;

		$handle = $this->getHandle("domain", $domainId, $this->domainName);
		if($handle) {
			$domain = $this->getDomain($handle);
			if(!$domain || isset($domain['error'])) {
				return ["error" => "Domain for the handle '".$handle."' was not found. Maybe the wrong account was configured."];
			}
			$domain->domainId = $domainId;
			$this->setDomainStatus($domain);
			DomainCache::put($domain);
			$this->setHandle($domain);
			return $domain;
		}

		// Search using v3 SearchDomain
		$criteria = array(
			'Mode' => 'Strict',
			'WithoutStates' => ['deleted'],
			'Clauses' => array(
				array(
					'Attribute' => 'DomainName',
					'Value' => $this->domainName,
					'Operator' => 'Is'
				)
			)
		);
		$ascioParams = array(
			'Criteria' => $criteria
		);
		$result = $this->sendRequest("SearchDomain", $ascioParams);
		if(isset($result['error'])) {
			return $result;
		}
		if(!isset($result->Domains) || !isset($result->Domains->Domain)) {
			return (object)["error" => "Domain not found"];
		}

		$domain = is_array($result->Domains->Domain) ? $result->Domains->Domain[0] : $result->Domains->Domain;
		$domain->domainId = $domainId;
		$this->setDomainStatus($domain);
		DomainCache::put($domain);
		$this->setHandle($domain);
		return $domain;
	}

	/**
	 * Register a new domain (v3 API)
	 */
	public function registerDomain($params = false) {
		$premiumDomainsEnabled = (bool) ($params['premiumEnabled'] ?? false);
		$premiumDomainsCost = $params['premiumCost'] ?? null;
		$params = $this->setParams($params);

		try {
			$ascioParams = $this->mapToOrder($params, "Register");
			if ($premiumDomainsEnabled && $premiumDomainsCost) {
				$ascioParams['Order']['AgreedPrice'] = $premiumDomainsCost;
			}
		} catch (AscioException $e) {
			return array("error" => $e->getMessage());
		}

		$result = $this->sendRequest("CreateOrder", $ascioParams);
		return $result;
	}

	/**
	 * Transfer a domain (v3 API)
	 */
	public function transferDomain($params = false) {
		$params = $this->setParams($params);
		try {
			$ascioParams = $this->mapToOrder($params, "Transfer");
			$datalessTlds = array("com","net","org","biz","info","us","cc","cn","com.cn","net.cn","org.cn","tv","it");
			if(in_array($params["tld"], $datalessTlds) && $this->params["DatalessTransfer"]=="on") {
				logActivity("WHMCS v3: Do dataless transfer");
				unset($ascioParams["Order"]["Domain"]["Registrant"]);
				unset($ascioParams["Order"]["Domain"]["AdminContact"]);
				unset($ascioParams["Order"]["Domain"]["TechContact"]);
				unset($ascioParams["Order"]["Domain"]["BillingContact"]);
				unset($ascioParams["Order"]["Domain"]["NameServers"]);
				unset($ascioParams["Order"]["Domain"]["DnsSecKeys"]);
				unset($ascioParams["Order"]["Options"]);
			}
		} catch (AscioException $e) {
			return array("error" => $e->getMessage());
		}

		$result = $this->sendRequest("CreateOrder", $ascioParams);
		$this->setOrderStatus($result, "Pending");

		// Initialize transfer tracking (PS-145)
		$domainId = $params["domainid"] ?? null;
		if ($domainId && !isset($result['error'])) {
			require_once(__DIR__ . "/TransferTracker.php");
			$orderId = $result->OrderId ?? $result->Order->OrderId ?? null;
			TransferTracker::updateStatus($domainId, 'pending', $orderId, 'Transfer initiated');
		}

		return $result;
	}

	/**
	 * Renew a domain (v3 API)
	 */
	public function renewDomain($params) {
		$result = Capsule::select("select Threshold, Renew from tblasciotlds where Tld = '".$params["tld"]."'")[0] ?? null;
		$params = $this->setParams($params);

		if($result && $result->Renew == 1) {
			try {
				$ascioParams = $this->mapToOrder($params, "Renew");
			} catch (AscioException $e) {
				return array("error" => $e->getMessage());
			}
		} else {
			$domain = $this->searchDomain($params);
			if($this->hasStatus($domain, "expiring")) {
				return $this->unexpireDomain($params);
			} else {
				return array("error" => "Domain can't be renewed again.");
			}
		}

		$result = $this->sendRequest("CreateOrder", $ascioParams);
		return $result;
	}

	/**
	 * Unexpire a domain (v3 API)
	 */
	public function unexpireDomain($params) {
		$params = $this->setParams($params);
		try {
			$ascioParams = $this->mapToOrder($params, "Unexpire");
		} catch (AscioException $e) {
			return array("error" => $e->getMessage());
		}
		return $this->sendRequest("CreateOrder", $ascioParams);
	}

	/**
	 * Expire a domain (v3 API)
	 */
	public function expireDomain($params) {
		$params = $this->setParams($params);
		try {
			$ascioParams = $this->mapToOrder($params, "Expire");
		} catch (AscioException $e) {
			return array("error" => $e->getMessage());
		}
		return $this->sendRequest("CreateOrder", $ascioParams);
	}

	/**
	 * Delete a domain (v3 API)
	 * Creates Delete_Domain order to delete the domain from the registry
	 */
	public function deleteDomain($params) {
		$params = $this->setParams($params);
		try {
			$ascioParams = $this->mapToOrder($params, "Delete");
		} catch (AscioException $e) {
			return array("error" => $e->getMessage());
		}
		return $this->sendRequest("CreateOrder", $ascioParams);
	}

	/**
	 * Restore a domain (v3 API)
	 * Creates Restore_Domain order for redemption recovery
	 */
	public function restoreDomain($params) {
		$params = $this->setParams($params);
		try {
			$ascioParams = $this->mapToOrder($params, "Restore");
		} catch (AscioException $e) {
			return array("error" => $e->getMessage());
		}
		return $this->sendRequest("CreateOrder", $ascioParams);
	}

	/**
	 * Cancel a pending order (v3 API)
	 * Calls CancelOrder API to cancel orders that are still pending
	 *
	 * @param array $params Module parameters containing orderId or domainid
	 * @return object|array Result object or error array
	 */
	public function cancelOrder($params) {
		$params = $this->setParams($params);

		// Try to find the pending order for this domain
		$orderId = $params['orderId'] ?? null;

		if (!$orderId) {
			// Search for pending orders for this domain
			$domain = $this->searchDomain();
			if (isset($domain['error'])) {
				return $domain;
			}

			// Get the most recent pending order
			$orderId = $this->findPendingOrderId($domain);
			if (!$orderId) {
				return array('error' => 'No pending order found for this domain');
			}
		}

		$ascioParams = array(
			'OrderId' => $orderId
		);

		return $this->sendRequest("CancelOrder", $ascioParams);
	}

	/**
	 * Find the most recent pending order ID for a domain
	 *
	 * @param object $domain Domain object from searchDomain
	 * @return string|null Order ID or null if not found
	 */
	protected function findPendingOrderId($domain) {
		$domainHandle = $domain->DomainHandle ?? $domain->Handle ?? null;
		if (!$domainHandle) {
			return null;
		}

		// Search for orders on this domain
		$criteria = array(
			'Mode' => 'Strict',
			'Clauses' => array(
				array(
					'Attribute' => 'DomainHandle',
					'Value' => $domainHandle,
					'Operator' => 'Is'
				),
				array(
					'Attribute' => 'Status',
					'Value' => 'Pending%',
					'Operator' => 'Like'
				)
			)
		);
		$ascioParams = array(
			'Criteria' => $criteria,
			'PageInfo' => array(
				'PageIndex' => 1,
				'PageSize' => 10
			)
		);

		$result = $this->sendRequest("SearchOrder", $ascioParams);

		if (isset($result['error'])) {
			return null;
		}

		if (!isset($result->Orders) || !isset($result->Orders->Order)) {
			return null;
		}

		$orders = is_array($result->Orders->Order) ? $result->Orders->Order : [$result->Orders->Order];
		if (count($orders) > 0) {
			// Return the most recent pending order
			return $orders[0]->OrderId ?? null;
		}

		return null;
	}

	/**
	 * Change domain owner (v3 API)
	 * Creates Owner_Change order for registrant transfer
	 */
	public function changeOwner($params) {
		$params = $this->setParams($params);
		try {
			$ascioParams = $this->mapToOrder($params, "OwnerChange");
		} catch (AscioException $e) {
			return array("error" => $e->getMessage());
		}
		return $this->sendRequest("CreateOrder", $ascioParams);
	}

	/**
	 * Update domain details (v3 API)
	 * Creates Domain_Details_Update order for updating domain information
	 */
	public function updateDomainDetails($params) {
		$params = $this->setParams($params);
		try {
			$ascioParams = $this->mapToOrder($params, "DetailsUpdate");
		} catch (AscioException $e) {
			return array("error" => $e->getMessage());
		}
		return $this->sendRequest("CreateOrder", $ascioParams);
	}

	/**
	 * Update nameservers (v3 API)
	 */
	public function saveNameservers($params) {
		$params = $this->setParams($params);
		try {
			$ascioParams = $this->mapToOrder($params, "NameserverUpdate");
		} catch (AscioException $e) {
			return array("error" => $e->getMessage());
		}
		return $this->sendRequest("CreateOrder", $ascioParams);
	}

	/**
	 * Update registrar lock (v3 API)
	 */
	public function saveRegistrarLock() {
		$lockstatus = $this->params["lockenabled"]=="unlocked" ? "UnLock" : "Lock";
		$lockParams = $this->mapToOrder($this->params, "ChangeLocks");
		$lockParams["Order"]["Domain"]["TransferLock"] = $lockstatus;
		return $this->sendRequest("CreateOrder", $lockParams);
	}

	/**
	 * Get EPP/Auth code (v3 API)
	 */
	public function getEPPCode($params) {
		$domain = $this->searchDomain($params);
		return array("eppcode" => $domain->AuthInfo ?? '');
	}

	/**
	 * Update EPP/Auth code (v3 API)
	 */
	public function updateEPPCode($params) {
		$params = $this->setParams($params);
		try {
			$ascioParams = $this->mapToOrder($params, "UpdateAuthInfo");
		} catch (AscioException $e) {
			return array("error" => $e->getMessage());
		}
		$result = $this->sendRequest("CreateOrder", $ascioParams);
		if(isset($result['error'])) {
			return $result;
		}
		return array("eppcode" => $ascioParams['Order']['Domain']['AuthInfo'] ?? '');
	}

	/**
	 * Process callback data from queue message
	 * Handles order status updates and domain status synchronization
	 *
	 * @param string $orderStatus Order status from callback
	 * @param int $messageId Queue message ID
	 * @param string $orderId Order ID
	 * @param string|null $type Callback type
	 */
	public function getCallbackData($orderStatus, $messageId, $orderId, $type = null) {
		// Get queue message details
		$queueResult = $this->getQueueMessage($messageId);
		if (isset($queueResult['error'])) {
			logActivity("Ascio v3 getCallbackData - Error getting queue message: " . $queueResult['error']);
			return;
		}

		$this->domainName = $queueResult->DomainName ?? $queueResult->ObjectName ?? null;

		// Get order details
		$order = $this->getOrder($orderId);
		if (isset($order['error'])) {
			logActivity("Ascio v3 getCallbackData - Error getting order: " . $order['error']);
			$this->ack($messageId);
			return;
		}

		// Handle SSL module orders separately
		$transactionComment = $order->Order->TransactionComment ?? '';
		if ($transactionComment === "WHMCS SSL Module") {
			require_once(__DIR__ . "/../../../servers/asciossl/lib/SslCallback.php");
			$params = new \ssl\Params();
			$params->testmode = $this->testMode;
			$params->setAccount($this->account, $this->password);
			$callback = new \ssl\SslCallback($params, $orderId);
			$message = $queueResult->Message ?? '';
			$callback->process($orderId, $orderStatus, $messageId, $message);
			return;
		}

		// Get domain details from order
		$domainObj = $order->Order->Domain ?? $order->OrderInfo->OrderRequest ?? null;
		$domainName = $domainObj->DomainName ?? $this->domainName;
		$domainId = Tools::getDomainIdFromOrder((object)['Domain' => $domainObj, 'TransactionComment' => $transactionComment]);

		// Verify this is an Ascio order
		if (!$this->isAscioOrder($domainId, $domainName)) {
			$domainId = null;
		}

		$this->params["domainid"] = $domainId;

		// If domain doesn't exist in WHMCS, ack and skip
		if (!isset($domainId)) {
			if (($this->params["MultiBrand_Mode"] ?? "") === "on") {
				sleep(5);
			} else {
				$this->ack($messageId);
			}
			Tools::log("DomainId not found in WHMCS: " . ($order->Order->Type ?? '') . ", Domain: " . $domainName);
			return;
		}

		// Get full domain info if we have a handle
		$domain = null;
		$domainHandle = $domainObj->DomainHandle ?? null;
		if ($domainHandle) {
			$domain = $this->getDomain($domainHandle);
			if ($domain && !isset($domain['error'])) {
				$this->setDomainStatus($domain);
				$domain->domainId = $domainId;
				DomainCache::put($domain);
				// NOTE: the persistent handle is deliberately NOT stored here.
				// This block runs for every callback, including Failed/Invalid
				// orders, whose handle must not overwrite the good one. The
				// handle is persisted only on Completed (see below).
			}
		}

		$this->params["domainname"] = $domainName;
		logActivity("WHMCS v3 getCallbackData -> setOrderStatus");
		$this->setOrderStatus($order);

		$errors = '';
		if ($orderStatus === "Completed") {
			// Auto-expire if configured
			$orderType = $order->Order->Type ?? '';
			if (($this->params["AutoExpire"] ?? "") === "on" &&
				($orderType === "Register" || $orderType === "Transfer")) {
				sleep(5);
				$this->expireDomain($this->params);
			}
			$this->deleteOldHandle($domainId);
			$this->setHandle($domain);
		} else {
			$msgPart = "Domain (" . $domainId . "): " . $domainName;
			$statusList = $queueResult->StatusList->CallbackStatus ?? null;
			$errors = Tools::formatError($statusList, $msgPart);
		}

		Tools::log($type . " received from Ascio v3. Order: " . ($order->Order->Type ?? '') . ", Domain: " . $domainName . ", Status: " . $orderStatus . "\n" . $errors);
		Tools::addNote($domainName, ($order->Order->Type ?? '') . ": " . $orderStatus . $errors);

		// Store detailed Ascio order status for visibility
		$orderType = $order->Order->Type ?? '';
		if ($domainId) {
			// Log to domain history table (PS-148)
			$whmcsStatus = Capsule::table('tbldomains')
				->where('id', $domainId)
				->value('status') ?? 'Unknown';
			$historyMessage = ($order->Order->Type ?? '') . ": " . $orderStatus;
			if ($errors) {
				$historyMessage .= "\n" . $errors;
			}
			DomainHistory::log(
				$domainId,
				$domainName,
				$orderStatus,
				$whmcsStatus,
				$orderId,
				$orderType,
				$historyMessage
			);
			Capsule::table('tbldomains_extra')
				->updateOrInsert(
					['domain_id' => $domainId, 'name' => 'ascio_order_status'],
					['value' => $orderStatus]
				);
			Capsule::table('tbldomains_extra')
				->updateOrInsert(
					['domain_id' => $domainId, 'name' => 'ascio_order_type'],
					['value' => $orderType]
				);
			Capsule::table('tbldomains_extra')
				->updateOrInsert(
					['domain_id' => $domainId, 'name' => 'ascio_order_id'],
					['value' => $orderId]
				);
			Capsule::table('tbldomains_extra')
				->updateOrInsert(
					['domain_id' => $domainId, 'name' => 'ascio_status_updated'],
					['value' => date('Y-m-d H:i:s')]
				);

			// Update transfer tracking status (PS-145)
			if ($orderType === 'Transfer') {
				require_once(__DIR__ . "/TransferTracker.php");
				$stage = TransferTracker::mapOrderStatusToStage($orderStatus);
				$message = $errors ?: null;
				TransferTracker::updateStatus($domainId, $stage, $orderId, $message);
			}
		}

		$this->ackMessage($messageId, $order, $domain);
		$this->sendStatus($order, $domainId, $orderStatus, $errors);
		$this->expireAfterRenew($order, $domain);
	}

	/**
	 * Check if order belongs to Ascio registrar
	 */
	protected function isAscioOrder($domainId, $domainName) {
		$result = Capsule::table('tbldomains')
			->where('id', $domainId)
			->where('domain', $domainName)
			->value('registrar');
		return $result === "ascio" || $result === "ascio_usd";
	}

	/**
	 * Auto-expire domain after auto-renew for TLDs without renew support
	 */
	protected function expireAfterRenew($order, $domain) {
		if (($this->params["AutoExpire"] ?? "") !== "on") return;
		$orderType = $order->Order->Type ?? '';
		$orderStatus = $order->Order->Status ?? '';
		if ($orderType === "Autorenew_Domain" && $orderStatus === "Completed" &&
			$domain && !$this->hasStatus($domain, "expiring")) {
			$this->expireDomain($this->params);
		}
	}

	/**
	 * Send status email notification
	 */
	public function sendStatus($order, $domainId, $orderStatus, $errors) {
		if (($this->params["DetailedOrderStatus"] ?? "") !== "on") return null;
		$orderType = $order->Order->Type ?? '';
		$allowedTypes = ["Register", "Transfer", "NameserverUpdate", "Delete",
			"Restore", "Queue", "Renew", "Unexpire", "ContactUpdate",
			"DetailsUpdate", "UpdateAuthInfo", "RegistrantDetailsUpdate", "ChangeLocks", "OwnerChange"];
		if (!in_array($orderType, $allowedTypes)) return null;
		$notifyStatuses = ['Completed', 'Failed', 'Invalid', 'Pending_End_User_Action', 'Pending_Documentation'];
		if (!in_array($orderStatus, $notifyStatuses)) return null;
		$values = [
			"messagename" => "Ascio Status",
			"customvars" => ["orderType" => str_replace("_", " ", $orderType), "status" => $orderStatus, "errors" => $errors],
			"id" => $domainId
		];
		return localAPI("sendemail", $values, Tools::getApiUser());
	}

	/**
	 * Auto-create DNS zone for new domains
	 */
	public function autoCreateZone($domain) {
		if (($this->params["AutoCreateDNS"] ?? "") !== "on") return;
		logActivity("WHMCS v3 Creating DNS zone: " . $domain);
		require_once(__DIR__ . "/Zone.php");
		$dns = $this->params["DNS_Default_Zone"] ?? '';
		$mx1 = $this->params["DNS_Default_Mailserver"] ?? '';
		$mx2 = $this->params["DNS_Default_Mailserver_2"] ?? '';
		$zone = new \ascio\dns\DnsZone($this->params, $domain);
		$params = $this->params;
		$params["dnsrecords"] = [
			["hostname" => "@", "type" => "A", "address" => $dns],
			["hostname" => "www", "type" => "A", "address" => $dns],
			["hostname" => "mail", "type" => "A", "address" => $mx1],
			["hostname" => "mail2", "type" => "A", "address" => $mx2],
			["hostname" => "@", "type" => "MX", "address" => "mail1", "priority" => 10],
			["hostname" => "@", "type" => "MX", "address" => "mail2", "priority" => 20]
		];
		$zone->update($params);
		Tools::log("Created DNS zone: " . $domain);
	}

	/**
	 * Start registrant verification process (v3 API)
	 */
	public function doRegistrantVerification($email) {
		$ascioParams = ['Email' => $email];
		return $this->sendRequest("StartRegistrantVerification", $ascioParams);
	}

	/**
	 * Get registrant verification status (v3 API)
	 */
	public function getRegistrantVerificationInfo($email) {
		$ascioParams = ['Email' => $email];
		return $this->sendRequest("GetRegistrantVerificationStatus", $ascioParams);
	}

	/**
	 * Update contact details (v3 API)
	 */
	public function updateContacts($params = false) {
		$params = $this->setParams($params);
		$old = $this->searchDomain($params);
		$newRegistrant = $this->mapToContact2($params["contactdetails"]["Registrant"], "Registrant");
		$newAdmin = $this->mapToContact2($params["contactdetails"]["Admin"], "Contact");
		$newTech = $this->mapToContact2($params["contactdetails"]["Technical"], "Contact");

		$updateRegistrant = Tools::compareRegistrant($newRegistrant, $old->Registrant);
		$updateAdmin = Tools::compareContact($newAdmin, $old->AdminContact);
		$updateTech = Tools::compareContact($newTech, $old->TechContact);

		$registrantResult = null;
		$contactResult = null;

		if($updateRegistrant) {
			logActivity("WHMCS v3 Update Registrant");
			try {
				$ascioParams = $this->mapToOrder($params, $updateRegistrant);
			} catch (AscioException $e) {
				return array("error" => $e->getMessage());
			}
			$ascioParams["Order"]["Domain"]["Owner"] = $newRegistrant;
			if($updateAdmin && $updateRegistrant == "OwnerChange") {
				logActivity("WHMCS v3 Owner_Change + Admin_Change");
				$ascioParams["Order"]["Domain"]["Admin"] = $newAdmin;
			}
			$registrantResult = $this->sendRequest("CreateOrder", $ascioParams);
		}

		if($updateTech || ($updateAdmin && $updateRegistrant != "OwnerChange")) {
			logActivity("WHMCS v3 Contact_Update");
			try {
				$ascioParams = $this->mapToOrder($params, "ContactUpdate");
			} catch (AscioException $e) {
				return array("error" => $e->getMessage());
			}
			if($updateAdmin) {
				$ascioParams["Order"]["Domain"]["Admin"] = $newAdmin;
			} else {
				$ascioParams["Order"]["Domain"]["Admin"] = $old->AdminContact;
			}
			if($updateTech) {
				$ascioParams["Order"]["Domain"]["Tech"] = $newTech;
			} else {
				$ascioParams["Order"]["Domain"]["Tech"] = $old->TechContact;
			}
			$ascioParams["Order"]["Domain"]["Billing"] = $old->BillingContact;
			$contactResult = $this->sendRequest("CreateOrder", $ascioParams);
		}

		if(!($contactResult["error"] ?? false) && !($registrantResult["error"] ?? false)) {
			return ["success" => true];
		}

		$errorContactUpdate = isset($contactResult["error"]) ? $contactResult["error"] . ". \n" : "";
		$errorRegistrantUpdate = isset($registrantResult["error"]) ? $registrantResult["error"] . ". \n" : "";
		return ["error" => $errorContactUpdate . $errorRegistrantUpdate];
	}

	// ==========================================
	// MAPPING METHODS (for TLD plugin overrides)
	// ==========================================

	/**
	 * Map WHMCS params to Ascio v3 Order structure
	 * TLD plugins can override this to customize order structure
	 */
	public function mapToOrder($params, $orderType) {

		// Get custom field names
		if (!empty($params["customfields"])) {
			$result = mysql_query("select id,fieldname from tblcustomfields");
			$customFields = [];
			foreach ($params["customfields"] as $key => $value) {
				$customFields[$value["id"]] = $value["value"];
			}
			while ($row = mysql_fetch_assoc($result)) {
				$params["custom"][$row['fieldname']] = $customFields[$row['id']] ?? null;
			}
		}

		$params = $this->setParams($params);
		$domainName = $this->domainName;
		$proxy = ($params["Proxy_Lite"] ?? '') == "on" ? "Privacy" : "Proxy";

		// v3 WSDL property names: Name (not DomainName), RenewPeriod (not RegPeriod), Owner (not Registrant)
		$domain = array(
			'Name' => $domainName,
			'RenewPeriod' => $params["regperiod"] ?? null,
			'AuthInfo' => $params["eppcode"] ?? null,
			'DomainPurpose' => $params["Application Purpose"] ?? null,
			'Comment' => $params["Comment"] ?? null,
			'Owner' => $this->mapToRegistrant($params),
			'Admin' => $this->mapToAdmin($params),
			'Tech' => $this->mapToTech($params),
			'Billing' => $this->mapToBilling($params),
			'NameServers' => $this->mapToNameservers($params),
			'Trademark' => $this->mapToTrademark($params)
		);

		if(in_array($orderType, ["Transfer", "Register", "DetailsUpdate"])) {
			$domain["PrivacyProxy"] = array("Type" => ($params["idprotection"] ?? false) ? $proxy : "None");
		}

		$order = array(
			'Type' => $orderType,
			'TransactionComment' => json_encode(array(
				"application" => "WHMCS",
				"domainId" => $params["domainid"] ?? null,
				"userId" => $params["userid"] ?? null,
				"objectType" => "Domain"
			)),
			'Domain' => $domain,
			'Comments' => $params["userid"] ?? null,
			'Options' => $params["options"] ?? null
		);

		return array('Order' => $order);
	}

	/**
	 * Map WHMCS params to Ascio v3 Registrant
	 * TLD plugins can override this for TLD-specific registrant requirements
	 */
	protected function mapToRegistrant($params) {
		$result = $this->mapToContact($params, "Registrant");
		$result["RegistrantType"] = $params["custom"]["RegistrantType"] ?? null;
		$result["VatNumber"] = $params["custom"]["VatNumber"] ?? null;
		$result["NexusCategory"] = $params["custom"]["NexusCategory"] ?? null;
		$result["RegistrantNumber"] = $params["custom"]["RegistrantNumber"] ?? null;
		$result["Details"] = $params["custom"]["Details"] ?? null;
		return $result;
	}

	/**
	 * Add contact fields common to admin/tech/billing
	 */
	protected function addContactFields($params, $type) {
		$result = $this->mapToContact($params, $type);
		$result["Type"] = $params["custom"]["Type"] ?? null;
		$result["Details"] = $params["custom"]["Details"] ?? null;
		$result["NexusCategory"] = $params["custom"]["NexusCategory"] ?? null;
		$result["OrganisationNumber"] = $params["custom"]["OrganisationNumber"] ?? null;
		return $result;
	}

	/**
	 * Map WHMCS params to Ascio v3 Admin Contact
	 * TLD plugins can override this for TLD-specific admin contact requirements
	 */
	protected function mapToAdmin($params) {
		return $this->addContactFields($params, "Admin");
	}

	/**
	 * Map WHMCS params to Ascio v3 Tech Contact
	 * TLD plugins can override this for TLD-specific tech contact requirements
	 */
	protected function mapToTech($params) {
		return $this->addContactFields($params, "Admin");
	}

	/**
	 * Map WHMCS params to Ascio v3 Billing Contact
	 * TLD plugins can override this for TLD-specific billing contact requirements
	 */
	protected function mapToBilling($params) {
		return $this->addContactFields($params, "Admin");
	}

	/**
	 * Map WHMCS params to Ascio v3 Trademark
	 * TLD plugins can override this for TLD-specific trademark requirements
	 */
	protected function mapToTrademark($params) {
		return null;
	}

	/**
	 * Map WHMCS params to base Contact structure (v3)
	 */
	public function mapToContact($params, $type) {
		$contactName = array();
		$prefix = "";

		if($type == "Registrant") {
			$contactName["FirstName"] = $params["firstname"] ?? null;
			$contactName["LastName"] = $params["lastname"] ?? null;
		} else {
			$prefix = strtolower($type);
			$contactName["FirstName"] = $params[$prefix . "firstname"] ?? null;
			$contactName["LastName"] = $params[$prefix . "lastname"] ?? null;
		}

		$country = $params[$prefix . "country"] ?? null;
		try {
			$contact = Array(
				'OrgName' => Tools::safeTrim($params[$prefix . "companyname"] ?? null),
				'Address1' => Tools::safeTrim($params[$prefix . "address1"] ?? null),
				'Address2' => Tools::safeTrim($params[$prefix . "address2"] ?? null),
				'PostalCode' => Tools::safeTrim($params[$prefix . "postcode"] ?? null),
				'City' => Tools::safeTrim($params[$prefix . "city"] ?? null),
				'State' => Tools::safeTrim($params[$prefix . "state"] ?? null),
				'CountryCode' => $country,
				'Email' => Tools::safeTrim($params[$prefix . "email"] ?? null),
				'Phone' => Tools::fixPhone($params[$prefix . "fullphonenumber"] ?? null, $country),
				'Fax' => Tools::fixPhone($params[$prefix . "custom"]["Fax"] ?? null, $country)
			);
		} catch (AscioException $e) {
			throw new AscioException($type . ", " . $e->getMessage());
		}
		return array_merge($contactName, $contact);
	}

	/**
	 * Map contact details from nested WHMCS structure (v3)
	 */
	public function mapToContact2($params, $type) {
		$ascio = array(
			'OrgName' => Tools::safeTrim($params["Company Name"] ?? null),
			'Address1' => Tools::safeTrim(($params["Address1"] ?? null) ?: ($params["Address 1"] ?? null)),
			'Address2' => Tools::safeTrim(($params["Address2"] ?? null) ?: ($params["Address 2"] ?? null)),
			'PostalCode' => Tools::safeTrim($params["Postcode"] ?? null),
			'City' => Tools::safeTrim($params["City"] ?? null),
			'State' => Tools::safeTrim($params["State"] ?? null),
			'CountryCode' => Tools::safeTrim(($params["Country Code"] ?? null) ?: ($params["Country"] ?? null)),
			'Email' => Tools::safeTrim($params["Email"] ?? null),
			'Phone' => Tools::fixPhone($params["Phone Number"] ?? null, $params["Country"] ?? null),
			'Fax' => Tools::fixPhone($params["Fax Number"] ?? null, $params["Country"] ?? null),
		);

		if($type == "Registrant") {
			$ascio['FirstName'] = Tools::safeTrim($params["First Name"] ?? null);
			$ascio['LastName'] = Tools::safeTrim($params["Last Name"] ?? null);
		} else {
			$ascio['FirstName'] = Tools::safeTrim($params["First Name"] ?? null);
			$ascio['LastName'] = Tools::safeTrim($params["Last Name"] ?? null);
		}
		return (object)$ascio;
	}

	/**
	 * Map contact from Ascio to WHMCS getContactDetails response format
	 * Used for admin/tech/billing contacts
	 */
	public function mapGetContactDetailContact($values, $contact, $type) {
		if ($contact) {
			$values[$type]["First Name"] = $contact->FirstName ?? '';
			$values[$type]["Last Name"] = $contact->LastName ?? '';
			$values[$type]["Company Name"] = $contact->OrgName ?? '';
			$values[$type]["Email"] = $contact->Email ?? '';
			$values[$type]["Phone Number"] = $contact->Phone ?? '';
			$values[$type]["Fax Number"] = $contact->Fax ?? '';
			$values[$type]["Address1"] = $contact->Address1 ?? '';
			$values[$type]["Address2"] = $contact->Address2 ?? '';
			$values[$type]["State"] = $contact->State ?? '';
			$values[$type]["Postcode"] = $contact->PostalCode ?? '';
			$values[$type]["City"] = $contact->City ?? '';
			$values[$type]["Country Code"] = $contact->CountryCode ?? '';
		}
		return $values;
	}

	/**
	 * Map registrant from Ascio to WHMCS getContactDetails response format
	 */
	public function mapGetContactDetailRegistrant($values, $registrant) {
		$name = Tools::splitName($registrant->Name ?? '');
		$values["Registrant"]["First Name"] = $name["first"];
		$values["Registrant"]["Last Name"] = $name["last"];
		$values["Registrant"]["Company Name"] = $registrant->OrgName ?? '';
		$values["Registrant"]["Email"] = $registrant->Email ?? '';
		$values["Registrant"]["Phone Number"] = $registrant->Phone ?? '';
		$values["Registrant"]["Fax Number"] = $registrant->Fax ?? '';
		$values["Registrant"]["Address1"] = $registrant->Address1 ?? '';
		$values["Registrant"]["Address2"] = $registrant->Address2 ?? '';
		$values["Registrant"]["State"] = $registrant->State ?? '';
		$values["Registrant"]["Postcode"] = $registrant->PostalCode ?? '';
		$values["Registrant"]["City"] = $registrant->City ?? '';
		$values["Registrant"]["Country Code"] = $registrant->CountryCode ?? '';
		return $values;
	}

	/**
	 * Map nameservers to v3 structure
	 * TLD plugins can override this for TLD-specific nameserver requirements
	 */
	public function mapToNameservers($params) {
		return array(
			'NameServer1' => Array('HostName' => $params["ns1"] ?? null),
			'NameServer2' => Array('HostName' => $params["ns2"] ?? null),
			'NameServer3' => Array('HostName' => $params["ns3"] ?? null),
			'NameServer4' => Array('HostName' => $params["ns4"] ?? null),
			'NameServer5' => Array('HostName' => $params["ns5"] ?? null)
		);
	}

	// ==========================================
	// STATUS AND HANDLE MANAGEMENT
	// ==========================================

	/**
	 * Check if domain has a specific status
	 */
	protected function hasStatus($domain, $search) {
		if(!$domain || !isset($domain->Status)) return false;
		return stripos($domain->Status, strtoupper($search)) !== false;
	}

	/**
	 * Get domain status for WHMCS
	 */
	public function getDomainStatus($domain) {
		if(!$domain || !isset($domain->Status)) {
			logActivity("WHMCS v3 Domain lookup failed or returned no status, skipping status update (getDomainStatus)");
			return false;
		}
		if($this->hasStatus($domain, "deleted")) {
			logActivity("WHMCS v3 Domain has Status deleted: ".$domain->Status);
			return "Cancelled";
		}
		if($this->hasStatus($domain, "active") ||
		   $this->hasStatus($domain, "expiring") ||
		   $this->hasStatus($domain, "pending_verification") ||
		   $this->hasStatus($domain, "lock")) {
			return "Active";
		}
		if($this->hasStatus($domain, "pending")) {
			return "Pending";
		}
		logActivity("WHMCS v3 Invalid Status: ".($domain->Status ?? 'unknown'));
		return false;
	}

	/**
	 * Set domain status in WHMCS
	 */
	public function setDomainStatus($domain) {
		$status = $this->getDomainStatus($domain);
		if($status) {
			$this->setStatus($domain, $status);
		}
	}

	/**
	 * Update domain status in WHMCS database
	 */
	public function setStatus($domain, $status) {
		if(!$status) return false;
		$values["domainid"] = $this->params["domainid"];

		if(isset($domain->ExpDate) && $domain->ExpDate != "0001-01-01T00:00:00") {
			$expDate = $this->formatDate($domain->ExpDate);
			$creDate = $this->formatDate($domain->CreDate ?? null);
			$domainName = $domain->DomainName ?? $domain->Name ?? $this->domainName ?? null;
			$result = Capsule::select("select Threshold, Renew from tblasciotlds where Tld = '".$this->getTld($domainName)."'")[0] ?? null;

			$hasRenew = $result && $result->Renew == 1;
			$threshold = $result->Threshold ?? 0;

			$dueDate = \DateTime::createFromFormat(\DateTime::ATOM, $domain->ExpDate."-01:00");
			if($dueDate) {
				$dueDate->modify($threshold.' day');
				if(!$this->hasStatus($domain, "expiring") && !$hasRenew) {
					$dueDate->modify('+1 year');
				}
				if(!isset($this->params["Sync_Due_Date"]) || $this->params["Sync_Due_Date"] == "on") {
					$values["nextduedate"] = $dueDate->format('Y-m-d');
				}
			}

			if($expDate) {
				$values["expirydate"] = $expDate;
				$values["registrationdate"] = $creDate;
			}
		}

		$values["status"] = $status;
		logActivity("WHMCS v3 setStatus: ".json_encode($values));
		$results = localAPI("updateclientdomain", $values, Tools::getApiUser());

		// Store detailed Ascio status for visibility
		if (isset($this->params["domainid"])) {
			Capsule::table('tbldomains_extra')
				->updateOrInsert(
					['domain_id' => $this->params["domainid"], 'name' => 'ascio_status'],
					['value' => $status]
				);
		}
	}

	/**
	 * Set order status after operation
	 */
	public function setOrderStatus($result, $defaultStatus = null) {
		if(is_array($result) && isset($result['error'])) return;
		if(!is_object($result) || !isset($result->Order)) return;

		$type = $result->Order->Type ?? null;
		$order = $result->Order;
		$domain = $order->Domain ?? null;
		$pending = strpos($order->Status ?? '', "Pending") !== false || strpos($order->Status ?? '', "NotSet") !== false;

		if(!$domain) {
			logActivity("WHMCS v3 setOrderStatus: Order ".$type." has no Domain object, skipping status update");
			return;
		}

		if($type == "Transfer" && $pending) {
			return $this->setStatus($domain, "Pending Transfer");
		}
		if($type == "Register" || $type == "Transfer") {
			if($pending) {
				$this->setStatus($domain, "Pending");
			} else {
				$this->setDomainStatus($domain);
			}
		}
	}

	/**
	 * Get TLD from domain name
	 */
	private function getTld($domainName) {
		if (!$domainName) return '';
		$tokens = explode(".", $domainName);
		array_shift($tokens);
		return join(".", $tokens);
	}

	/**
	 * Format date from XML datetime to WHMCS format
	 */
	private function formatDate($xsDateTime) {
		if(!$xsDateTime || $xsDateTime == "0001-01-01T00:00:00") return false;
		$dateTokens = explode("T", $xsDateTime);
		if(count($dateTokens) == 2) {
			return str_replace("-", "", $dateTokens[0]);
		}
		return false;
	}

	// ==========================================
	// HANDLE MANAGEMENT
	// ==========================================

	public function getHandle($type, $whmcsId, $domainName) {
		$handleId = Capsule::table('tblasciohandles')
			->where([
				"type" => $type,
				"whmcs_id" => $whmcsId,
				"domain" => $domainName
			])
			->value('ascio_id');

		if(!$handleId) {
			$handleId = Capsule::table('tblasciohandles')
				->where([
					"type" => $type,
					"whmcs_id" => $whmcsId
				])
				->value('ascio_id');
		}
		return $handleId;
	}

	public function setHandle($domain) {
		if(!$domain) return;
		$domainHandle = $domain->DomainHandle ?? $domain->Handle ?? null;
		$domainName = $domain->DomainName ?? null;
		if($domainHandle && $domainName) {
			$this->storeHandle("domain", $this->params["domainid"], $domainHandle, $domainName);
		}
	}

	public function storeHandle($type, $whmcsId, $ascioId, $domain) {
		if(!isset($ascioId)) return;
		$handle = $this->getHandle($type, $whmcsId, $domain);
		if(!$handle) {
			Capsule::table('tblasciohandles')
				->insert([
					"ascio_id" => $ascioId,
					"whmcs_id" => $whmcsId,
					"type" => $type,
					"domain" => $domain
				]);
		} else {
			// Overwrite by (type, whmcs_id, domain) — the same keys getHandle
			// matched on. Keying the UPDATE on ascio_id would target the NEW
			// handle, which does not yet exist, silently updating zero rows and
			// leaving a stale handle behind (e.g. a completed transfer's new
			// registry handle never replacing the old one).
			Capsule::table('tblasciohandles')
				->where([
					"type" => $type,
					"whmcs_id" => $whmcsId,
					"domain" => $domain
				])
				->update([
					"ascio_id" => $ascioId
				]);
		}
	}

	public function deleteOldHandle($whmcsDomainId) {
		Capsule::table('tblasciohandles')->where('whmcs_id', '=', $whmcsDomainId)->delete();
	}
}
} // end if class_exists
?>
