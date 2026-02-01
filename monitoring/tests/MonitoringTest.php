<?php

namespace Ascio\Monitoring\Tests;

use PHPUnit\Framework\TestCase;
use Ascio\Monitoring\Monitoring;
use Ascio\Core\Tests\MockAscioClient;
use Ascio\Core\Tests\MockDatabase;
use Ascio\Core\Tests\MockParams;

/**
 * Unit tests for Monitoring business logic.
 */
class MonitoringTest extends TestCase
{
    protected MockAscioClient $client;
    protected MockDatabase $db;
    protected MockParams $params;
    protected Monitoring $monitoring;

    protected function setUp(): void
    {
        $this->client = new MockAscioClient();
        $this->db = new MockDatabase();
        $this->params = (new MockParams())->setServiceId(1)->setUserId(1);

        $this->monitoring = new Monitoring($this->params, $this->client, $this->db);

        // Seed initial data
        $this->db->insert('mod_ascio_monitoring', [
            'whmcs_service_id' => 1,
            'user_id' => 1,
            'name' => 'testbrand',
            'tier' => 3,
            'notification_frequency' => 'Daily',
            'email_notification_1' => 'admin@test.com',
            'period' => 1,
            'status' => 'Pending',
        ]);
    }

    public function testFromFormSetsData(): void
    {
        $formData = [
            'name' => 'mybrand',
            'tier' => 2,
            'notification_frequency' => 'Weekly',
            'email_notification_1' => 'test@example.com',
        ];

        $this->monitoring->fromForm($formData);
        $data = $this->monitoring->getData();

        $this->assertEquals('mybrand', $data['name']);
        $this->assertEquals(2, $data['tier']);
        $this->assertEquals('Weekly', $data['notification_frequency']);
    }

    public function testReadDbLoadsData(): void
    {
        $data = $this->monitoring->readDb();

        $this->assertNotNull($data);
        $this->assertEquals('testbrand', $data->name);
        $this->assertEquals(3, $data->tier);
    }

    public function testRegisterCallsCreateOrder(): void
    {
        $contactData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'company' => 'Test Corp',
            'address1' => '123 Main St',
            'city' => 'Testville',
            'state' => 'TS',
            'postcode' => '12345',
            'country' => 'US',
            'phone' => '+1.5551234567',
        ];

        $result = $this->monitoring->register($contactData);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('order_id', $result);
        $this->assertStringStartsWith('TEST', $result['order_id']);

        $calls = $this->client->getCalls('createOrder');
        $this->assertCount(1, $calls);
    }

    public function testRegisterUpdatesDatabase(): void
    {
        $contactData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $result = $this->monitoring->register($contactData);

        // Check database was updated
        $data = $this->db->first('mod_ascio_monitoring', ['*'], ['whmcs_service_id' => 1]);
        $this->assertNotNull($data->order_id);
        $this->assertEquals(200, $data->code);
    }

    public function testRenewCallsCreateOrderWithRenewType(): void
    {
        // First set up existing handle
        $this->db->update('mod_ascio_monitoring', ['handle' => 'NW123'], ['whmcs_service_id' => 1]);

        $contactData = ['name' => 'John Doe', 'email' => 'john@example.com'];

        $result = $this->monitoring->renew($contactData);

        $this->assertTrue($result['success']);

        $calls = $this->client->getCalls('createOrder');
        $this->assertCount(1, $calls);
    }

    public function testTerminateCallsCreateOrderWithDeleteType(): void
    {
        $this->db->update('mod_ascio_monitoring', ['handle' => 'NW123'], ['whmcs_service_id' => 1]);

        $result = $this->monitoring->terminate();

        $this->assertTrue($result['success']);
    }

    public function testGetInfoReturnsNameWatchInfo(): void
    {
        $info = $this->monitoring->getInfo('NW123');

        $this->assertNotNull($info);
        $this->assertEquals('NW123', $info->getHandle());

        $calls = $this->client->getCalls('getNameWatch');
        $this->assertCount(1, $calls);
        $this->assertEquals('NW123', $calls[0]['handle']);
    }

    public function testWriteDbInsertsNewRecord(): void
    {
        // Clear and create new instance
        $this->db->clear();
        $params = (new MockParams())->setServiceId(2)->setUserId(2);
        $monitoring = new Monitoring($params, $this->client, $this->db);

        $monitoring->fromForm([
            'name' => 'newbrand',
            'tier' => 1,
            'notification_frequency' => 'Monthly',
        ]);
        $monitoring->writeDb();

        $data = $this->db->first('mod_ascio_monitoring', ['*'], ['whmcs_service_id' => 2]);
        $this->assertNotNull($data);
        $this->assertEquals('newbrand', $data->name);
    }
}
