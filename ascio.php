<?php
/*
*
* Ascio Web Service 
* http://aws.request.info
* Author: www.request.com - ml@webender.de
*
*/



//
//  WHMCS functions
//
require_once("lib/Tools.php");
require_once("lib/Request.php");
require_once("lib/RequestV3.php");
require_once("lib/DnsService.php");
require_once("lib/Zone.php");

use WHMCS\Carbon;
use WHMCS\Domain\Registrar\Domain;
use WHMCS\Domains\DomainLookup\ResultsList;
use WHMCS\Domains\DomainLookup\SearchResult;
use WHMCS\Domain\TopLevel\ImportItem;
use WHMCS\Results\ResultsList as PriceResultList;
/**
 * Define module related metadata
 *
 * Provide some module information including the display name and API Version to
 * determine the method of decoding the input values.
 *
 * @return array
 */
function ascio_MetaData()
{
    return array(
        'DisplayName' => 'Ascio Domains',
        'APIVersion' => '1.1',
    );
}
function ascio_getConfigArray() {
	$configarray = array(
	'FriendlyName' => [
		'Type' => 'System',
		'Value' => 'Ascio Domains',
	],
	 "Username" => array( "Type" => "text", "Size" => "20", "Description" => "Enter your username here" ),
	 "Password" => array( "Type" => "password", "Size" => "20", "Description" => "Enter your password here"),	 
	 "TestMode" => array( "Type" => "yesno",  "Description" => "You will need a test-account for this","FriendlyName" =>"Test Mode"),
	 "AutoExpire" => array( "Type" => "yesno", "Size" => "20", "Description" => "Do not use Ascio's auto-renew feature. Let WHMCS handle the renew","FriendlyName" =>"Auto Expire"),
	 "Sync_Due_Date" => array( "Type" => "yesno", "Size" => "20", "Description" => "Sync the due-date with thresholds","Default" => "yes","FriendlyName" =>"Sync Due Date"),
	 "DetailedOrderStatus" => array( "Type" => "yesno", "Size" => "20", "Description" => "Send an detailed order status to the end-customer.", "Default" => "yes","FriendlyName" =>"Detailed order status"),
	 "AutoCreateDNS" => array( "Type" => "yesno", "Size" => "20", "Description" => "Automaticly create a zone in AscioDNS before registering and transfering a domain", "Default" => "no","FriendlyName" =>"Auto create DNS records"),
	  "NameserverRegex" => array( "Type" => "text", "Size" => "20", "Description" => "Only create DNS Zones, when DNS server matches this expression", "Default" => "","FriendlyName" =>"Namerserver Regular Expression"),
	  "DatalessTransfer" => array( "Type" => "yesno", "Size" => "20", "Description" => "Use dataless transfer when Possible", "Default" => "","FriendlyName" =>"Dataless Transfer"),
	 "DNS_Default_Zone" => array( "Type" => "text", "Size" => "20", "Description" => "For AutoCreateDNS: Default IP-address for www and @","FriendlyName" =>"Default A Record"),
	 "DNS_Default_Mailserver" => array( "Type" => "text", "Size" => "20", "Description" => "For AutoCreateDNS: Default IP-address for mx (mail-server)","FriendlyName" =>"Default MX Record"),
	 "DNS_Default_Mailserver_2" => array( "Type" => "text", "Size" => "20", "Description" => "For AutoCreateDNS: Default IP-address for mx2 (backup mail-server)","FriendlyName" =>"Default MX Record 2"),	
	 "Proxy_Lite" => array( "Type" => "yesno",  "Description" => "Privacy. Don't hide the name when using ID-Protection. Only the address-data.","FriendlyName" =>"Use Privacy Proxy"),
	 "MultiBrand_Mode" => array( "Type" => "yesno",  "Description" => "For multiple brands with one account.","FriendlyName" =>"Multi Brand Mode"),
	);
	return $configarray;
}
function ascio_AdminCustomButtonArray() {
    $buttonarray = array(
	 "Update EPP Code" => "UpdateEPPCode",
	 "Autorenew On" => "UnexpireDomain",
	 "Autorenew Off" => "ExpireDomain"
	);
	return $buttonarray;
}
function ascio_ClientAreaCustomButtonArray() {
    $buttonarray = array(
	 "Update EPP Code" => "Expire "
	);
	return $buttonarray;
}
function ascio_DomainSuggestionOptions() {
    return array(
        'tldsToInclude' => array(
            'FriendlyName' => 'Comma separated list of TLDs (.com, .net)',
            'Type' => 'text',
            'Description' => 'Include TLDs',
        ),
    );
}
function ascio_CheckAvailability($params)
{
	try {	
		$request = new Request($params);
		// availability check parameters
		$searchTerm = $params['searchTerm'];
		$tldsToInclude = $params['tldsToInclude'];
		$isIdnDomain = (bool) $params['isIdnDomain'];
		$premiumEnabled = (bool) $params['premiumEnabled'];
		$results = new ResultsList();
		foreach($tldsToInclude as $key => $tld) {
			$result = $request->availabilityInfo($searchTerm . $tld);	
			$searchResult = new SearchResult($searchTerm, $tld);
			$code = $result->AvailabilityInfoResult->ResultCode;	
			if ($code == 200 || $code == 203) {
				$status = SearchResult::STATUS_NOT_REGISTERED;
			} elseif ($code == 201) {
				$status = SearchResult::STATUS_REGISTERED;
			} elseif ($code == 0) {
				$status = SearchResult::STATUS_RESERVED;
			} else {
				
				$status = SearchResult::STATUS_TLD_NOT_SUPPORTED;
			}
			
			// Return premium information if applicable		
			if ( isset($result->PriceInfo->Prices)) {	
				$status = SearchResult::STATUS_NOT_REGISTERED;
				$searchResult->setPremiumDomain(true);
				$searchResult->setPremiumCostPricing(Tools::reformatPrices($result));
			}
			$searchResult->setStatus($status);
			// Append to the search results list
			$results->append($searchResult);			
		}
		return $results;
	}
    catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
		);
	}
	
}
/**
 * Get Domain Suggestions.
 *
 * Provide domain suggestions based on the domain lookup term provided.
 *
 * @param array $params common module parameters
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @see \WHMCS\Domains\DomainLookup\SearchResult
 * @see \WHMCS\Domains\DomainLookup\ResultsList
 *
 * @throws Exception Upon domain suggestions check failure.
 *
 * @return \WHMCS\Domains\DomainLookup\ResultsList An ArrayObject based collection of \WHMCS\Domains\DomainLookup\SearchResult results
 */
function ascio_GetDomainSuggestions($params)
{
	// user defined configuration values

	$request = createRequest($params);
    $searchTerm = $params['searchTerm'];
	$tlds = $params['suggestionSettings']['tldsToInclude'];
	$tldsToInclude = explode(", ",$tlds);
	foreach($tldsToInclude as $key => $tld) {
		$tldsToInclude[$key] = trim($tld,". ");
	}
    $isIdnDomain = (bool) $params['isIdnDomain'];
    $premiumEnabled = (bool) $params['premiumEnabled'];

    try {
        $results = new ResultsList();
		$avResult = $request->availabilityCheck([$searchTerm],$tldsToInclude);
		foreach($avResult->results->AvailabilityCheckResult as $key => $result) {
			$tld = str_replace($searchTerm,"",$result->DomainName);					
			$searchResult = new SearchResult($searchTerm,$tld);
			if($result->StatusCode == 200) {				
				$searchResult->setStatus(SearchResult::STATUS_NOT_REGISTERED);
				$results->append($searchResult);
			} else if($result->StatusCode == 203) {
				$searchResult->setStatus(SearchResult::STATUS_NOT_REGISTERED);				
				$aiResult = $request->availabilityInfo($result->DomainName);
				$searchResult->setPremiumDomain(true);								
				if(isset($aiResult->PriceInfo)) {
					$searchResult->setPremiumCostPricing(Tools::reformatPrices($aiResult));
				}				
				$results->append($searchResult);
			}
		}
        return $results;
    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}


function ascio_GetNameservers($params) {	
	$request = createRequest($params);	
	$domain = $request->searchDomain(); 
	if (is_array($domain)) return $domain;
	$ns = $domain->NameServers;

	# Put your code to get the nameservers here and return the values below
	$values["ns1"] = $ns->NameServer1->HostName;
	$values["ns2"] = $ns->NameServer2->HostName;
	$values["ns3"] = $ns->NameServer3->HostName;
	$values["ns4"] = $ns->NameServer4->HostName;
	$values["ns5"] = $ns->NameServer5->HostName;
	return $values;
}
function ascio_SaveNameservers($params) {
	$request = createRequest($params);
	$result =  $request->saveNameservers($params);
	// has error?
	if(is_array($result)) {
		return $result;
	} else {
		return array(
            'success' => true,
        );
	}
}
function mapNameservers($ascioNameServers) {
	$nameservers = [];
	foreach($ascioNameServers as $key => $ascioNameserver) {
		$nameservers[] =  $ascioNameserver->HostName;
	}
	return $nameservers;
}
function ascio_GetDomainInformation($params) {
	$request = createRequest($params);
	$domain = $request->searchDomain();
	if (is_array($domain)) $domain = $domain[0];
	$expDate = Carbon::createFromFormat('Y-m-d', Tools::dateFromXsDateTime($domain->ExpDate));
	$irtpTransferLockExpiryDate = Carbon::createFromFormat('Y-m-d', Tools::dateFromXsDateTime($domain->CreDate));
	$irtpTransferLockExpiryDate->addDays(90);
	$request = createRequest($params);
	$rvInfo = $request->getRegistrantVerificationInfo($domain->Registrant->Email);
	Tools::setVerificationStatus($params["domainid"], $rvInfo);
	$result = (new Domain)
        ->setDomain($domain->DomainName)
        ->setNameservers(mapNameservers($domain->Nameservers))
        ->setRegistrationStatus($request->getDomainRegistrarStatus($domain))
        ->setExpiryDate($expDate) // $response['expirydate'] = YYYY-MM-DD
        ->setRestorable(true)
        ->setIdProtectionStatus($domain->PrivacyProxy->Type == "Proxy" || $domain->PrivacyProxy->Type=="Privacy")
        ->setDnsManagementStatus($params['dnsmanagement'])
        ->setEmailForwardingStatus($params['emailforwarding'])
        ->setIsIrtpEnabled(Tools::isIcannTld($domain->DomainName))
        ->setIrtpOptOutStatus(false)
        ->setIrtpTransferLock(true)
        ->setIrtpTransferLockExpiryDate($irtpTransferLockExpiryDate)
        ->setRegistrantEmailAddress($domain->Registrant->Email)
        ->setIrtpVerificationTriggerFields(
            [
                'Registrant' => [
                    'First Name',
                    'Last Name',
                    'Organization Name',
                    'Email',
                ],
            ]
        );

	return $result; 
}
/**
 * Admin Domains Tab Fields
 * @param array $param::Data from WHMCS
 * @return array::Admin domain tab fields
 */
function ascio_AdminDomainsTabFields($params){
	$status = Tools::getVerificationStatus($params["domainid"]);
	$outRows = ""; 
	$translation = [
		"last_from_address" => "Last mail sent from",
		"last_to_address" =>  "Verification Email",
		"last_try_date" => "Last mail sent",
		"verified_by" => "IP Address of the Client when verifying",
		"verified_date" => "Verified at date"
	];
	foreach($status as $message) {
		if($translation[$message->name]) {
			$outRows .= '<tr>
				<td>'. $translation[$message->name]. '</td><td>' . $message->value . '</td>
			</tr>';
		}
	}
	
    # Return output
    return [
		"Registrant Verification" => '<table><tbody>' . $outRows. '</tbody></table>'
	]; 
}

function ascio_ResendIRTPVerificationEmail(array $params) {
	// Perform API call to initiate resending of the IRTP Verification Email
	$request = createRequest($params);
	$result = $request->doRegistrantVerification($params);

	if ($result->ResultCode == 1) {
		return ['success' => true];
	} else {
		return ['error' => "Could not send registrant verification email."];
	}
}
function ascio_GetRegistrarLock($params) {
	$request = createRequest($params);
	$domain = $request->searchDomain();
	$status = $domain->Status;

	if (strpos($status,"TRANSFER_LOCK")===false) {
		$lockstatus="unlocked";
	} else {
		$lockstatus="locked";
	}
	return $lockstatus;
}

function ascio_saveRegistrarLock($params) {
	$request = createRequest($params);
	$result = $request->saveRegistrarLock();
	// has error?
	if(is_array($result)) {
		return $result;
	} else {
		return array(
			'success' => true,
		);
	} 
}
function ascio_IDProtectToggle($params) {
	$params["idprotection"] = $params["protectenable"] == 1 ? true : false;
	$request = createRequest($params);
	return $request->updateDomain();
}

function ascio_GetEmailForwarding($params) {
	$request = createRequest($params);
	# Put your code to get email forwarding here - the result should be an array of prefixes and forward to emails (max 10)
	foreach ($result as $value) {
		$values[$counter]["prefix"] = $value["prefix"];
		$values[$counter]["forwardto"] = $value["forwardto"];
	}
	return $values;
}

function ascio_SaveEmailForwarding($params) {
	$request = createRequest($params);
	foreach ($params["prefix"] AS $key=>$value) {
		$forwardarray[$key]["prefix"] =  $params["prefix"][$key];
		$forwardarray[$key]["forwardto"] =  $params["forwardto"][$key];
	}
	# Put your code to save email forwarders here
}

function ascio_GetDNS($params) {
	$zone = new DnsZone($params);
	$result =  $zone->convertToWhmcs($zone->get());
	return $result;
}
function ascio_SaveDNS($params) {		
	$zone = new DnsZone($params);
	$result = $zone->update($params);
}
function ascio_RegisterDomain($params) {
	$request = createRequest($params);
	$result =  $request->registerDomain($params); 
	// has error?
	if(is_array($result)) {
		return $result;
	} else {
		return array(
            'success' => true,
        );
	}
}

function ascio_TransferDomain($params) {
	$request = createRequest($params);
	$result =   $request->transferDomain($params); 
	// has error?
	if(is_array($result)) {
		return $result;
	} else {
		return array(
            'success' => true,
        );
	} 
}

function ascio_RenewDomain($params) {
	$request = createRequest($params);
	$result =  $request->renewDomain($params); 
	// has error?
	if(is_array($result)) {
		return $result;
	} else {
		return array(
            'success' => true,
        );
	} 
}

function ascio_ExpireDomain($params) {
	$request = createRequest($params);
	$result =   $request->expireDomain($params); 
	// has error?
	if(is_array($result)) {
		return $result;
	} else {
		return array(
			'success' => true,
		);
	} 
}
function ascio_UnexpireDomain($params) {
	$request = createRequest($params);
	$result =  $request->unexpireDomain($params); 
	// has error?
	if(is_array($result)) {
		return $result;
	} else {
		return array(
			'success' => true,
		);
	} 
}

function ascio_GetContactDetails($params) {
	$request = createRequest($params);
	$result = $request->searchDomain();
	$values = $request->mapGetContactDetailRegistrant([],$result->Registrant);
	$values = $request->mapGetContactDetailContact($values,$result->AdminContact,"Admin");
	$values = $request->mapGetContactDetailContact($values,$result->TechContact,"Technical");
	$values = $request->mapGetContactDetailContact($values,$result->BillingContact,"Billing");
	return $values;
}

function ascio_SaveContactDetails($params) {
	$request = createRequest($params);
	$result =   $request->updateContacts($params);
	// has error?
	if(is_array($result)) {
		return $result;
	} else {
		return array(
			'success' => true,
		);
	}   
	
}

function ascio_GetEPPCode($params) {
	$request = createRequest($params);	
	$params = $request->getEPPCode($params);
	return $params;
}
function ascio_UpdateEPPCode($params) {
	$request = createRequest($params);	
	$result = $request->updateEPPCode($params);
	// has error?
	if(is_array($result)) {
		return $result;
	} else {
		return array(
			'success' => true,
		);
	} 
}

function ascio_ModifyNameserver($params) {
	$request = createRequest($params);
    $nameserver = $params["nameserver"];
    $currentipaddress = $params["currentipaddress"];
    $newipaddress = $params["newipaddress"];
    # If error, return the error message in the value below
    $values["error"] = $error;
    //Nameserver_Update
    return $values;
}

function ascio_DeleteNameserver($params) {
    return  ["error" => "Operation not allowed"];
}
// this function is not needed if you have polling or callbacks

function ascio_Sync($params) {
	$request = createRequest($params);
	$domain = $request->searchDomain($params);
	echo "Syncing ". $params["sld"].".".$params["tld"]. " :".$domain->Status. "\n";
	if(!$domain) return array("error" => "Domain ".$params["sld"].".".$params["tld"]." not found.");
	$d = new DateTime($domain->ExpDate);
	$values["expirydate"] = $d->format("Y-m-d");
	$values["active"] = $request->getDomainStatus($domain);
	syslog(LOG_INFO, "Syncing ". $params["sld"].".".$params["tld"]);
	
	return $values;
}

function ascio_GetTldPricing(array $params)
{
    // Perform API call to retrieve extension information
    // A connection error should return a simple array with error key and message
    // return ['error' => 'This error occurred',];
	$command = 'GetTLDPricing';
	$results = localAPI($command);
	$tlds = array_keys($results["pricing"]);	
	$now = new DateTime();
	$pageInfo =  [
		"PageIndex" => 1,
		"PageSize" => 5000
	];
	$ascioParams =  [
		"Date" => $now->format('Y-m-d\TH:i:s'),
		"ObjectTypes" => ["DomainType"],
		"OrderTypes" => [
			"Register", 
			"Renew", 
			"Restore", 
			"Transfer"		
		],
		"Tlds" => $tlds,
		"PageInfo" => $pageInfo
	];
	$request = new RequestV3($params);
	$result = $request->getPrices($ascioParams);
	$currency = $result->Currency;
	$tlds = extractPeriods($result->Prices->PriceInfo); 
    $results = new PriceResultList;
    foreach ($tlds as $tld =>  $extension) {
        // All the set methods can be chained and utilised together.
		$item = (new ImportItem)
            ->setExtension($tld)
            ->setMinYears(min($extension["Period"]) > 0 ? min($extension["Period"])  : 1)
            ->setMaxYears(max($extension["Period"]))
            ->setRegisterPrice($extension['OrderType']['Register'])
            ->setRenewPrice($extension['OrderType']['Renew'])
            ->setTransferPrice($extension['OrderType']['Transfer'])
            ->setRedemptionFeeDays(30)
            ->setRedemptionFeePrice($extension['OrderType']['Restore'])
            ->setCurrency($currency)
            ->setEppRequired(true);
        $results[] = $item;
    }   
	return $results;
}
function extractPeriods($list) {
	$tlds = [];
	foreach($list as $entry) {
		$product = $entry->Product;
		//var_dump($product->Tld); 
		//die();
		$tlds[$product->Tld]["Period"][] = $product->Period;
		if(
			$product->Period == 1 || 
			$product->OrderType == "Restore" ||
			($product->OrderType == "Transfer" && $product->Period == 0)
		) {
			$tlds[$product->Tld]["OrderType"][$product->OrderType] = $entry->Price;
		}
 	}
	return $tlds; 
}
?>
