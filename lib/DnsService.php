<?php
namespace ascio\dns;
use \SoapClient as SoapClient; 
use \SoapHeader as SoapHeader;

class GetRoles {
}

class GetRolesResponse {
  public $GetRolesResult; // Response
  public $roles; // ArrayOfRoleItem
}

class Response {
  public $StatusCode; // short
  public $StatusMessage; // string
  public $TechnicalGuid; // string
  public $TrackingReference; // string
  public $Values; // ArrayOfstring
}

class RoleItem {
  public $Rights; // ArrayOfstring
  public $Role; // string
}

class CreateUser {
  public $user; // User
}

class User {
  public $CreatedDate; // dateTime
  public $Email; // string
  public $Name; // string
  public $Password; // string
  public $Role; // string
  public $UpdatedDate; // dateTime
  public $UserName; // string
}

class CreateUserResponse {
  public $CreateUserResult; // Response
}

class UpdateUser {
  public $user; // User
}

class UpdateUserResponse {
  public $UpdateUserResult; // Response
}

class DeleteUser {
  public $userName; // string
}

class DeleteUserResponse {
  public $DeleteUserResult; // Response
}

class GetUser {
  public $userName; // string
}

class GetUserResponse {
  public $GetUserResult; // Response
  public $user; // User
}

class SearchUser {
  public $searchUserClauses; // ArrayOfSearchUserClause
}

class SearchUserClause {
  public $Operator; // SearchOperatorType
  public $SearchUserField; // SearchUserField
  public $Value; // string
}

class SearchOperatorType {
  const Is = 'Is';
  const IsNot = 'IsNot';
  const Like = 'Like';
  const NotLike = 'NotLike';
  const LessThan = 'LessThan';
  const GreaterThan = 'GreaterThan';
}

class SearchUserField {
  const UserName = 'UserName';
  const RoleType = 'RoleType';
  const Email = 'Email';
}

class SearchUserResponse {
  public $SearchUserResult; // Response
  public $userNames; // ArrayOfstring
}

class ChangePassword {
  public $userName; // string
  public $newPassword; // string
}

class ChangePasswordResponse {
  public $ChangePasswordResult; // Response
}

class CreateZone {
  public $zoneName; // string
  public $owner; // string
  public $records; // ArrayOfRecord
}

class Record {
  public $Id; // int
  public $Serial; // long
  public $Source; // string
  public $TTL; // long
  public $Target; // string
  public $UpdatedDate; // dateTime
}

class WebForward {
  public $RedirectionType; // RedirectionType
}

class RedirectionType {
  const Temporary = 'Temporary';
  const Permanent = 'Permanent';
  const Frame = 'Frame';
}

class SRV {
  public $Port; // int
  public $Priority; // int
  public $Weight; // int
}

class CNAME {
}

class SOA {
  public $Expire; // int
  public $HostmasterEmail; // string
  public $PrimaryNameServer; // string
  public $Refresh; // int
  public $Retry; // int
  public $SerialUsage; // int
}

class TXT {
}

class PTR {
}

class MX {
  public $Priority; // int
}

class A {
}

class AAAA {
}

class NS {
}

class MailForward {
}

class CreateZoneResponse {
  public $CreateZoneResult; // Response
}

class DeleteZone {
  public $zoneName; // string
}

class DeleteZoneResponse {
  public $DeleteZoneResult; // Response
}

class GetZoneLog {
  public $zoneName; // string
}

class GetZoneLogResponse {
  public $GetZoneLogResult; // Response
  public $zoneLogEntries; // ArrayOfZoneLogEntry
}

class ZoneLogEntry {
  public $Action; // string
  public $ActionBy; // string
  public $ActionByIpAddress; // string
  public $ActionDate; // dateTime
  public $Record; // Record
  public $ZoneName; // string
}

class RestoreZone {
  public $zoneName; // string
}

class RestoreZoneResponse {
  public $RestoreZoneResult; // Response
  public $zone; // Zone
}

class GetZone {
  public $zoneName; // string
}

class GetZoneResponse {
  public $GetZoneResult; // Response
  public $zone; // Zone
}

class Zone {
  public $CreatedDate; // dateTime
  public $Owner; // string
  public $Records; // ArrayOfRecord
  public $ZoneName; // string
}

class SearchZoneNames {
  public $searchZoneClauses; // ArrayOfSearchZoneClause
}

class SearchZoneClause {
  public $Operator; // SearchOperatorType
  public $SearchZoneField; // SearchZoneField
  public $Value; // string
}

class SearchZoneField {
  const ZoneName = 'ZoneName';
  const Owner = 'Owner';
  const Source = 'Source';
  const Target = 'Target';
  const RecordType = 'RecordType';
  const CreatedDate = 'CreatedDate';
  const TTL = 'TTL';
}

class SearchZoneNamesResponse {
  public $SearchZoneNamesResult; // Response
  public $zoneNames; // ArrayOfstring
}

class SearchZone {
  public $searchZoneClauses; // ArrayOfSearchZoneClause
  public $zoneInfoLevel; // ZoneInfoLevel
}

class ZoneInfoLevel {
  const Basic = 'Basic';
  const Full = 'Full';
  const Partial = 'Partial';
}

class SearchZoneResponse {
  public $SearchZoneResult; // Response
  public $zones; // ArrayOfZone
}

class SetZoneOwner {
  public $zoneName; // string
  public $owner; // string
}

class SetZoneOwnerResponse {
  public $SetZoneOwnerResult; // Response
}

class CreateRecord {
  public $zoneName; // string
  public $record; // Record
}

class CreateRecordResponse {
  public $CreateRecordResult; // Response
  public $recordId; // int
}

class UpdateRecord {
  public $record; // Record
}

class UpdateRecordResponse {
  public $UpdateRecordResult; // Response
}

class DeleteRecord {
  public $recordId; // int
}

class DeleteRecordResponse {
  public $DeleteRecordResult; // Response
}

class GetRecord {
  public $recordId; // int
}

class GetRecordResponse {
  public $GetRecordResult; // Response
  public $record; // Record
}


/**
 * DnsService class
 * 
 *  
 * 
 * @author    {author}
 * @copyright {copyright}
 * @package   {package}
 */
class DnsService extends SoapClient {

  private static $classmap = array(
                                    'GetRoles' => 'ascio\dns\GetRoles',
                                    'GetRolesResponse' => 'ascio\dns\GetRolesResponse',
                                    'Response' => 'ascio\dns\Response',
                                    'RoleItem' => 'ascio\dns\RoleItem',
                                    'CreateUser' => 'ascio\dns\CreateUser',
                                    'User' => 'ascio\dns\User',
                                    'CreateUserResponse' => 'ascio\dns\CreateUserResponse',
                                    'UpdateUser' => 'ascio\dns\UpdateUser',
                                    'UpdateUserResponse' => 'ascio\dns\UpdateUserResponse',
                                    'DeleteUser' => 'ascio\dns\DeleteUser',
                                    'DeleteUserResponse' => 'ascio\dns\DeleteUserResponse',
                                    'GetUser' => 'ascio\dns\GetUser',
                                    'GetUserResponse' => 'ascio\dns\GetUserResponse',
                                    'SearchUser' => 'ascio\dns\SearchUser',
                                    'SearchUserClause' => 'ascio\dns\SearchUserClause',
                                    'SearchOperatorType' => 'ascio\dns\SearchOperatorType',
                                    'SearchUserField' => 'ascio\dns\SearchUserField',
                                    'SearchUserResponse' => 'ascio\dns\SearchUserResponse',
                                    'ChangePassword' => 'ascio\dns\ChangePassword',
                                    'ChangePasswordResponse' => 'ascio\dns\ChangePasswordResponse',
                                    'CreateZone' => 'ascio\dns\CreateZone',
                                    'Record' => 'ascio\dns\Record',
                                    'WebForward' => 'ascio\dns\WebForward',
                                    'RedirectionType' => 'ascio\dns\RedirectionType',
                                    'SRV' => 'ascio\dns\SRV',
                                    'CNAME' => 'ascio\dns\CNAME',
                                    'SOA' => 'ascio\dns\SOA',
                                    'TXT' => 'ascio\dns\TXT',
                                    'PTR' => 'ascio\dns\PTR',
                                    'MX' => 'ascio\dns\MX',
                                    'A' => 'ascio\dns\A',
                                    'AAAA' => 'ascio\dns\AAAA',
                                    'NS' => 'ascio\dns\NS',
                                    'MailForward' => 'ascio\dns\MailForward',
                                    'CreateZoneResponse' => 'ascio\dns\CreateZoneResponse',
                                    'DeleteZone' => 'ascio\dns\DeleteZone',
                                    'DeleteZoneResponse' => 'ascio\dns\DeleteZoneResponse',
                                    'GetZoneLog' => 'ascio\dns\GetZoneLog',
                                    'GetZoneLogResponse' => 'ascio\dns\GetZoneLogResponse',
                                    'ZoneLogEntry' => 'ascio\dns\ZoneLogEntry',
                                    'RestoreZone' => 'ascio\dns\RestoreZone',
                                    'RestoreZoneResponse' => 'ascio\dns\RestoreZoneResponse',
                                    'GetZone' => 'ascio\dns\GetZone',
                                    'GetZoneResponse' => 'ascio\dns\GetZoneResponse',
                                    'Zone' => 'ascio\dns\Zone',
                                    'SearchZoneNames' => 'ascio\dns\SearchZoneNames',
                                    'SearchZoneClause' => 'ascio\dns\SearchZoneClause',
                                    'SearchZoneField' => 'ascio\dns\SearchZoneField',
                                    'SearchZoneNamesResponse' => 'ascio\dns\SearchZoneNamesResponse',
                                    'SearchZone' => 'ascio\dns\SearchZone',
                                    'ZoneInfoLevel' => 'ascio\dns\ZoneInfoLevel',
                                    'SearchZoneResponse' => 'ascio\dns\SearchZoneResponse',
                                    'SetZoneOwner' => 'ascio\dns\SetZoneOwner',
                                    'SetZoneOwnerResponse' => 'ascio\dns\SetZoneOwnerResponse',
                                    'CreateRecord' => 'ascio\dns\CreateRecord',
                                    'CreateRecordResponse' => 'ascio\dns\CreateRecordResponse',
                                    'UpdateRecord' => 'ascio\dns\UpdateRecord',
                                    'UpdateRecordResponse' => 'ascio\dns\UpdateRecordResponse',
                                    'DeleteRecord' => 'ascio\dns\DeleteRecord',
                                    'DeleteRecordResponse' => 'ascio\dns\DeleteRecordResponse',
                                    'GetRecord' => 'ascio\dns\GetRecord',
                                    'GetRecordResponse' => 'ascio\dns\GetRecordResponse',
                                   );
 
  public function __construct($userName, $password, $partnerAccount = null, $actAs = null, $wsdl = "https://dnsservice.ascio.com/2010/10/30/DnsService.wsdl", $options = array()) {
    $ns = 'http://groupnbt.com/2010/10/30/Dns/DnsService'; //Namespace of the WS. 
    $headers = array(); 
    $headers[] = new SoapHeader($ns,'UserName', $userName);
    $headers[] = new SoapHeader($ns, 'Password', $password);
    if($actAs) $headers[] = new SoapHeader($ns, 'ActAs', $actAs);
    if($partnerAccount) $headers[] = new SoapHeader($ns, 'Account', $partnerAccount);
    $this->__setSoapHeaders($headers);
    foreach(self::$classmap as $key => $value) {
      if(!isset($options['classmap'][$key])) {
        $options['classmap'][$key] = $value;
      }
    }
    $options['trace'] = 1;
    parent::__construct($wsdl, $options);
  }

  /**
   *  
   *
   * @param GetRoles $parameters
   * @return GetRolesResponse
   */
  public function GetRoles(GetRoles $parameters) {
    return $this->__soapCall('GetRoles', array($parameters),       array(
            'uri' => 'http://groupnbt.com/2010/10/30/Dns/DnsService',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param CreateUser $parameters
   * @return CreateUserResponse
   */
  public function CreateUser(CreateUser $parameters) {
    return $this->__soapCall('CreateUser', array($parameters),       array(
            'uri' => 'http://groupnbt.com/2010/10/30/Dns/DnsService',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param UpdateUser $parameters
   * @return UpdateUserResponse
   */
  public function UpdateUser(UpdateUser $parameters) {
    return $this->__soapCall('UpdateUser', array($parameters),       array(
            'uri' => 'http://groupnbt.com/2010/10/30/Dns/DnsService',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param DeleteUser $parameters
   * @return DeleteUserResponse
   */
  public function DeleteUser(DeleteUser $parameters) {
    return $this->__soapCall('DeleteUser', array($parameters),       array(
            'uri' => 'http://groupnbt.com/2010/10/30/Dns/DnsService',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param GetUser $parameters
   * @return GetUserResponse
   */
  public function GetUser(GetUser $parameters) {
    return $this->__soapCall('GetUser', array($parameters),       array(
            'uri' => 'http://groupnbt.com/2010/10/30/Dns/DnsService',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param SearchUser $parameters
   * @return SearchUserResponse
   */
  public function SearchUser(SearchUser $parameters) {
    return $this->__soapCall('SearchUser', array($parameters),       array(
            'uri' => 'http://groupnbt.com/2010/10/30/Dns/DnsService',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param ChangePassword $parameters
   * @return ChangePasswordResponse
   */
  public function ChangePassword(ChangePassword $parameters) {
    return $this->__soapCall('ChangePassword', array($parameters),       array(
            'uri' => 'http://groupnbt.com/2010/10/30/Dns/DnsService',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param CreateZone $parameters
   * @return CreateZoneResponse
   */
  public function CreateZone(CreateZone $parameters) {
    return $this->__soapCall('CreateZone', array($parameters),       array(
            'uri' => 'http://groupnbt.com/2010/10/30/Dns/DnsService',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param DeleteZone $parameters
   * @return DeleteZoneResponse
   */
  public function DeleteZone(DeleteZone $parameters) {
    return $this->__soapCall('DeleteZone', array($parameters),       array(
            'uri' => 'http://groupnbt.com/2010/10/30/Dns/DnsService',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param GetZoneLog $parameters
   * @return GetZoneLogResponse
   */
  public function GetZoneLog(GetZoneLog $parameters) {
      $res = $this->__soapCall('GetZoneLog', array($parameters),       array(
            'uri' => 'http://groupnbt.com/2010/10/30/Dns/DnsService',
            'soapaction' => ''
           )
      );
      echo("last: ".$this->__getLastRequest());
      return $res; 
  }

  /**
   *  
   *
   * @param GetZone $parameters
   * @return GetZoneResponse
   */
  public function GetZone(GetZone $parameters) {
    return $this->__soapCall('GetZone', array($parameters),       array(
            'uri' => 'http://groupnbt.com/2010/10/30/Dns/DnsService',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param RestoreZone $parameters
   * @return RestoreZoneResponse
   */
  public function RestoreZone(RestoreZone $parameters) {
    return $this->__soapCall('RestoreZone', array($parameters),       array(
            'uri' => 'http://groupnbt.com/2010/10/30/Dns/DnsService',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param SearchZoneNames $parameters
   * @return SearchZoneNamesResponse
   */
  public function SearchZoneNames(SearchZoneNames $parameters) {
    return $this->__soapCall('SearchZoneNames', array($parameters),       array(
            'uri' => 'http://groupnbt.com/2010/10/30/Dns/DnsService',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param SearchZone $parameters
   * @return SearchZoneResponse
   */
  public function SearchZone(SearchZone $parameters) {
    return $this->__soapCall('SearchZone', array($parameters),       array(
            'uri' => 'http://groupnbt.com/2010/10/30/Dns/DnsService',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param SetZoneOwner $parameters
   * @return SetZoneOwnerResponse
   */
  public function SetZoneOwner(SetZoneOwner $parameters) {
    return $this->__soapCall('SetZoneOwner', array($parameters),       array(
            'uri' => 'http://groupnbt.com/2010/10/30/Dns/DnsService',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param CreateRecord $parameters
   * @return CreateRecordResponse
   */
  public function CreateRecord(CreateRecord $parameters) {
    return $this->__soapCall('CreateRecord', array($parameters),       array(
            'uri' => 'http://groupnbt.com/2010/10/30/Dns/DnsService',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param UpdateRecord $parameters
   * @return UpdateRecordResponse
   */
  public function UpdateRecord(UpdateRecord $parameters) {
    return $this->__soapCall('UpdateRecord', array($parameters),       array(
            'uri' => 'http://groupnbt.com/2010/10/30/Dns/DnsService',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param DeleteRecord $parameters
   * @return DeleteRecordResponse
   */
  public function DeleteRecord(DeleteRecord $parameters) {
    return $this->__soapCall('DeleteRecord', array($parameters),       array(
            'uri' => 'http://groupnbt.com/2010/10/30/Dns/DnsService',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param GetRecord $parameters
   * @return GetRecordResponse
   */
  public function GetRecord(GetRecord $parameters) {
    return $this->__soapCall('GetRecord', array($parameters),       array(
            'uri' => 'http://groupnbt.com/2010/10/30/Dns/DnsService',
            'soapaction' => ''
           )
      );
  }

}

?>
