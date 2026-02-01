<?php

/**
 * Unit tests for TMCH (Trademark Clearinghouse) business logic.
 *
 * Tests the Tmch class methods with mocked API client and database.
 */

declare(strict_types=1);

namespace Ascio\Tmch\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use Ascio\Tmch\Tmch;
use Ascio\Core\Tests\MockAscioClient;
use Ascio\Core\Tests\MockDatabase;
use Ascio\Core\Tests\MockParams;
use Ascio\Core\OrderType;

require_once __DIR__ . '/bootstrap.php';

#[Group('unit')]
#[Group('tmch')]
class TmchTest extends TestCase
{
    protected MockAscioClient $client;
    protected MockDatabase $db;
    protected MockParams $params;
    protected Tmch $tmch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new MockAscioClient();
        $this->db = new MockDatabase();
        $this->params = (new MockParams())->setServiceId(1)->setUserId(1);

        $this->tmch = new Tmch($this->params, $this->client, $this->db);

        // Seed initial test data
        $this->db->insert('mod_ascio_tmch', [
            'whmcs_service_id' => 1,
            'user_id' => 1,
            'mark_name' => 'TestBrand',
            'mark_type' => 'Trademark',
            'service_type' => 'Sunrise',
            'goods_and_services' => 'Software development services',
            'application_id' => 'APP123',
            'registration_number' => 'REG456',
            'jurisdiction' => 'US',
            'notification_frequency' => 'Daily',
            'claim_email_1' => 'admin@testbrand.com',
            'period' => 1,
            'status' => 'Pending',
            'owner_name' => 'John Doe',
            'owner_email' => 'john@testbrand.com',
            'owner_company' => 'TestBrand Inc',
            'owner_address1' => '123 Main St',
            'owner_city' => 'New York',
            'owner_state' => 'NY',
            'owner_postcode' => '10001',
            'owner_country' => 'US',
            'owner_phone' => '+1.5551234567',
        ]);
    }

    protected function tearDown(): void
    {
        $this->db->clear();
        $this->client->reset();
        parent::tearDown();
    }

    // =========================================================================
    // fromForm() Tests
    // =========================================================================

    #[Test]
    public function fromFormSetsAllMarkData(): void
    {
        $formData = [
            'mark_name' => 'NewBrand',
            'mark_type' => 'TreatyOrStatute',
            'service_type' => 'Claims',
            'goods_and_services' => 'Legal services',
            'notification_frequency' => 'Weekly',
            'claim_email_1' => 'claims@newbrand.com',
            'application_id' => 'APP789',
            'registration_number' => 'REG012',
            'jurisdiction' => 'EU',
            'period' => 2,
        ];

        $this->tmch->fromForm($formData);
        $data = $this->tmch->getData();

        $this->assertEquals('NewBrand', $data['mark_name']);
        $this->assertEquals('TreatyOrStatute', $data['mark_type']);
        $this->assertEquals('Claims', $data['service_type']);
        $this->assertEquals('Legal services', $data['goods_and_services']);
        $this->assertEquals('Weekly', $data['notification_frequency']);
        $this->assertEquals('claims@newbrand.com', $data['claim_email_1']);
        $this->assertEquals(2, $data['period']);
    }

    #[Test]
    public function fromFormSetsOwnerContactData(): void
    {
        $formData = [
            'mark_name' => 'ContactTestBrand',
            'owner_name' => 'Jane Smith',
            'owner_email' => 'jane@company.com',
            'owner_company' => 'Smith Corp',
            'owner_address1' => '456 Oak Ave',
            'owner_address2' => 'Suite 100',
            'owner_city' => 'Boston',
            'owner_state' => 'MA',
            'owner_postcode' => '02101',
            'owner_country' => 'US',
            'owner_phone' => '+1.6175551234',
        ];

        $this->tmch->fromForm($formData);
        $data = $this->tmch->getData();

        $this->assertEquals('Jane Smith', $data['owner_name']);
        $this->assertEquals('jane@company.com', $data['owner_email']);
        $this->assertEquals('Smith Corp', $data['owner_company']);
        $this->assertEquals('456 Oak Ave', $data['owner_address1']);
        $this->assertEquals('Suite 100', $data['owner_address2']);
        $this->assertEquals('Boston', $data['owner_city']);
        $this->assertEquals('MA', $data['owner_state']);
        $this->assertEquals('02101', $data['owner_postcode']);
        $this->assertEquals('US', $data['owner_country']);
    }

    #[Test]
    public function fromFormSetsDefaultsForMissingData(): void
    {
        $formData = [
            'mark_name' => 'MinimalBrand',
        ];

        $this->tmch->fromForm($formData);
        $data = $this->tmch->getData();

        $this->assertEquals('MinimalBrand', $data['mark_name']);
        $this->assertEquals('Trademark', $data['mark_type']);
        $this->assertEquals('Sunrise', $data['service_type']);
        $this->assertEquals('Daily', $data['notification_frequency']);
        $this->assertEquals(1, $data['period']);
    }

    #[Test]
    public function fromFormHandlesLabelsArray(): void
    {
        $formData = [
            'mark_name' => 'LabelBrand',
            'labels' => ['label1', 'label2', 'label3'],
        ];

        $this->tmch->fromForm($formData);
        $data = $this->tmch->getData();

        $this->assertNotNull($data['labels']);
        $labels = json_decode($data['labels'], true);
        $this->assertEquals(['label1', 'label2', 'label3'], $labels);
    }

    #[Test]
    public function fromFormReturnsFluentInterface(): void
    {
        $result = $this->tmch->fromForm(['mark_name' => 'Test']);
        $this->assertInstanceOf(Tmch::class, $result);
    }

    // =========================================================================
    // readDb() / writeDb() Tests
    // =========================================================================

    #[Test]
    public function readDbLoadsExistingData(): void
    {
        $data = $this->tmch->readDb();

        $this->assertNotNull($data);
        $this->assertEquals('TestBrand', $data->mark_name);
        $this->assertEquals('Trademark', $data->mark_type);
        $this->assertEquals('Sunrise', $data->service_type);
        $this->assertEquals('US', $data->jurisdiction);
    }

    #[Test]
    public function readDbReturnsNullForNonExistentService(): void
    {
        $params = (new MockParams())->setServiceId(999)->setUserId(1);
        $tmch = new Tmch($params, $this->client, $this->db);

        $data = $tmch->readDb();

        $this->assertNull($data);
    }

    #[Test]
    public function readDbCachesDataOnSubsequentCalls(): void
    {
        // First call loads from DB
        $data1 = $this->tmch->readDb();
        $originalName = $data1->mark_name;

        // Modify DB directly
        $this->db->update('mod_ascio_tmch', ['mark_name' => 'ModifiedName'], ['whmcs_service_id' => 1]);

        // Second call should return cached data (not the modified DB value)
        $data2 = $this->tmch->readDb();

        $this->assertEquals($originalName, $data2->mark_name);
        $this->assertEquals('TestBrand', $data2->mark_name);
    }

    #[Test]
    public function writeDbUpdatesExistingRecord(): void
    {
        $this->tmch->readDb(); // Load existing data

        $this->tmch->fromForm([
            'mark_name' => 'UpdatedBrand',
            'mark_type' => 'CourtValidated',
        ]);
        $this->tmch->writeDb();

        $data = $this->db->first('mod_ascio_tmch', ['*'], ['whmcs_service_id' => 1]);
        $this->assertEquals('UpdatedBrand', $data->mark_name);
        $this->assertEquals('CourtValidated', $data->mark_type);
    }

    #[Test]
    public function writeDbInsertsNewRecord(): void
    {
        $this->db->clear();
        $params = (new MockParams())->setServiceId(2)->setUserId(2);
        $tmch = new Tmch($params, $this->client, $this->db);

        $tmch->fromForm([
            'mark_name' => 'NewBrand',
            'mark_type' => 'Trademark',
            'service_type' => 'Claims',
            'goods_and_services' => 'New services',
            'period' => 1,
        ]);
        $tmch->writeDb();

        $data = $this->db->first('mod_ascio_tmch', ['*'], ['whmcs_service_id' => 2]);
        $this->assertNotNull($data);
        $this->assertEquals('NewBrand', $data->mark_name);
        $this->assertEquals(2, $data->whmcs_service_id);
        $this->assertEquals(2, $data->user_id);
    }

    // =========================================================================
    // register() Tests
    // =========================================================================

    #[Test]
    public function registerCallsCreateOrderWithCorrectType(): void
    {
        $contactData = [
            'owner_name' => 'John Doe',
            'owner_email' => 'john@test.com',
            'owner_company' => 'Test Corp',
            'owner_address1' => '123 Main St',
            'owner_city' => 'New York',
            'owner_state' => 'NY',
            'owner_postcode' => '10001',
            'owner_country' => 'US',
            'owner_phone' => '+1.5551234567',
        ];

        $result = $this->tmch->register($contactData);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('order_id', $result);

        $calls = $this->client->getCalls('createOrder');
        $this->assertCount(1, $calls);
    }

    #[Test]
    public function registerReturnsOrderIdWithTestPrefix(): void
    {
        $contactData = [
            'owner_name' => 'John Doe',
            'owner_email' => 'john@test.com',
        ];

        $result = $this->tmch->register($contactData);

        $this->assertTrue($result['success']);
        $this->assertStringStartsWith('TEST', $result['order_id']);
    }

    #[Test]
    public function registerUpdatesDatabase(): void
    {
        $contactData = [
            'owner_name' => 'John Doe',
            'owner_email' => 'john@test.com',
        ];

        $result = $this->tmch->register($contactData);

        $data = $this->db->first('mod_ascio_tmch', ['*'], ['whmcs_service_id' => 1]);
        $this->assertNotNull($data->order_id);
        $this->assertEquals(200, $data->code);
        $this->assertEquals('Success', $data->message);
    }

    #[Test]
    public function registerWithDocumentsIncludesDocuments(): void
    {
        $contactData = ['owner_name' => 'John Doe', 'owner_email' => 'john@test.com'];
        $documents = [
            [
                'type' => 'TrademarkCopy',
                'filename' => 'trademark.pdf',
                'content' => 'PDF content here',
            ],
        ];

        $result = $this->tmch->register($contactData, $documents);

        $this->assertTrue($result['success']);

        $calls = $this->client->getCalls('createOrder');
        $this->assertCount(1, $calls);
        // Order request should contain documents
        $this->assertArrayHasKey('orderRequest', $calls[0]);
    }

    #[Test]
    public function registerHandlesApiFailure(): void
    {
        // Queue a failure response
        $failureResult = new class {
            public function getResultCode() { return 400; }
            public function getResultMessage() { return 'Validation failed'; }
            public function getErrors() {
                return new class {
                    public function getErrorCode() { return []; }
                    public function getString() { return ['Invalid mark name']; }
                };
            }
        };

        $this->client->queueResponse('createOrder', (object)['CreateOrderResult' => $failureResult]);

        $contactData = ['owner_name' => 'John Doe', 'owner_email' => 'john@test.com'];
        $result = $this->tmch->register($contactData);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals(400, $result['code']);
    }

    #[Test]
    public function registerHandlesSoapFault(): void
    {
        $this->client->queueException('createOrder', new \SoapFault('Server', 'Connection error'));

        $contactData = ['owner_name' => 'John Doe', 'owner_email' => 'john@test.com'];
        $result = $this->tmch->register($contactData);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Temporary error', $result['error']);
    }

    // =========================================================================
    // renew() Tests
    // =========================================================================

    #[Test]
    public function renewCallsCreateOrderWithRenewType(): void
    {
        // Set up existing handle
        $this->db->update('mod_ascio_tmch', ['handle' => 'MK123'], ['whmcs_service_id' => 1]);

        // Clear cache by creating new instance
        $tmch = new Tmch($this->params, $this->client, $this->db);

        $contactData = ['owner_name' => 'John Doe', 'owner_email' => 'john@test.com'];
        $result = $tmch->renew($contactData);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('order_id', $result);

        $calls = $this->client->getCalls('createOrder');
        $this->assertCount(1, $calls);
    }

    #[Test]
    public function renewUpdatesStatusInDatabase(): void
    {
        $this->db->update('mod_ascio_tmch', ['handle' => 'MK123'], ['whmcs_service_id' => 1]);
        $tmch = new Tmch($this->params, $this->client, $this->db);

        $contactData = ['owner_name' => 'John Doe', 'owner_email' => 'john@test.com'];
        $result = $tmch->renew($contactData);

        $data = $this->db->first('mod_ascio_tmch', ['*'], ['whmcs_service_id' => 1]);
        $this->assertNotNull($data->order_id);
        $this->assertEquals(200, $data->code);
    }

    // =========================================================================
    // terminate() Tests
    // =========================================================================

    #[Test]
    public function terminateCallsCreateOrderWithDeleteType(): void
    {
        $this->db->update('mod_ascio_tmch', ['handle' => 'MK123'], ['whmcs_service_id' => 1]);
        $tmch = new Tmch($this->params, $this->client, $this->db);

        $result = $tmch->terminate();

        $this->assertTrue($result['success']);

        $calls = $this->client->getCalls('createOrder');
        $this->assertCount(1, $calls);
    }

    #[Test]
    public function terminateUpdatesStatusInDatabase(): void
    {
        $this->db->update('mod_ascio_tmch', ['handle' => 'MK123'], ['whmcs_service_id' => 1]);
        $tmch = new Tmch($this->params, $this->client, $this->db);

        $result = $tmch->terminate();

        $data = $this->db->first('mod_ascio_tmch', ['*'], ['whmcs_service_id' => 1]);
        $this->assertEquals(200, $data->code);
    }

    // =========================================================================
    // getInfo() Tests
    // =========================================================================

    #[Test]
    public function getInfoReturnsMarkInfo(): void
    {
        $info = $this->tmch->getInfo('MK123');

        $this->assertNotNull($info);
        $this->assertEquals('MK123', $info->getHandle());

        $calls = $this->client->getCalls('getMark');
        $this->assertCount(1, $calls);
        $this->assertEquals('MK123', $calls[0]['handle']);
    }

    #[Test]
    public function getInfoIncludesExpiryDate(): void
    {
        $info = $this->tmch->getInfo('MK123');

        $this->assertEquals('2025-12-31', $info->getExpDate());
    }

    #[Test]
    public function getInfoIncludesMarkId(): void
    {
        $info = $this->tmch->getInfo('MK123');

        $this->assertEquals('TMCHMK123', $info->getMarkId());
    }

    // =========================================================================
    // getSMD() Tests
    // =========================================================================

    #[Test]
    public function getSMDReturnsErrorWithoutHandle(): void
    {
        // No handle set in database
        $result = $this->tmch->getSMD();

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('handle not yet assigned', $result['error']);
    }

    #[Test]
    public function getSMDReturnsSuccessWithHandle(): void
    {
        $this->db->update('mod_ascio_tmch', ['handle' => 'MK123'], ['whmcs_service_id' => 1]);

        // Queue a response with SMD data
        $info = new class {
            public function getHandle() { return 'MK123'; }
            public function getExpDate() { return '2025-12-31'; }
            public function getMarkId() { return 'TMCHMK123'; }
            public function getSmd() { return '<?xml version="1.0"?><smd>SMD DATA</smd>'; }
        };

        $result = new class($info) {
            private $info;
            public function __construct($info) { $this->info = $info; }
            public function getResultCode() { return 200; }
            public function getMarkInfo() { return $this->info; }
        };

        $this->client->queueResponse('getMark', (object)['GetMarkResult' => $result]);

        $tmch = new Tmch($this->params, $this->client, $this->db);
        $result = $tmch->getSMD();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('filename', $result);
        $this->assertStringContainsString('SMD DATA', $result['content']);
    }

    #[Test]
    public function getSMDReturnsErrorWhenSMDNotAvailable(): void
    {
        $this->db->update('mod_ascio_tmch', ['handle' => 'MK123'], ['whmcs_service_id' => 1]);

        // Queue a response without SMD data
        $info = new class {
            public function getHandle() { return 'MK123'; }
            public function getExpDate() { return '2025-12-31'; }
            public function getMarkId() { return 'TMCHMK123'; }
            public function getSmd() { return null; }
        };

        $result = new class($info) {
            private $info;
            public function __construct($info) { $this->info = $info; }
            public function getResultCode() { return 200; }
            public function getMarkInfo() { return $this->info; }
        };

        $this->client->queueResponse('getMark', (object)['GetMarkResult' => $result]);

        $tmch = new Tmch($this->params, $this->client, $this->db);
        $result = $tmch->getSMD();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not available', $result['error']);
    }

    #[Test]
    public function getSMDGeneratesFilenameFromMarkName(): void
    {
        $this->db->update('mod_ascio_tmch', ['handle' => 'MK123', 'mark_name' => 'My Brand'], ['whmcs_service_id' => 1]);

        $info = new class {
            public function getHandle() { return 'MK123'; }
            public function getExpDate() { return '2025-12-31'; }
            public function getMarkId() { return 'TMCHMK123'; }
            public function getSmd() { return '<smd>data</smd>'; }
        };

        $result = new class($info) {
            private $info;
            public function __construct($info) { $this->info = $info; }
            public function getResultCode() { return 200; }
            public function getMarkInfo() { return $this->info; }
        };

        $this->client->queueResponse('getMark', (object)['GetMarkResult' => $result]);

        $tmch = new Tmch($this->params, $this->client, $this->db);
        $result = $tmch->getSMD();

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('smd_', $result['filename']);
        $this->assertStringContainsString('.smd', $result['filename']);
    }

    // =========================================================================
    // uploadDocumentation() Tests
    // =========================================================================

    #[Test]
    public function uploadDocumentationReturnsErrorWithoutHandle(): void
    {
        // No order_id set
        $documents = [
            ['type' => 'TrademarkCopy', 'filename' => 'doc.pdf', 'content' => 'PDF content'],
        ];

        $result = $this->tmch->uploadDocumentation($documents);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('order not yet created', $result['error']);
    }

    #[Test]
    public function uploadDocumentationCallsApiWithDocuments(): void
    {
        $this->db->update('mod_ascio_tmch', ['handle' => 'MK123', 'order_id' => 'TEST1001'], ['whmcs_service_id' => 1]);
        $tmch = new Tmch($this->params, $this->client, $this->db);

        $documents = [
            ['type' => 'TrademarkCopy', 'filename' => 'trademark.pdf', 'content' => 'PDF content'],
            ['type' => 'ProofOfUse', 'filename' => 'proof.pdf', 'content' => 'Proof content'],
        ];

        $result = $tmch->uploadDocumentation($documents);

        $this->assertTrue($result['success']);

        $calls = $this->client->getCalls('uploadDocumentation');
        $this->assertCount(1, $calls);
    }

    #[Test]
    public function uploadDocumentationUpdatesDocumentsUploadedFlag(): void
    {
        $this->db->update('mod_ascio_tmch', ['handle' => 'MK123', 'order_id' => 'TEST1001'], ['whmcs_service_id' => 1]);
        $tmch = new Tmch($this->params, $this->client, $this->db);

        $documents = [
            ['type' => 'TrademarkCopy', 'filename' => 'trademark.pdf', 'content' => 'PDF content'],
        ];

        $result = $tmch->uploadDocumentation($documents);

        $this->assertTrue($result['success']);

        $data = $this->db->first('mod_ascio_tmch', ['*'], ['whmcs_service_id' => 1]);
        $this->assertEquals(1, $data->documents_uploaded);
    }

    #[Test]
    public function uploadDocumentationHandlesApiFailure(): void
    {
        $this->db->update('mod_ascio_tmch', ['handle' => 'MK123', 'order_id' => 'TEST1001'], ['whmcs_service_id' => 1]);

        $failureResult = new class {
            public function getResultCode() { return 400; }
            public function getResultMessage() { return 'Upload failed'; }
            public function getErrors() {
                return new class {
                    public function getErrorCode() { return []; }
                    public function getString() { return ['Invalid document format']; }
                };
            }
        };

        $this->client->queueResponse('uploadDocumentation', (object)['UploadDocumentationResult' => $failureResult]);

        $tmch = new Tmch($this->params, $this->client, $this->db);
        $documents = [
            ['type' => 'TrademarkCopy', 'filename' => 'invalid.xyz', 'content' => 'content'],
        ];

        $result = $tmch->uploadDocumentation($documents);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    #[Test]
    public function uploadDocumentationHandlesSoapFault(): void
    {
        $this->db->update('mod_ascio_tmch', ['handle' => 'MK123', 'order_id' => 'TEST1001'], ['whmcs_service_id' => 1]);

        $this->client->queueException('uploadDocumentation', new \SoapFault('Server', 'Upload service unavailable'));

        $tmch = new Tmch($this->params, $this->client, $this->db);
        $documents = [
            ['type' => 'TrademarkCopy', 'filename' => 'doc.pdf', 'content' => 'content'],
        ];

        $result = $tmch->uploadDocumentation($documents);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Temporary error', $result['error']);
    }

    // =========================================================================
    // buildOwner() Tests (via register)
    // =========================================================================

    #[Test]
    public function buildOwnerAcceptsOwnerPrefixedKeys(): void
    {
        $contactData = [
            'owner_name' => 'John Doe',
            'owner_email' => 'john@test.com',
            'owner_company' => 'Test Corp',
            'owner_address1' => '123 Main St',
            'owner_city' => 'Boston',
            'owner_state' => 'MA',
            'owner_postcode' => '02101',
            'owner_country' => 'US',
            'owner_phone' => '+1.6175551234',
        ];

        $result = $this->tmch->register($contactData);

        $this->assertTrue($result['success']);
        $calls = $this->client->getCalls('createOrder');
        $this->assertCount(1, $calls);
    }

    #[Test]
    public function buildOwnerAcceptsNonPrefixedKeys(): void
    {
        $contactData = [
            'name' => 'Jane Smith',
            'email' => 'jane@test.com',
            'company' => 'Smith Corp',
            'address1' => '456 Oak Ave',
            'city' => 'Seattle',
            'state' => 'WA',
            'postcode' => '98101',
            'country' => 'US',
            'phone' => '+1.2065551234',
        ];

        $result = $this->tmch->register($contactData);

        $this->assertTrue($result['success']);
    }

    // =========================================================================
    // toForm() Tests
    // =========================================================================

    #[Test]
    public function toFormLoadsDataFromDatabase(): void
    {
        $data = $this->tmch->toForm();

        $this->assertIsArray($data);
        $this->assertEquals('TestBrand', $data['mark_name']);
        $this->assertEquals('Trademark', $data['mark_type']);
    }

    #[Test]
    public function toFormReturnsSetDataBeforeDbLoad(): void
    {
        $params = (new MockParams())->setServiceId(999)->setUserId(1);
        $tmch = new Tmch($params, $this->client, $this->db);

        $tmch->fromForm(['mark_name' => 'FormBrand', 'mark_type' => 'CourtValidated']);
        $data = $tmch->toForm();

        $this->assertEquals('FormBrand', $data['mark_name']);
        $this->assertEquals('CourtValidated', $data['mark_type']);
    }

    // =========================================================================
    // Service ID Tests
    // =========================================================================

    #[Test]
    public function getServiceIdReturnsConfiguredId(): void
    {
        $this->assertEquals(1, $this->tmch->getServiceId());
    }

    #[Test]
    public function setServiceIdUpdatesId(): void
    {
        $this->tmch->setServiceId(42);
        $this->assertEquals(42, $this->tmch->getServiceId());
    }

    #[Test]
    public function setServiceIdReturnsFluentInterface(): void
    {
        $result = $this->tmch->setServiceId(42);
        $this->assertInstanceOf(Tmch::class, $result);
    }

    // =========================================================================
    // Mark Type Tests
    // =========================================================================

    #[Test]
    public function markTypesConstantContainsValidTypes(): void
    {
        $this->assertContains('Trademark', Tmch::MARK_TYPES);
        $this->assertContains('TreatyOrStatute', Tmch::MARK_TYPES);
        $this->assertContains('CourtValidated', Tmch::MARK_TYPES);
        $this->assertCount(3, Tmch::MARK_TYPES);
    }

    #[Test]
    public function serviceTypesConstantContainsValidTypes(): void
    {
        $this->assertContains('Sunrise', Tmch::SERVICE_TYPES);
        $this->assertContains('Claims', Tmch::SERVICE_TYPES);
        $this->assertCount(2, Tmch::SERVICE_TYPES);
    }

    // =========================================================================
    // Order ID Formatting Tests
    // =========================================================================

    #[Test]
    public function orderIdHasTestPrefixInTestMode(): void
    {
        $contactData = ['owner_name' => 'John Doe', 'owner_email' => 'john@test.com'];
        $result = $this->tmch->register($contactData);

        $this->assertTrue($result['success']);
        $this->assertStringStartsWith('TEST', $result['order_id']);
    }

    #[Test]
    public function orderIdHasAPrefixInLiveMode(): void
    {
        $params = (new MockParams('live_account', 'live_pass', false))
            ->setServiceId(1)
            ->setUserId(1);
        $tmch = new Tmch($params, $this->client, $this->db);

        $contactData = ['owner_name' => 'John Doe', 'owner_email' => 'john@test.com'];
        $result = $tmch->register($contactData);

        $this->assertTrue($result['success']);
        $this->assertStringStartsWith('A', $result['order_id']);
    }

    // =========================================================================
    // Multi-email Claim Notification Tests
    // =========================================================================

    #[Test]
    public function fromFormSetsMultipleClaimEmails(): void
    {
        $formData = [
            'mark_name' => 'MultiEmailBrand',
            'claim_email_1' => 'email1@test.com',
            'claim_email_2' => 'email2@test.com',
            'claim_email_3' => 'email3@test.com',
            'claim_email_4' => 'email4@test.com',
            'claim_email_5' => 'email5@test.com',
        ];

        $this->tmch->fromForm($formData);
        $data = $this->tmch->getData();

        $this->assertEquals('email1@test.com', $data['claim_email_1']);
        $this->assertEquals('email2@test.com', $data['claim_email_2']);
        $this->assertEquals('email3@test.com', $data['claim_email_3']);
        $this->assertEquals('email4@test.com', $data['claim_email_4']);
        $this->assertEquals('email5@test.com', $data['claim_email_5']);
    }
}
