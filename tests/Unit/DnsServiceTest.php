<?php
/**
 * Unit tests for DnsService class
 *
 * Tests DNS zone and record management functionality.
 */

namespace Ascio\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;

require_once dirname(__DIR__, 2) . '/lib/DnsService.php';

/**
 * DnsService unit tests
 */
#[Group('unit')]
#[Group('dns')]
class DnsServiceTest extends TestCase
{
    // =========================================================================
    // Data Class Tests - Record Types
    // =========================================================================

    #[Test]
    public function aRecordClassHasCorrectProperties(): void
    {
        $record = new \ascio\dns\A();

        $this->assertObjectHasProperty('Source', $record);
        $this->assertObjectHasProperty('Target', $record);
        $this->assertObjectHasProperty('TTL', $record);
    }

    #[Test]
    public function aRecordCanSetProperties(): void
    {
        $record = new \ascio\dns\A();
        $record->Source = 'www';
        $record->Target = '192.168.1.1';
        $record->TTL = 3600;

        $this->assertEquals('www', $record->Source);
        $this->assertEquals('192.168.1.1', $record->Target);
        $this->assertEquals(3600, $record->TTL);
    }

    #[Test]
    public function aaaaRecordClassHasCorrectProperties(): void
    {
        $record = new \ascio\dns\AAAA();

        $this->assertObjectHasProperty('Source', $record);
        $this->assertObjectHasProperty('Target', $record);
        $this->assertObjectHasProperty('TTL', $record);
    }

    #[Test]
    public function cnameRecordClassHasCorrectProperties(): void
    {
        $record = new \ascio\dns\CNAME();

        $this->assertObjectHasProperty('Source', $record);
        $this->assertObjectHasProperty('Target', $record);
        $this->assertObjectHasProperty('TTL', $record);
    }

    #[Test]
    public function mxRecordClassHasCorrectProperties(): void
    {
        $record = new \ascio\dns\MX();

        $this->assertObjectHasProperty('Source', $record);
        $this->assertObjectHasProperty('Target', $record);
        $this->assertObjectHasProperty('TTL', $record);
        $this->assertObjectHasProperty('Priority', $record);
    }

    #[Test]
    public function mxRecordCanSetPriority(): void
    {
        $record = new \ascio\dns\MX();
        $record->Source = '@';
        $record->Target = 'mail.example.com';
        $record->TTL = 3600;
        $record->Priority = 10;

        $this->assertEquals(10, $record->Priority);
    }

    #[Test]
    public function txtRecordClassHasCorrectProperties(): void
    {
        $record = new \ascio\dns\TXT();

        $this->assertObjectHasProperty('Source', $record);
        $this->assertObjectHasProperty('Target', $record);
        $this->assertObjectHasProperty('TTL', $record);
    }

    #[Test]
    public function srvRecordClassHasCorrectProperties(): void
    {
        $record = new \ascio\dns\SRV();

        $this->assertObjectHasProperty('Port', $record);
        $this->assertObjectHasProperty('Priority', $record);
        $this->assertObjectHasProperty('Weight', $record);
    }

    #[Test]
    public function soaRecordClassHasCorrectProperties(): void
    {
        $record = new \ascio\dns\SOA();

        $this->assertObjectHasProperty('Expire', $record);
        $this->assertObjectHasProperty('HostmasterEmail', $record);
        $this->assertObjectHasProperty('PrimaryNameServer', $record);
        $this->assertObjectHasProperty('Refresh', $record);
        $this->assertObjectHasProperty('Retry', $record);
        $this->assertObjectHasProperty('SerialUsage', $record);
    }

    #[Test]
    public function baseRecordClassHasCorrectProperties(): void
    {
        $record = new \ascio\dns\Record();

        $this->assertObjectHasProperty('Id', $record);
        $this->assertObjectHasProperty('Serial', $record);
        $this->assertObjectHasProperty('Source', $record);
        $this->assertObjectHasProperty('TTL', $record);
        $this->assertObjectHasProperty('Target', $record);
        $this->assertObjectHasProperty('UpdatedDate', $record);
    }

    // =========================================================================
    // Data Class Tests - Zone
    // =========================================================================

    #[Test]
    public function zoneClassHasCorrectProperties(): void
    {
        $zone = new \ascio\dns\Zone();

        $this->assertObjectHasProperty('CreatedDate', $zone);
        $this->assertObjectHasProperty('Owner', $zone);
        $this->assertObjectHasProperty('Records', $zone);
        $this->assertObjectHasProperty('ZoneName', $zone);
    }

    #[Test]
    public function zoneCanSetProperties(): void
    {
        $zone = new \ascio\dns\Zone();
        $zone->ZoneName = 'example.com';
        $zone->Owner = 'admin@example.com';
        $zone->Records = [];

        $this->assertEquals('example.com', $zone->ZoneName);
        $this->assertEquals('admin@example.com', $zone->Owner);
        $this->assertEquals([], $zone->Records);
    }

    // =========================================================================
    // Data Class Tests - User
    // =========================================================================

    #[Test]
    public function userClassHasCorrectProperties(): void
    {
        $user = new \ascio\dns\User();

        $this->assertObjectHasProperty('CreatedDate', $user);
        $this->assertObjectHasProperty('Email', $user);
        $this->assertObjectHasProperty('Name', $user);
        $this->assertObjectHasProperty('Password', $user);
        $this->assertObjectHasProperty('Role', $user);
        $this->assertObjectHasProperty('UpdatedDate', $user);
        $this->assertObjectHasProperty('UserName', $user);
    }

    #[Test]
    public function userCanSetCredentials(): void
    {
        $user = new \ascio\dns\User();
        $user->UserName = 'testuser';
        $user->Password = 'testpass';
        $user->Email = 'test@example.com';
        $user->Role = 'Admin';

        $this->assertEquals('testuser', $user->UserName);
        $this->assertEquals('testpass', $user->Password);
        $this->assertEquals('test@example.com', $user->Email);
        $this->assertEquals('Admin', $user->Role);
    }

    // =========================================================================
    // Data Class Tests - Response
    // =========================================================================

    #[Test]
    public function responseClassHasCorrectProperties(): void
    {
        $response = new \ascio\dns\Response();

        $this->assertObjectHasProperty('StatusCode', $response);
        $this->assertObjectHasProperty('StatusMessage', $response);
        $this->assertObjectHasProperty('TechnicalGuid', $response);
        $this->assertObjectHasProperty('TrackingReference', $response);
        $this->assertObjectHasProperty('Values', $response);
    }

    #[Test]
    public function responseCanSetStatusCode(): void
    {
        $response = new \ascio\dns\Response();
        $response->StatusCode = 200;
        $response->StatusMessage = 'Success';

        $this->assertEquals(200, $response->StatusCode);
        $this->assertEquals('Success', $response->StatusMessage);
    }

    // =========================================================================
    // Enum Tests
    // =========================================================================

    #[Test]
    public function searchOperatorTypeHasCorrectValues(): void
    {
        $this->assertEquals('Is', \ascio\dns\SearchOperatorType::Is);
        $this->assertEquals('IsNot', \ascio\dns\SearchOperatorType::IsNot);
        $this->assertEquals('Like', \ascio\dns\SearchOperatorType::Like);
        $this->assertEquals('NotLike', \ascio\dns\SearchOperatorType::NotLike);
        $this->assertEquals('LessThan', \ascio\dns\SearchOperatorType::LessThan);
        $this->assertEquals('GreaterThan', \ascio\dns\SearchOperatorType::GreaterThan);
    }

    #[Test]
    public function searchUserFieldHasCorrectValues(): void
    {
        $this->assertEquals('UserName', \ascio\dns\SearchUserField::UserName);
        $this->assertEquals('RoleType', \ascio\dns\SearchUserField::RoleType);
        $this->assertEquals('Email', \ascio\dns\SearchUserField::Email);
    }

    #[Test]
    public function searchZoneFieldHasCorrectValues(): void
    {
        $this->assertEquals('ZoneName', \ascio\dns\SearchZoneField::ZoneName);
        $this->assertEquals('Owner', \ascio\dns\SearchZoneField::Owner);
        $this->assertEquals('Source', \ascio\dns\SearchZoneField::Source);
        $this->assertEquals('Target', \ascio\dns\SearchZoneField::Target);
        $this->assertEquals('RecordType', \ascio\dns\SearchZoneField::RecordType);
        $this->assertEquals('CreatedDate', \ascio\dns\SearchZoneField::CreatedDate);
        $this->assertEquals('TTL', \ascio\dns\SearchZoneField::TTL);
    }

    #[Test]
    public function zoneInfoLevelHasCorrectValues(): void
    {
        $this->assertEquals('Basic', \ascio\dns\ZoneInfoLevel::Basic);
        $this->assertEquals('Full', \ascio\dns\ZoneInfoLevel::Full);
        $this->assertEquals('Partial', \ascio\dns\ZoneInfoLevel::Partial);
    }

    #[Test]
    public function redirectionTypeHasCorrectValues(): void
    {
        $this->assertEquals('Temporary', \ascio\dns\RedirectionType::Temporary);
        $this->assertEquals('Permanent', \ascio\dns\RedirectionType::Permanent);
        $this->assertEquals('Frame', \ascio\dns\RedirectionType::Frame);
    }

    // =========================================================================
    // Request/Response Class Tests
    // =========================================================================

    #[Test]
    public function createZoneRequestHasCorrectProperties(): void
    {
        $request = new \ascio\dns\CreateZone();

        $this->assertObjectHasProperty('zoneName', $request);
        $this->assertObjectHasProperty('owner', $request);
        $this->assertObjectHasProperty('records', $request);
    }

    #[Test]
    public function deleteZoneRequestHasCorrectProperties(): void
    {
        $request = new \ascio\dns\DeleteZone();

        $this->assertObjectHasProperty('zoneName', $request);
    }

    #[Test]
    public function getZoneRequestHasCorrectProperties(): void
    {
        $request = new \ascio\dns\GetZone();

        $this->assertObjectHasProperty('zoneName', $request);
    }

    #[Test]
    public function createRecordRequestHasCorrectProperties(): void
    {
        $request = new \ascio\dns\CreateRecord();

        $this->assertObjectHasProperty('zoneName', $request);
        $this->assertObjectHasProperty('record', $request);
    }

    #[Test]
    public function updateRecordRequestHasCorrectProperties(): void
    {
        $request = new \ascio\dns\UpdateRecord();

        $this->assertObjectHasProperty('record', $request);
    }

    #[Test]
    public function deleteRecordRequestHasCorrectProperties(): void
    {
        $request = new \ascio\dns\DeleteRecord();

        $this->assertObjectHasProperty('recordId', $request);
    }

    #[Test]
    public function getRecordRequestHasCorrectProperties(): void
    {
        $request = new \ascio\dns\GetRecord();

        $this->assertObjectHasProperty('recordId', $request);
    }

    #[Test]
    public function createRecordResponseHasCorrectProperties(): void
    {
        $response = new \ascio\dns\CreateRecordResponse();

        $this->assertObjectHasProperty('CreateRecordResult', $response);
        $this->assertObjectHasProperty('recordId', $response);
    }

    #[Test]
    public function getZoneResponseHasCorrectProperties(): void
    {
        $response = new \ascio\dns\GetZoneResponse();

        $this->assertObjectHasProperty('GetZoneResult', $response);
        $this->assertObjectHasProperty('zone', $response);
    }

    // =========================================================================
    // Search Clause Tests
    // =========================================================================

    #[Test]
    public function searchUserClauseHasCorrectProperties(): void
    {
        $clause = new \ascio\dns\SearchUserClause();

        $this->assertObjectHasProperty('Operator', $clause);
        $this->assertObjectHasProperty('SearchUserField', $clause);
        $this->assertObjectHasProperty('Value', $clause);
    }

    #[Test]
    public function searchUserClauseCanBeConfigured(): void
    {
        $clause = new \ascio\dns\SearchUserClause();
        $clause->Operator = \ascio\dns\SearchOperatorType::Like;
        $clause->SearchUserField = \ascio\dns\SearchUserField::Email;
        $clause->Value = '%@example.com';

        $this->assertEquals('Like', $clause->Operator);
        $this->assertEquals('Email', $clause->SearchUserField);
        $this->assertEquals('%@example.com', $clause->Value);
    }

    #[Test]
    public function searchZoneClauseHasCorrectProperties(): void
    {
        $clause = new \ascio\dns\SearchZoneClause();

        $this->assertObjectHasProperty('Operator', $clause);
        $this->assertObjectHasProperty('SearchZoneField', $clause);
        $this->assertObjectHasProperty('Value', $clause);
    }

    #[Test]
    public function searchZoneClauseCanBeConfigured(): void
    {
        $clause = new \ascio\dns\SearchZoneClause();
        $clause->Operator = \ascio\dns\SearchOperatorType::Is;
        $clause->SearchZoneField = \ascio\dns\SearchZoneField::ZoneName;
        $clause->Value = 'example.com';

        $this->assertEquals('Is', $clause->Operator);
        $this->assertEquals('ZoneName', $clause->SearchZoneField);
        $this->assertEquals('example.com', $clause->Value);
    }

    // =========================================================================
    // User Management Request Tests
    // =========================================================================

    #[Test]
    public function createUserRequestHasCorrectProperties(): void
    {
        $request = new \ascio\dns\CreateUser();

        $this->assertObjectHasProperty('user', $request);
    }

    #[Test]
    public function updateUserRequestHasCorrectProperties(): void
    {
        $request = new \ascio\dns\UpdateUser();

        $this->assertObjectHasProperty('user', $request);
    }

    #[Test]
    public function deleteUserRequestHasCorrectProperties(): void
    {
        $request = new \ascio\dns\DeleteUser();

        $this->assertObjectHasProperty('userName', $request);
    }

    #[Test]
    public function getUserRequestHasCorrectProperties(): void
    {
        $request = new \ascio\dns\GetUser();

        $this->assertObjectHasProperty('userName', $request);
    }

    #[Test]
    public function changePasswordRequestHasCorrectProperties(): void
    {
        $request = new \ascio\dns\ChangePassword();

        $this->assertObjectHasProperty('userName', $request);
        $this->assertObjectHasProperty('newPassword', $request);
    }

    // =========================================================================
    // Web Forward Tests
    // =========================================================================

    #[Test]
    public function webForwardHasCorrectProperties(): void
    {
        $forward = new \ascio\dns\WebForward();

        $this->assertObjectHasProperty('Source', $forward);
        $this->assertObjectHasProperty('Target', $forward);
        $this->assertObjectHasProperty('TTL', $forward);
        $this->assertObjectHasProperty('RedirectionType', $forward);
    }

    #[Test]
    public function webForwardCanConfigureRedirection(): void
    {
        $forward = new \ascio\dns\WebForward();
        $forward->Source = 'old.example.com';
        $forward->Target = 'https://new.example.com';
        $forward->TTL = 300;
        $forward->RedirectionType = \ascio\dns\RedirectionType::Permanent;

        $this->assertEquals('old.example.com', $forward->Source);
        $this->assertEquals('https://new.example.com', $forward->Target);
        $this->assertEquals(300, $forward->TTL);
        $this->assertEquals('Permanent', $forward->RedirectionType);
    }

    // =========================================================================
    // Zone Log Tests
    // =========================================================================

    #[Test]
    public function getZoneLogRequestHasCorrectProperties(): void
    {
        $request = new \ascio\dns\GetZoneLog();

        $this->assertObjectHasProperty('zoneName', $request);
    }

    #[Test]
    public function zoneLogEntryHasCorrectProperties(): void
    {
        $entry = new \ascio\dns\ZoneLogEntry();

        $this->assertObjectHasProperty('Action', $entry);
        $this->assertObjectHasProperty('ActionBy', $entry);
        $this->assertObjectHasProperty('ActionByIpAddress', $entry);
        $this->assertObjectHasProperty('ActionDate', $entry);
        $this->assertObjectHasProperty('Record', $entry);
        $this->assertObjectHasProperty('ZoneName', $entry);
    }

    // =========================================================================
    // RoleItem Tests
    // =========================================================================

    #[Test]
    public function roleItemHasCorrectProperties(): void
    {
        $role = new \ascio\dns\RoleItem();

        $this->assertObjectHasProperty('Rights', $role);
        $this->assertObjectHasProperty('Role', $role);
    }

    // =========================================================================
    // DnsService Class Tests
    // =========================================================================

    #[Test]
    public function dnsServiceClassExists(): void
    {
        $this->assertTrue(class_exists(\ascio\dns\DnsService::class));
    }

    #[Test]
    public function dnsServiceExtendsSoapClient(): void
    {
        $reflection = new \ReflectionClass(\ascio\dns\DnsService::class);
        $this->assertTrue($reflection->isSubclassOf(\SoapClient::class));
    }

    #[Test]
    public function dnsServiceHasClassmap(): void
    {
        $reflection = new \ReflectionClass(\ascio\dns\DnsService::class);
        $classmap = $reflection->getProperty('classmap');
        $classmap->setAccessible(true);

        // Get default value
        $classmapValue = $classmap->getDefaultValue();

        $this->assertIsArray($classmapValue);
        $this->assertArrayHasKey('Zone', $classmapValue);
        $this->assertArrayHasKey('Record', $classmapValue);
        $this->assertArrayHasKey('A', $classmapValue);
        $this->assertArrayHasKey('AAAA', $classmapValue);
        $this->assertArrayHasKey('CNAME', $classmapValue);
        $this->assertArrayHasKey('MX', $classmapValue);
        $this->assertArrayHasKey('TXT', $classmapValue);
    }

    #[Test]
    public function dnsServiceClassmapPointsToCorrectNamespace(): void
    {
        $reflection = new \ReflectionClass(\ascio\dns\DnsService::class);
        $classmap = $reflection->getProperty('classmap');
        $classmap->setAccessible(true);

        $classmapValue = $classmap->getDefaultValue();

        $this->assertEquals('ascio\dns\Zone', $classmapValue['Zone']);
        $this->assertEquals('ascio\dns\A', $classmapValue['A']);
        $this->assertEquals('ascio\dns\Response', $classmapValue['Response']);
    }

    #[Test]
    public function dnsServiceHasRequiredMethods(): void
    {
        $reflection = new \ReflectionClass(\ascio\dns\DnsService::class);

        $this->assertTrue($reflection->hasMethod('GetRoles'));
        $this->assertTrue($reflection->hasMethod('CreateUser'));
        $this->assertTrue($reflection->hasMethod('UpdateUser'));
        $this->assertTrue($reflection->hasMethod('DeleteUser'));
        $this->assertTrue($reflection->hasMethod('GetUser'));
        $this->assertTrue($reflection->hasMethod('SearchUser'));
        $this->assertTrue($reflection->hasMethod('ChangePassword'));
        $this->assertTrue($reflection->hasMethod('CreateZone'));
        $this->assertTrue($reflection->hasMethod('DeleteZone'));
        $this->assertTrue($reflection->hasMethod('GetZone'));
        $this->assertTrue($reflection->hasMethod('GetZoneLog'));
        $this->assertTrue($reflection->hasMethod('RestoreZone'));
        $this->assertTrue($reflection->hasMethod('SearchZoneNames'));
        $this->assertTrue($reflection->hasMethod('SearchZone'));
        $this->assertTrue($reflection->hasMethod('SetZoneOwner'));
        $this->assertTrue($reflection->hasMethod('CreateRecord'));
        $this->assertTrue($reflection->hasMethod('UpdateRecord'));
        $this->assertTrue($reflection->hasMethod('DeleteRecord'));
        $this->assertTrue($reflection->hasMethod('GetRecord'));
    }

    #[Test]
    public function dnsServiceMethodsArePublic(): void
    {
        $reflection = new \ReflectionClass(\ascio\dns\DnsService::class);

        $publicMethods = [
            'CreateZone', 'DeleteZone', 'GetZone', 'SearchZone',
            'CreateRecord', 'UpdateRecord', 'DeleteRecord', 'GetRecord'
        ];

        foreach ($publicMethods as $method) {
            $methodReflection = $reflection->getMethod($method);
            $this->assertTrue($methodReflection->isPublic(), "Method $method should be public");
        }
    }

    // =========================================================================
    // DataProvider Tests
    // =========================================================================

    #[Test]
    #[DataProvider('recordTypeProvider')]
    public function allRecordTypesExist(string $className): void
    {
        $fullClassName = 'ascio\\dns\\' . $className;
        $this->assertTrue(class_exists($fullClassName), "Class $fullClassName should exist");
    }

    public static function recordTypeProvider(): array
    {
        return [
            'A record' => ['A'],
            'AAAA record' => ['AAAA'],
            'CNAME record' => ['CNAME'],
            'MX record' => ['MX'],
            'TXT record' => ['TXT'],
            'SRV record' => ['SRV'],
            'SOA record' => ['SOA'],
            'NS record' => ['NS'],
            'PTR record' => ['PTR'],
        ];
    }

    #[Test]
    #[DataProvider('requestResponseProvider')]
    public function requestResponseClassesExist(string $requestClass, string $responseClass): void
    {
        $fullRequestClass = 'ascio\\dns\\' . $requestClass;
        $fullResponseClass = 'ascio\\dns\\' . $responseClass;

        $this->assertTrue(class_exists($fullRequestClass), "Request class $fullRequestClass should exist");
        $this->assertTrue(class_exists($fullResponseClass), "Response class $fullResponseClass should exist");
    }

    public static function requestResponseProvider(): array
    {
        return [
            'Zone operations' => ['CreateZone', 'CreateZoneResponse'],
            'Zone delete' => ['DeleteZone', 'DeleteZoneResponse'],
            'Zone get' => ['GetZone', 'GetZoneResponse'],
            'Zone restore' => ['RestoreZone', 'RestoreZoneResponse'],
            'Record operations' => ['CreateRecord', 'CreateRecordResponse'],
            'Record delete' => ['DeleteRecord', 'DeleteRecordResponse'],
            'Record get' => ['GetRecord', 'GetRecordResponse'],
            'Record update' => ['UpdateRecord', 'UpdateRecordResponse'],
            'User create' => ['CreateUser', 'CreateUserResponse'],
            'User delete' => ['DeleteUser', 'DeleteUserResponse'],
            'User get' => ['GetUser', 'GetUserResponse'],
            'User update' => ['UpdateUser', 'UpdateUserResponse'],
            'Password change' => ['ChangePassword', 'ChangePasswordResponse'],
            'Roles get' => ['GetRoles', 'GetRolesResponse'],
            'Zone search' => ['SearchZone', 'SearchZoneResponse'],
            'Zone names search' => ['SearchZoneNames', 'SearchZoneNamesResponse'],
            'User search' => ['SearchUser', 'SearchUserResponse'],
            'Zone owner' => ['SetZoneOwner', 'SetZoneOwnerResponse'],
            'Zone log' => ['GetZoneLog', 'GetZoneLogResponse'],
        ];
    }
}
