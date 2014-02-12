<?php

require_once("config.php");
require_once("sessioncache.php");
require_once("tools.php");

Class ASCIO {

  private static function login($params) {
    $session = array(
      'Account' => $params["Username"],
      'Password' => $params["Password"]
    );
    return ASCIO::sendRequest('LogIn', array('session' => $session));
  }

  public static function request($functionName, $ascioParams) {
    $sessionId = SessionCache::get();
    if (!$sessionId) {
      $result = ASCIO::login(getAscioCredentials());
      if (is_array($result)) {
        return $result;
      }
      $ascioParams["sessionId"] = $result->sessionId;
      SessionCache::put($result->sessionId);
    } else {
      $ascioParams["sessionId"] = $sessionId;
    }
    $result = ASCIO::sendRequest($functionName, $ascioParams);
    if (is_array($result) && strpos($result["error"], "Invalid Session") > -1) {
      SessionCache::clear();
      return ASCIO::request($functionName, $ascioParams);
    } else {
      return $result;
    }
    return;
  }

  private static function sendRequest($functionName, $ascioParams) {
    syslog(LOG_INFO, $functionName);
    $ascioParams = Tools::cleanAscioParams($ascioParams);
    $client = new SoapClient(getAscioWsdl(), array("trace" => 1));
    $result = $client->__call($functionName, array('parameters' => $ascioParams));
    $resultName = $functionName . "Result";
    $status = $result->$resultName;

    //error_log(print_r($result, true));

    if ($status->ResultCode == 200) {
      return $result;
    } else {
      if (count($status->Values->string) > 1) {
        $messages = join("<br/>\n", $status->Values->string);
      } else {
        $messages = $status->Values->string;
      }
      return array('error' => $status->Message . "<br/>\n" . $messages);
    }
  }

  private static function getDomain($handle) {
    $ascioParams = array(
      'sessionId' => 'mySessionId',
      'domainHandle' => $handle
    );
    $result = ASCIO::request("GetDomain", $ascioParams);
    return $result;
  }

  public static function searchDomain($params) {
    $criteria = array(
      'Mode' => 'Strict',
      'Clauses' => Array(
        'Clause' => Array(
          'Attribute' => 'DomainName',
          'Value' => $params["sld"] . "." . $params["tld"], '
  				Operator' => 'Is'
        )
      )
    );
    $ascioParams = array(
      'sessionId' => 'mySessionId',
      'criteria' => $criteria
    );
    $result = ASCIO::request("SearchDomain", $ascioParams);
    if (is_array($result))
      return $result;
    else
      return $result->domains->Domain;
  }

  public static function getCallbackData($orderStatus, $messageId, $orderId) {
    // get message
    $ascioParams = array(
      'sessionId' => 'mySessionId',
      'msgId' => $messageId
    );
    $result = ASCIO::request("GetMessageQueue", $ascioParams);
    $domainName = $result->item->DomainName;
    if ($orderStatus == "Completed") {
      $status = "Active";
    } else {
      $status = $orderStatus;
    }

    // External WHMCS API: Set Status
    $postfields = array();
    $postfields["domain"] = $domainName;
    $postfields["status"] = $status;
    $results = ASCIO::callApi("updateclientdomain", $postfields);
    $id = $results["domainid"];
    // External WHMCS API: Send Mail
    $msgPart = "Domain order " . $id . ", " . $domainName;
    if ($orderStatus == "Completed") {
      $message = Tools::formatOK($msgPart);
    } else {
      $message = Tools::formatError($result->item->StatusList->CallbackStatus, $msgPart);
    }
    $postfields = array();
    $postfields["customtype"] = "domain";
    $postfields["customsubject"] = $msgPart . " " . strtolower($status);
    $postfields["custommessage"] = $message;
    $postfields["id"] = $id;
    ASCIO::callApi("sendemail", $postfields);

    // Ascio ACK Message
    syslog(LOG_INFO, $domainName . ": " . $status);
    $order = ASCIO::getOrder($orderId);
    ASCIO::sendAuthCode($order->order);
    $result = ASCIO::ack($messageId);
  }

  private static function getOrder($orderId) {
    $ascioParams = array(
      'sessionId' => 'mySessionId',
      'orderId' => $orderId
    );
    $result = ASCIO::request("GetOrder", $ascioParams);
    return $result;
  }

  private static function sendAuthCode($order) {
    if ($order->Type != "Update_AuthInfo")
      return;

    $domain = ASCIO::getDomain($order->Domain->DomainHandle);
    syslog(LOG_INFO, "EPP Code: " . $domain->domain->AuthInfo);
    $msg = "New AuthCode generated for " . $domain->domain->DomainName . ": " . $domain->domain->AuthInfo;
    $postfields = array();
    $postfields["customtype"] = "domain";
    $postfields["customsubject"] = $domain->domain->DomainName . ": New AuthCode generated";
    $postfields["custommessage"] = $msg;
    $postfields["id"] = $order->OrderId;
    ASCIO::callApi("sendemail", $postfields);
  }

  public static function poll() {
    $params = getAscioCredentials();
    $ascioParams = array(
      'sessionId' => 'mySessionId',
      'msgType' => 'Message_to_Partner'
    );
    $result = ASCIO::request("PollMessage", $ascioParams);
    return $result;
  }

  public static function ack($msgId) {
    $ascioParams = array(
      'sessionId' => 'mySessionId',
      'msgId' => $msgId
    );
    $result = ASCIO::request("AckMessage", $ascioParams);
    return $result;
  }

  public static function callApi($command, $params) {
    //error_log($command);
    //error_log(print_r($params, true));
    //error_log(getWHMCSCredentials());

    $results = localAPI($command, $params, getWHMCSCredentials());

    error_log(print_r($results, true));

    if ($results["result"] == "success") {
      # Result was OK!
    } else {
      # An error occured	 	
      echo "The following error occured: " . $results["message"];
    }
    return $results;
  }

}

?>
