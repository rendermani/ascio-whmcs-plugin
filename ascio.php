<?php
/*
*
* Ascio Web Service 
* http://aws.request.info
* Author: www.request.com - ml@webender.de
*
*/

use WHMCS\Carbon;
use WHMCS\Domain\Registrar\Domain;
use WHMCS\Domains\DomainLookup\ResultsList;
use WHMCS\Domains\DomainLookup\SearchResult;
use WHMCS\Domain\TopLevel\ImportItem;
use WHMCS\Results\ResultsList as PriceResultList;
use Illuminate\Database\Capsule\Manager as Capsule;
use ascio\Request;
use ascio\dns\DnsZone as DnsZone;
use ascio\Tools as Tools;

//
//  WHMCS functions
//
require_once("lib/Tools.php");
require_once("lib/Request.php");
require_once("lib/DnsService.php");
require_once("lib/Zone.php");

/**
 * Get the Request class instance for Ascio v3 API
 *
 * @param array $params Module parameters from WHMCS
 * @param string|null $operation Optional operation name for logging
 * @return Request The request class instance
 */
function ascio_getRequestClass($params, $operation = null) {
	if ($operation) {
		logActivity("Ascio: API operation: {$operation}");
	}
	return new Request($params);
}

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
        'SupportsV3Api' => true,
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
	 "AutoExpire" => array( "Type" => "yesno", "Size" => "20", "Description" => "ON: Expire domains immediately after register/transfer (prevents auto-renewal). OFF: Expire at threshold date only if unpaid (recommended for WHMCS-managed billing)","FriendlyName" =>"Auto Expire"),
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
	 "Autorenew Off" => "ExpireDomain",
	 "Delete Domain" => "DeleteDomain",
	 "Restore Domain" => "RestoreDomain",
	 "Cancel Order" => "CancelOrder",
	 "Change Owner" => "ChangeOwner",
	 "Update Domain Details" => "UpdateDomainDetails"
	);
	return $buttonarray;
}
function ascio_ClientAreaCustomButtonArray() {
    $buttonarray = array(
	 "View Contact Details" => "ClientViewContactDetails",
	 "Update Contact Details" => "ClientUpdateContactDetails",
	 "Check Lock Status" => "ClientGetLockStatus",
	 "View EPP Code" => "ViewEPPCode",
	 "Update EPP Code" => "UpdateEPPCode",
	 "Request IRTP Verification" => "ClientRequestIRTP",
	 "View Nameservers" => "ClientViewNameservers",
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
		$request = ascio_getRequestClass($params, 'CheckAvailability');
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

	$request = ascio_getRequestClass($params, 'GetDomainSuggestions');
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
	$request = ascio_getRequestClass($params, 'GetNameservers');
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
	$request = ascio_getRequestClass($params, 'SaveNameservers');
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
	$request = ascio_getRequestClass($params, 'GetDomainInformation');
	$domain = $request->searchDomain();
	if (is_array($domain)) $domain = $domain[0];
	$expDate = Carbon::createFromFormat('Y-m-d', Tools::dateFromXsDateTime($domain->ExpDate));
	$irtpTransferLockExpiryDate = Carbon::createFromFormat('Y-m-d', Tools::dateFromXsDateTime($domain->CreDate));
	$irtpTransferLockExpiryDate->addDays(90);
	$request = ascio_getRequestClass($params, 'GetRegistrantVerificationInfo');
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

	// Get Ascio order status from tbldomains_extra
	$ascioData = Capsule::table('tbldomains_extra')
		->where('domain_id', $params["domainid"])
		->whereIn('name', ['ascio_order_status', 'ascio_order_type', 'ascio_order_id', 'ascio_status', 'ascio_status_updated'])
		->pluck('value', 'name');

	$orderStatusRows = "";
	$statusLabels = [
		'ascio_order_status' => 'Order Status',
		'ascio_order_type' => 'Order Type',
		'ascio_order_id' => 'Order ID',
		'ascio_status' => 'Domain Status (Ascio)',
		'ascio_status_updated' => 'Last Updated',
	];
	foreach ($statusLabels as $key => $label) {
		if (isset($ascioData[$key]) && $ascioData[$key]) {
			$value = htmlspecialchars($ascioData[$key]);
			$orderStatusRows .= "<tr><td>{$label}</td><td>{$value}</td></tr>";
		}
	}

    # Return output
    return [
		"Registrant Verification" => '<table><tbody>' . $outRows. '</tbody></table>',
		"Ascio Order Status" => '<table><tbody>' . $orderStatusRows . '</tbody></table>',
	];
}
function ascio_ResendIRTPVerificationEmail(array $params) {
	// Perform API call to initiate resending of the IRTP Verification Email
	// no idea where this is triggered yet
	$request = ascio_getRequestClass($params, 'ResendIRTPVerificationEmail');
	$result = $request->doRegistrantVerification($params);
	if ($result->ResultCode == 1) {
		return ['success' => true];
	} else {
		return ['error' => "Could not send registrant verification email."];
	}
}
function ascio_GetRegistrarLock($params) {
	$request = ascio_getRequestClass($params, 'GetRegistrarLock');
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
	$request = ascio_getRequestClass($params, 'SaveRegistrarLock');
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
	$request = ascio_getRequestClass($params, 'IDProtectToggle');
	return $request->updateDomain();
}

function ascio_GetEmailForwarding($params) {
	$request = ascio_getRequestClass($params, 'GetEmailForwarding');
	# Put your code to get email forwarding here - the result should be an array of prefixes and forward to emails (max 10)
	foreach ($result as $value) {
		$values[$counter]["prefix"] = $value["prefix"];
		$values[$counter]["forwardto"] = $value["forwardto"];
	}
	return $values;
}

function ascio_SaveEmailForwarding($params) {
	$request = ascio_getRequestClass($params, 'SaveEmailForwarding');
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
	$request = ascio_getRequestClass($params, 'RegisterDomain');
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
	$request = ascio_getRequestClass($params, 'TransferDomain');
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
	$request = ascio_getRequestClass($params, 'RenewDomain');
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
	$request = ascio_getRequestClass($params, 'ExpireDomain');
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
	$request = ascio_getRequestClass($params, 'UnexpireDomain');
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

function ascio_DeleteDomain($params) {
	$request = ascio_getRequestClass($params, 'DeleteDomain');
	$result = $request->deleteDomain($params);
	// has error?
	if(is_array($result)) {
		return $result;
	} else {
		return array(
			'success' => true,
		);
	}
}

function ascio_RestoreDomain($params) {
	$request = ascio_getRequestClass($params, 'RestoreDomain');
	$result = $request->restoreDomain($params);
	// has error?
	if(is_array($result)) {
		return $result;
	} else {
		return array(
			'success' => true,
		);
	}
}

function ascio_CancelOrder($params) {
	$request = ascio_getRequestClass($params, 'CancelOrder');
	$result = $request->cancelOrder($params);
	// has error?
	if(is_array($result)) {
		return $result;
	} else {
		return array(
			'success' => true,
		);
	}
}

function ascio_ChangeOwner($params) {
	$request = ascio_getRequestClass($params, 'ChangeOwner');
	$result = $request->changeOwner($params);
	// has error?
	if(is_array($result)) {
		return $result;
	} else {
		return array(
			'success' => true,
		);
	}
}

function ascio_UpdateDomainDetails($params) {
	$request = ascio_getRequestClass($params, 'UpdateDomainDetails');
	$result = $request->updateDomainDetails($params);
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
	$request = ascio_getRequestClass($params, 'GetContactDetails');
	$result = $request->searchDomain();
	$values = $request->mapGetContactDetailRegistrant([],$result->Registrant);
	$values = $request->mapGetContactDetailContact($values,$result->AdminContact,"Admin");
	$values = $request->mapGetContactDetailContact($values,$result->TechContact,"Technical");
	$values = $request->mapGetContactDetailContact($values,$result->BillingContact,"Billing");
	return $values;
}

function ascio_SaveContactDetails($params) {
	$request = ascio_getRequestClass($params, 'SaveContactDetails');
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
	$request = ascio_getRequestClass($params, 'GetEPPCode');
	$params = $request->getEPPCode($params);
	return $params;
}
function ascio_UpdateEPPCode($params) {
	$request = ascio_getRequestClass($params, 'UpdateEPPCode');
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

/**
 * Client Area: View EPP Code
 * Retrieves the current EPP/Auth code without regenerating it
 *
 * @param array $params Module parameters from WHMCS
 * @return array Success with EPP code or error
 */
function ascio_ViewEPPCode($params) {
	$request = ascio_getRequestClass($params, 'ViewEPPCode');
	$result = $request->getEPPCode($params);
	// has error?
	if(isset($result['error'])) {
		return $result;
	}
	$eppCode = $result['eppcode'] ?? '';
	if(empty($eppCode)) {
		return array(
			'error' => 'No EPP code found for this domain. You may need to request a new one using "Update EPP Code".',
		);
	}
	return array(
		'success' => true,
		'eppcode' => $eppCode,
	);
}

/**
 * Client Area: View Contact Details
 * Wrapper for GetContactDetails that formats output for client area display
 *
 * @param array $params Module parameters from WHMCS
 * @return array Contact details or error
 */
function ascio_ClientViewContactDetails($params) {
	return ascio_GetContactDetails($params);
}

/**
 * Client Area: Update Contact Details
 * Wrapper for SaveContactDetails for client area button
 *
 * @param array $params Module parameters from WHMCS
 * @return array Success or error
 */
function ascio_ClientUpdateContactDetails($params) {
	return ascio_SaveContactDetails($params);
}

/**
 * Client Area: Get Lock Status
 * Wrapper for GetRegistrarLock that formats output for client area display
 *
 * @param array $params Module parameters from WHMCS
 * @return array Lock status information or error
 */
function ascio_ClientGetLockStatus($params) {
	$lockstatus = ascio_GetRegistrarLock($params);
	// Check if error was returned
	if(is_array($lockstatus) && isset($lockstatus['error'])) {
		return $lockstatus;
	}
	return array(
		'success' => true,
		'lockstatus' => $lockstatus,
	);
}

/**
 * Client Area: Request IRTP Verification
 * Wrapper for ResendIRTPVerificationEmail for client area button
 *
 * @param array $params Module parameters from WHMCS
 * @return array Success or error
 */
function ascio_ClientRequestIRTP($params) {
	return ascio_ResendIRTPVerificationEmail($params);
}

/**
 * Client Area: View Nameservers
 * Wrapper for GetNameservers that formats output for client area display
 *
 * @param array $params Module parameters from WHMCS
 * @return array Nameserver information or error
 */
function ascio_ClientViewNameservers($params) {
	$result = ascio_GetNameservers($params);
	// Check if error was returned
	if(isset($result['error'])) {
		return $result;
	}
	return array(
		'success' => true,
		'ns1' => $result['ns1'] ?? '',
		'ns2' => $result['ns2'] ?? '',
		'ns3' => $result['ns3'] ?? '',
		'ns4' => $result['ns4'] ?? '',
		'ns5' => $result['ns5'] ?? '',
	);
}

function ascio_ModifyNameserver($params) {
	$request = ascio_getRequestClass($params, 'ModifyNameserver');
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
	$request = ascio_getRequestClass($params, 'Sync');
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
	// GetTldPricing always uses V3 API as it's only available in V3
	logActivity("Ascio: Using API v3 for operation: GetTldPricing (V3-only feature)");
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
