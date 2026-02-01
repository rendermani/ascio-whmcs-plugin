<?php
require_once("v3/service/autoload.php");
require_once("lib/Contacts.php");
require_once("lib/Ssl.php");
require_once("lib/Sans.php");
require_once("lib/Error.php");
require_once("lib/Params.php");
use ascio\whmcs\ssl as ssl;
use ascio\v3 as ascio;

use Illuminate\Database\Capsule\Manager as Capsule;
/**
 * WHMCS SDK Sample Provisioning Module
 *
 * Provisioning Modules, also referred to as Product or Server Modules, allow
 * you to create modules that allow for the provisioning and management of
 * products and services in WHMCS.
 *
 * This sample file demonstrates how a provisioning module for WHMCS should be
 * structured and exercises all supported functionality.
 *
 * Provisioning Modules are stored in the /modules/servers/ directory. The
 * module name you choose must be unique, and should be all lowercase,
 * containing only letters & numbers, always starting with a letter.
 *
 * Within the module itself, all functions must be prefixed with the module
 * filename, followed by an underscore, and then the function name. For this
 * example file, the filename is "asciossl" and therefore all
 * functions begin "asciossl_".
 *
 * If your module or third party API does not support a given function, you
 * should not define that function within your module. Only the _ConfigOptions
 * function is required.
 *
 * For more information, please refer to the online documentation.
 *
 * @see http://docs.whmcs.com/Provisioning_Module_Developer_Docs
 *
 * @copyright Copyright (c) WHMCS Limited 2015
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

// Require any libraries needed for the module to function.
// require_once __DIR__ . '/path/to/library/loader.php';
//
// Also, perform any initialization required by the service's library.

/**
 * Define module related meta data.
 *
 * Values returned here are used to determine module related abilities and
 * settings.
 *
 * @see http://docs.whmcs.com/Provisioning_Module_Meta_Data_Parameters
 *
 * @return array
 */
function asciossl_MetaData()
{
    return array(
        'DisplayName' => 'Ascio SSL module',
        'APIVersion' => '1.1', // Use API Version 1.1
        'RequiresServer' => false, // Set true if module requires a server to work
        'DefaultNonSSLPort' => '1111', // Default Non-SSL Connection Port
        'DefaultSSLPort' => '1112', // Default SSL Connection Port
        'ServiceSingleSignOnLabel' => 'Login to Panel as User',
        'AdminSingleSignOnLabel' => 'Login to Panel as Admin',
    );
}

/**
 * Define product configuration options.
 *
 * The values you return here define the configuration options that are
 * presented to a user when configuring a product for use with the module. These
 * values are then made available in all module function calls with the key name
 * configoptionX - with X being the index number of the field from 1 to 24.
 *
 * You can specify up to 24 parameters, with field types:
 * * text
 * * password
 * * yesno
 * * dropdown
 * * radio
 * * textarea
 *
 * Examples of each and their possible configuration parameters are provided in
 * this sample function.
 *
 * @return array
 */
function asciossl_ConfigOptions()
{
    return array(
        'CertificateType' => array(
        	'Type' => 'dropdown',
        	'Options' => array(
        		 "quicksslpremium" => "GeoTrust QuickSSL Premium Certificate",
                 "quicksslpremiummd" => "GeoTrust QuickSSL Premium Multi Domain Certificate",
                 "truebizid" => "GeoTrust True BusinessID Certificate",
                 "truebusinessidevmd" => "Geo Trust True Business ID With EV Multi-Domain",
                 "truebusinessidwildcard" => "GeoTrust True BusinessID Wildcard Certificate",
                 "truebusinessidev" => "GeoTrust True BusinessID with EV ",
                 "truebizidmd" => "GeoTrust True BusinessID with Multi-Domain",
                 "malwarescan" => "GeoTrust Web Site Anti-Malware Scan",
                 "thawtecsc" => "Thawte Code Signing Certificate",
                 "thawtecscind" => "Thawte Code Signing Individual Certificate",
                 "sslwebserver" => "thawte SSL Web Server Certificates",
                 "ssl123" => "Thawte SSL123 Certificates",
                 "sslwebserverev" => "Thawte SSLWebserver EV ",
                 "sslwebserverwildcard" => "Thawte Wildcard SSL Certificate",
                 "verisigncsc" => "Symantec Code Signing",
                 "verisigncscind" => "Symantec Code Signing Individual",
                 "securesitewildcard" => "Symantec Secure Site Wildcard",
                 "trustsealorg" => "Symantec Trust Seal",
                 "securesite" => "Symantec Secure Site",
                 "securesitepro" => "Symantec Secure Site Pro",
                 "securesiteproev" => "Symantec Secure Site Pro with EV ",
                 "securesiteev" => "Symantec Secure Site with EV ",
                 "freessl" => "Free SSL Certificate",
                 "rapidssl" => "RapidSSL Certificate",
                 "rapidsslwildcard" => "RapidSSL Wildcard Certificate",
                 "comodocsc" => "Comodo Code Signing Certificate",
                 "comododvucc" => "Domain Validated UCC SSL",
                 "elitessl" => "Comodo Elite SSL",
                 "essentialssl" => "Comodo Essential SSL Certificate",
                 "essentialwildcard" => "Comodo EssentialSSL Wildcard Certificate",
                 "comodoevmdc" => "Comodo EV Multi-Domain SSL Certificate",
                 "comodoevsgc" => "Comodo EV SGC SSL Certificate",
                 "comodoevssl" => "Comodo EV SSL Certificate",
                 "hgpcicontrolscan" => "Comodo HackerGuardian PCI Scan Control Center",
                 "hackerprooftm" => "Comodo HackerProof Trust Mark including Daily Vulnerability Scan",
                 "instantssl" => "Comodo InstantSSL Certificate",
                 "comodopremiumssl" => "Comodo Premium SSL Certificate",
                 "instantsslpro" => "Comodo InstantSSL Pro Certificate",
                 "comodomdc" => "Comodo Multi-Domain SSL Certificate",
                 "comodomdcwildcard" => "Comodo Multi-Domain Wildcard SSL Certificate",
                 "comodopciscan" => "Comodo PCI Scanning Enterprise Edition",
                 "positivemdcssl" => "PositiveSSL Multi-Domain Certificate",
                 "positivemdcwildcard" => "PositiveSSL Multi-Domain Wildcard Certificate",
                 "positivessl" => "Comodo PositiveSSL Certificate",
                 "positivesslwildcard" => "Comodo PositiveSSL Wildcard Certificate",
                 "comodopremiumwildcard" => "Comodo PremiumSSL Wildcard Certificate",
                 "comodosgc" => "Comodo SGC SSL Certificate",
                 "comodosgcwildcard" => "Comodo SGC SSL Wildcard Certificate",
                 "comodossl" => "Comodo SSL Certificate",
                 "comodoucc" => "Comodo Unified Communications Certificate",
                 "comodowildcard" => "Comodo Wildcard SSL Certificate",
                 "comodouccwildcard" => "Comodo Unified Communications Wildcard Certificate",
                 "webinsnterprise" => "Web Inspector Enterprise",
                 "webinsplus" => "Web Inspector Plus",
                 "webinspremium" => "Web Inspector Premium ",
                 "webinsbasic" => "Web Inspector Starter",
                 "ubasicid" => "CERTUM Basic ID Certificate",
                 "ucommercialssl" => "Certum Commercial SSL ",
                 "ucommercialwildcard" => "CERTUM Commercial SSL WildCard Certificate",
                 "uenterpriseid" => "CERTUM Enterprise ID Certificate",
                 "uprofessionalid" => "CERTUM Professional ID Certificate",
                 "utrustedssl" => "CERTUM Trusted SSL Certificate",
                 "utrustedwildcard" => "CERTUM Trusted SSL Wildcard Certificate"
            ),                 
        )
    );
}
function asciossl_updateOrder($params) {
    // switch updateOrder (AutoInstallSsl)
    if( $params["customfields"]["AutoInstallSsl"])  return;
    try {
		// setup client
        // TODO add new credentials with params
        $user = $params["configoption1"];
        $password = $params["configoption2"];
        $testmode = $params["configoption3"]=="on" ? true : false;
        $wsdl = $testmode ? "https://aws.demo.ascio.com/v3/aws.wsdl" : "https://aws.ascio.com/v3/aws.wsdl";	
    	$header = new SoapHeader('http://www.ascio.com/2013/02','SecurityHeaderDetails', array('Account'=> $user, 'Password'=>$password), false);
        $ascioClient     = new ascio\AscioService(array("trace" => true, "encoding" => "ISO-8859-1"),$wsdl);
		$ascioClient->__setSoapHeaders($header);
    	// get database data
    	$result = mysql_query("select id,remoteid,status from tblsslorders where serviceid='".$params["serviceid"]."'");
    	$sslOrderData = mysql_fetch_assoc($result);   	

        $result       = select_query("mod_asciossl","order_id,certificate_id,token",array("id"=>$sslOrderData["id"]));
        $ascioResult = mysql_fetch_array($result);

        $orderId = $sslOrderData["remoteid"];
    	$token   = $ascioResult["token"];
    	$certificateId   = $ascioResult["certificate_id"];
    	$completed = array("Completed","Failed","Invalid","Order not validated");     	
    	$orderStatus = $sslOrderData["status"];
    	if(array_search($completed,$orderStatus)) {
			$certificateId = $ascioResult["certificate_id"];
    	} else {
			// get order - write tblsslorders
			$orderRequest = new ascio\GetOrderRequest();			
            $orderRequest->setOrderId($orderId);
            $response = $ascioClient->GetOrder(new ascio\GetOrder($orderRequest));
            if(!$response->GetOrderResult->GetOrderInfo()) return;
			$certificateId = $response->GetOrderResult->GetOrderInfo()->getOrderRequest()->GetAutoInstallSsl()->getHandle();
			$orderStatus = $response->GetOrderResult->GetOrderInfo()->GetStatus();				
			update_query("tblsslorders",array("certificate_id" => $certificateId, "status" => $orderStatus),array("id" => $sslOrderData["id"] ));
			// get certificate - write mod_asciossl
			$getSslCertificateRequest = new ascio\GetAutoInstallSslRequest();
			$getSslCertificateRequest->setHandle($certificateId);
			$response = $ascioClient->GetAutoInstallSsl(new ascio\GetAutoInstallSsl($getSslCertificateRequest));
			$result = $response->GetAutoInstallSslResult->GetAutoInstallSslInfo();
			if(!$result) return;
            $token = $result->getToken();
			$status = $result->getStatus();				
			update_query("mod_asciossl",array("status" => $status,"token" => $token), array("id" => $sslOrderData["id"] ));
		}


		
        // Call the service's function, using the values provided by WHMCS in
        // `$params`.
        $response = array();

        // Return an array based on the function's response.
        return array(
            'AutoInstallSsl Token' => $token,
            'Ascio Order ID' => $orderId,
            'Ascio Certificate ID' => $certificateId,
            'Status' => $orderStatus
        );
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'asciossl',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        // In an error condition, simply return no additional fields to display.
    }

    return array();
}
/**
 * Provision a new instance of a product/service.
 *
 * Attempt to provision a new instance of a given product/service. This is
 * called any time provisioning is requested inside of WHMCS. Depending upon the
 * configuration, this can be any of:
 * * When a new order is placed
 * * When an invoice for a new order is paid
 * * Upon manual request by an admin user
 *
 * @param array $params common module parameters
 *
 * @see http://docs.whmcs.com/Provisioning_Module_SDK_Parameters
 *
 * @return string "success" or an error message
 */
function asciossl_CreateAccount(array $params)
{
    // No AutoInstalSSL
    if( $params["customfields"]["AutoInstallSsl"] !=="on")  return;
    // AutoInstalSSL
    try {
        // TODO add new credentials with params
        $user = $params["configoption1"];
		$password = $params["configoption2"];
        $testmode = $params["configoption3"]=="on" ? true : false;
        $certtype = $params["configoption4"];
        $certyears = $params["configoptions"]["Registration Period (Years)"];
        $domainName = $params["customfields"]["DomainName"] ? $params["customfields"]["DomainName"] :uniqid("WHMCS-SSL-Token-");
        $wsdl = $testmode ? "https://aws.demo.ascio.com/v3/aws.wsdl" : "https://aws.ascio.com/v3/aws.wsdl";  
		$header = new SoapHeader('http://www.ascio.com/2013/02','SecurityHeaderDetails', array('Account'=> $user, 'Password'=>$password), false);
        $ascioClient     = new ascio\AscioService(array("trace" => true, "encoding" => "ISO-8859-1"),$wsdl);
		$ascioClient->__setSoapHeaders($header);
		$orderRequest = new ascio\AutoInstallSslOrderRequest(ascio\OrderType::Register);
		$orderRequest->setPeriod($certyears); 
		$autoInstallSsl = new ascio\AutoInstallSsl(0);        
		$autoInstallSsl->setCommonName($domainName);
		$autoInstallSsl->setProductCode($certtype);
		//$autoInstallSsl->setEmail($params["customfields"]["Approval Email"]);
		$orderRequest->setAutoInstallSsl($autoInstallSsl);
		$createOrder = new ascio\CreateOrder($orderRequest);
		$response = $ascioClient->createOrder($createOrder); 
		$orderInfo = $response->CreateOrderResult->getOrderInfo();
        if($response->CreateOrderResult->getResultCode() != 200) {
            return join(", ",$response->CreateOrderResult->getErrors()->getString());
        }
		$orderId = $orderInfo->getOrderId();
         // 1. Create record at WHMCS tblssorders table
        $queryData = array(
            "userid" => $params["clientsdetails"]["userid"],
            "serviceid" => $params["serviceid"],
            "remoteid" => $orderId,
            "module" => "asciossl",
            "certtype" => $certtype,
            "status" => $orderInfo->getStatus()
        );
        $orderId = Capsule::table('tblsslorders')->insertGetId($queryData);		
    	// 2. Create record at custom module table
	    $queryData = array(
    	    'id' => $sslorderid,
        	'user_id' => $params["clientsdetails"]["userid"],
        	'order_id' => $orderId,
        	'type' => $certtype,
        	'period' => $certyears,
    	);
        $orderId = Capsule::table('mod_asciossl')->insert($queryData);
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'asciossl',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );      
        return $e->getMessage();
    }

    return 'success';
}
/**
 * Admin services tab additional fields.
 *
 * Define additional rows and fields to be displayed in the admin area service
 * information and management page within the clients profile.
 *
 * Supports an unlimited number of additional field labels and content of any
 * type to output.
 *
 * @param array $params common module parameters
 *
 * @see http://docs.whmcs.com/Provisioning_Module_SDK_Parameters
 * @see asciossl_AdminServicesTabFieldsSave()
 *
 * @return array
 */
function asciossl_AdminServicesTabFields(array $params)
{
    return asciossl_updateOrder($params);
}

/**
 * Test connection with the given server parameters.
 *
 * Allows an admin user to verify that an API connection can be
 * successfully made with the given configuration parameters for a
 * server.
 *
 * When defined in a module, a Test Connection button will appear
 * alongside the Server Type dropdown when adding or editing an
 * existing server.
 *
 * @param array $params common module parameters
 *
 * @see http://docs.whmcs.com/Provisioning_Module_SDK_Parameters
 *
 * @return array
 */
function asciossl_TestConnection(array $params)
{
    try {
        // Call the service's connection test function.

        $success = true;
        $errorMsg = '';
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'asciossl',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        $success = false;
        $errorMsg = $e->getMessage();
    }

    return array(
        'success' => $success,
        'error' => $errorMsg,
    );
}
function asciossl_Renew() {        
    var_dump("test renew");
}

/**
 * Additional actions an admin user can invoke.
 *
 * Define additional actions that an admin user can perform for an
 * instance of a product/service.
 *
 * @see asciossl_buttonOneFunction()
 *
 * @return array
 */
function asciossl_AdminCustomButtonArray()
{
    // TODO Create Renew
    // TODO Create Reissue
    // TODO Create Order SANs
    // TODO Create Fail
    return array(
        "Renew certificate" => "renew"
    );
}

/**
 * Additional actions a client user can invoke.
 *
 * Define additional actions a client user can perform for an instance of a
 * product/service.
 *
 * Any actions you define here will be automatically displayed in the available
 * list of actions within the client area.
 *
 * @return array
 */
function asciossl_ClientAreaCustomButtonArray()
{
    return array(
        "Download certificate" => "download",
    );
}

/**
 * Custom function for performing an additional action.
 *
 * You can define an unlimited number of custom functions in this way.
 *
 * Similar to all other module call functions, they should either return
 * 'success' or an error message to be displayed.
 *
 * @param array $params common module parameters
 *
 * @see http://docs.whmcs.com/Provisioning_Module_SDK_Parameters
 * @see asciossl_AdminCustomButtonArray()
 *
 * @return string "success" or an error message
 */
function asciossl_buttonOneFunction(array $params)
{
    try {
        // Call the service's function, using the values provided by WHMCS in
        // `$params`.
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'asciossl',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

/**
 * Custom function for performing an additional action.
 *
 * You can define an unlimited number of custom functions in this way.
 *
 * Similar to all other module call functions, they should either return
 * 'success' or an error message to be displayed.
 *
 * @param array $params common module parameters
 *
 * @see http://docs.whmcs.com/Provisioning_Module_SDK_Parameters
 * @see asciossl_ClientAreaCustomButtonArray()
 *
 * @return string "success" or an error message
 */
function asciossl_actionOneFunction(array $params)
{
    try {
        // Call the service's function, using the values provided by WHMCS in
        // `$params`.
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'asciossl',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}



/**
 * Execute actions upon save of an instance of a product/service.
 *
 * Use to perform any required actions upon the submission of the admin area
 * product management form.
 *
 * It can also be used in conjunction with the AdminServicesTabFields function
 * to handle values submitted in any custom fields which is demonstrated here.
 *
 * @param array $params common module parameters
 *
 * @see http://docs.whmcs.com/Provisioning_Module_SDK_Parameters
 * @see asciossl_AdminServicesTabFields()
 */
function asciossl_AdminServicesTabFieldsSave(array $params)
{
    // Fetch form submission variables.
    $originalFieldValue = isset($_REQUEST['asciossl_original_uniquefieldname'])
        ? $_REQUEST['asciossl_original_uniquefieldname']
        : '';

    $newFieldValue = isset($_REQUEST['asciossl_uniquefieldname'])
        ? $_REQUEST['asciossl_uniquefieldname']
        : '';

    // Look for a change in value to avoid making unnecessary service calls.
    if ($originalFieldValue != $newFieldValue) {
        try {
            // Call the service's function, using the values provided by WHMCS
            // in `$params`.
        } catch (Exception $e) {
            // Record the error in WHMCS's module log.
            logModuleCall(
                'asciossl',
                __FUNCTION__,
                $params,
                $e->getMessage(),
                $e->getTraceAsString()
            );

            // Otherwise, error conditions are not supported in this operation.
        }
    }
}

function asciossl_download(array $whmcsParams) {
    $params = new ssl\Params($whmcsParams);
    $ssl = new ssl\Ssl($params);
    $sslData = $ssl->readDb();
    $certificate =  $ssl->getCertificate($sslData->certificate_id);
    $name = $ssl->getCertificateConfig()->name."-" .$ssl->fqdn->getFqdn().".crt";
    $name = str_replace(" ","_",$name);

    header("Content-Description: File Transfer"); 
    header("Content-Type: application/octet-stream"); 
    header("Content-Disposition: attachment; filename='".$name."'"); 

    echo ($certificate->getCertificate());
    die();
}
function asciossl_reissue(array $whmcsParams) {
    $params = new ssl\Params($whmcsParams);
    $ssl = new ssl\Ssl($params);    
    $ssl->readDb();
    $template =  "templates/certificate-data-reissue.tpl";
    $pagesVars = array(
        'tabOverviewReplacementTemplate' => $template,
        'templateVariables' => $ssl->toForm()       
    );     
    return $pagesVars;
}
/**
 *
 * @param array $params common module parameters
 *
 * @see http://docs.whmcs.com/Provisioning_Module_SDK_Parameters
 *
 * @return array
 */
function asciossl_ClientArea(array $whmcsParams)
{    
    // autoinstall SSL 
    if( $params["customfields"]["AutoInstallSsl"] =="on") {
        return "<p></p><h4>AutoInstallSSL Token:</h4> <span>".$update["AutoInstallSsl Token"]."</span><p></p>";
    } 
    if(!$_SESSION["pageUid"]){
        $_SESSION["pageUid"] = [];
    }     
    $uid = uniqid(); 
    $params = new ssl\Params($whmcsParams);
    $ssl = new ssl\Ssl($params);
    $contacts = new ssl\SslContacts($params);
    
    if($_POST["step"]=="reissue") {        
        if(in_array($_POST["random"],$_SESSION["pageUid"] )) {
            //no resubmit            
            $ssl->readDb();
            $result = [];  
        } else {
            $ssl->fromForm();
            $ssl->writeDb();
            $contacts->readDb();
            $result = $ssl->reissue($contacts);           
        }
        array_push($_SESSION["pageUid"],$_POST["random"]);    
        $form = [];
        $form["statusHtml"] =  $ssl->statusHtml();
        $form["message"] = $form["status"];     
        $form["certificateName"] = $ssl->getCertificateConfig()->name;   
        $pagesVars = array(
            'tabOverviewReplacementTemplate' => "templates/status.tpl",
            'templateVariables' => array_merge($ssl->toForm(),$result,$form)
        ); 
        return $pagesVars;
    }
    if($_GET["ordertype"]=="reissue") {
        $ssl->readDb();
        $template =  "templates/certificate-data-reissue.tpl";
        $form =  $ssl->toForm();
        $form["random"] = $uid;    
        $pagesVars = array(
            'tabOverviewReplacementTemplate' => $template,
            'templateVariables' => $form      
        );     
        return $pagesVars;
    }
    if($_POST["step"]=="contacts") {        
        // contact form
        $contactList = $contacts->getDropDownOptions();
        $ssl->fromForm();
        $ssl->writeDb();      
      
        $pagesVars = array(
            'tabOverviewReplacementTemplate' => "templates/contacts.tpl",
            'templateVariables' => array(
                'contactList' => $contactList,
                'random'      => $uid  //prevent resubmit
            )        
        ); 
   
        $pagesVars["templateVariables"] = array_merge($contacts->toForm(), $pagesVars["templateVariables"] ); 
    } elseif ($_POST["step"]=="register") {
        // TODO fix this
        // prevent resubmit
               
        if(in_array($_POST["random"],$_SESSION["pageUid"] )) {
            //no resubmit            
            $ssl->readDb();
            $result = [];           
        } else {
            $contacts->fromForm();
            $contacts->writeDb();
            //TODO: Trigger Renew/Reissue
            $result = $ssl->register($contacts);
        }
        array_push($_SESSION["pageUid"],$_POST["random"]);    
        $form = [];
        $form["statusHtml"] =  $ssl->statusHtml();
        $form["message"] = $form["status"];     
        $form["certificateName"] = $ssl->getCertificateConfig()->name;      
        $pagesVars = array(
            'tabOverviewReplacementTemplate' => "templates/status.tpl",
            'templateVariables' => array_merge($ssl->toForm(),$result,$form)
        );      
    } else { 
        // read data
        $data = $ssl->readDb();      
        $form = $ssl->toForm() ;
        //TODO: Add reissue/renew
        if($data->code == 200) {
            // show status
            $form["statusHtml"] =  $ssl->statusHtml();
            $form["message"] = $form["status"];
            $form["certificateName"] = $ssl->getCertificateConfig()->name;
            $template =  "templates/status.tpl";
        } else {
            $form["errors"] = $form["errors"] ?  $form["errors"] :  $data->message;
            // show certificate parameters                        
            $template =  "templates/certificate-data.tpl";
        }
        $pagesVars = array(
            'tabOverviewReplacementTemplate' => $template,
            'templateVariables' => $form       
        );        
    }
  
    return $pagesVars;
}

/**
 * Ensure required database tables exist.
 *
 * Creates tables lazily on first use (standard WHMCS pattern for server modules).
 *
 * @return bool True if tables exist or were created successfully
 */
function asciossl_EnsureTable(): bool
{
    try {
        if (!Capsule::schema()->hasTable('mod_asciossl')) {
            Capsule::schema()->create('mod_asciossl', function ($table) {
                $table->increments('id');
                $table->integer('user_id');
                $table->string('order_id', 10);
                $table->string('certificate_id', 20)->nullable();
                $table->string('type', 255);
                $table->integer('period');
                $table->string('status', 100)->nullable();
                $table->integer('code')->nullable();
                $table->string('message', 1024)->nullable();
                $table->string('errors', 4096)->nullable();
                $table->string('token', 100)->nullable();
                $table->integer('whmcs_service_id');
                $table->string('common_name', 2048)->nullable();
                $table->text('csr')->nullable();
                $table->string('webserver', 2048)->nullable();
                $table->enum('verification_type', ['Email', 'Dns', 'File']);
                $table->string('dns_name', 1024)->nullable();
                $table->string('dns_value', 1024)->nullable();
                $table->string('dns_error_code', 256)->nullable();
                $table->string('dns_error_message', 2048)->nullable();
                $table->boolean('create_dns_record')->nullable();
                $table->boolean('dns_created')->nullable();
                $table->string('approval_email', 256)->nullable();
                $table->date('expire_date')->nullable();
                $table->string('ownerTitle', 256)->nullable();
                $table->string('ownerFirstName', 256)->nullable();
                $table->string('ownerLastName', 256)->nullable();
                $table->string('ownerCompanyName', 512)->nullable();
                $table->string('ownerPhone', 256)->nullable();
                $table->string('ownerAddress1', 512)->nullable();
                $table->string('ownerAddress2', 512)->nullable();
                $table->string('ownerCity', 256)->nullable();
                $table->string('ownerState', 256)->nullable();
                $table->string('ownerPostcode', 256)->nullable();
                $table->string('ownerCountry', 256)->nullable();
                $table->string('adminTitle', 256)->nullable();
                $table->string('adminFirstName', 256)->nullable();
                $table->string('adminLastName', 256)->nullable();
                $table->string('adminCompanyName', 512)->nullable();
                $table->string('adminPhone', 256)->nullable();
                $table->string('adminAddress1', 512)->nullable();
                $table->string('adminAddress2', 512)->nullable();
                $table->string('adminCity', 256)->nullable();
                $table->string('adminState', 256)->nullable();
                $table->string('adminPostcode', 256)->nullable();
                $table->string('adminCountry', 256)->nullable();
                $table->string('techTitle', 256)->nullable();
                $table->string('techFirstName', 256)->nullable();
                $table->string('techLastName', 256)->nullable();
                $table->string('techCompanyName', 512)->nullable();
                $table->string('techPhone', 256)->nullable();
                $table->string('techAddress1', 512)->nullable();
                $table->string('techAddress2', 512)->nullable();
                $table->string('techCity', 256)->nullable();
                $table->string('techState', 256)->nullable();
                $table->string('techPostcode', 256)->nullable();
                $table->string('techCountry', 256)->nullable();
                $table->string('ownerEmail', 256)->nullable();
                $table->string('adminEmail', 256)->nullable();
                $table->string('techEmail', 256)->nullable();
                $table->timestamp('completed_date')->nullable();
                $table->string('module', 20)->default('ssl');
                $table->index('order_id');
                $table->index('whmcs_service_id');
            });
        }

        if (!Capsule::schema()->hasTable('mod_asciossl_sans')) {
            Capsule::schema()->create('mod_asciossl_sans', function ($table) {
                $table->increments('id');
                $table->integer('service_id');
                $table->string('name', 256);
                $table->string('verification_type', 255)->nullable();
                $table->string('email', 256);
                $table->boolean('mx_fqdn')->nullable();
                $table->boolean('mx_domain')->nullable();
                $table->string('dns_name', 255)->nullable();
                $table->string('dns_value', 255);
                $table->string('dns_error_message', 255);
                $table->string('dns_error_code', 255);
                $table->boolean('dns_created');
                $table->index('service_id', 'whmcs_service_id');
                $table->index('name');
            });
        }

        if (!Capsule::schema()->hasTable('mod_asciossl_settings')) {
            Capsule::schema()->create('mod_asciossl_settings', function ($table) {
                $table->increments('id');
                $table->string('name', 255)->unique();
                $table->string('value', 255);
                $table->enum('role', ['User', 'Admin', ''])->default('User');
                $table->index('name');
                $table->index('role');
            });

            // Insert default settings
            Capsule::table('mod_asciossl_settings')->insert([
                ['id' => 1, 'name' => 'Account', 'value' => '', 'role' => 'User'],
                ['id' => 2, 'name' => 'Password', 'value' => '', 'role' => 'User'],
                ['id' => 3, 'name' => 'AccountTesting', 'value' => '', 'role' => 'User'],
                ['id' => 4, 'name' => 'PasswordTesting', 'value' => '', 'role' => 'User'],
                ['id' => 5, 'name' => 'Environment', 'value' => '', 'role' => 'User'],
                ['id' => 6, 'name' => 'CreateDns', 'value' => '1', 'role' => 'User'],
                ['id' => 7, 'name' => 'RequireDomain', 'value' => '1', 'role' => 'User'],
                ['id' => 9, 'name' => 'DbVersion', 'value' => '0.2', 'role' => 'Admin'],
            ]);
        }

        return true;

    } catch (\Exception $e) {
        logModuleCall('asciossl', 'EnsureTable', [], $e->getMessage(), $e->getTraceAsString());
        return false;
    }
}

