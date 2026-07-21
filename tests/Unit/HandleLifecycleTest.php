<?php

namespace Ascio\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use ascio\Request;
use Ascio\Tests\Mocks\WhmcsFunctionsMock;
use Ascio\Tests\Mocks\CapsuleMock;
use Ascio\Tests\Mocks\SoapClientMock;
use Ascio\Tests\Mocks\SoapResponseMock;
use Ascio\Tests\Mocks\MockParamsV3;

/**
 * Full callback-lifecycle tests for the persistent Ascio handle cache
 * (tblasciohandles), driven end-to-end through Request::getCallbackData with a
 * mocked SOAP transport.
 *
 * Regression target — miserve.info (whmcs_id 227474):
 *   1. Apr: Transfer FAILED, order carried handle MISERVEI63560.
 *   2. Jun: Transfer COMPLETED, new registry handle MISERVEI31291.
 * The completed handle never replaced the stale one, so GetDomain-by-handle kept
 * returning the dead MISERVEI63560. Two defects:
 *   D1 — a failed/invalid callback must never persist a handle.
 *   D2 — a completed callback must overwrite an existing (changed) handle.
 *
 * @covers \ascio\Request
 */
class HandleLifecycleTest extends TestCase
{
    private const DOMAIN = 'miserve.info';
    private const WHMCS_ID = 227474;

    protected function setUp(): void
    {
        parent::setUp();
        WhmcsFunctionsMock::reset();
        CapsuleMock::reset();
        SoapClientMock::reset();

        // The domain must resolve as an Ascio-registered WHMCS domain.
        CapsuleMock::setTableData('tbldomains', [
            [
                'id' => self::WHMCS_ID,
                'domain' => self::DOMAIN,
                'registrar' => 'ascio',
                'status' => 'Pending',
            ],
        ]);
        CapsuleMock::setTableData('tblasciohandles', []);
    }

    /**
     * Build a Request whose SOAP transport is the in-memory mock, and prime the
     * three calls getCallbackData makes: GetQueueMessage, GetOrder, GetDomain.
     */
    private function makeRequest(string $orderType, string $orderStatus, ?string $handle): Request
    {
        $params = array_merge(MockParamsV3::getDefault(), [
            'domainname' => self::DOMAIN,
            'TestMode' => 'on',
        ]);

        $request = new class($params) extends Request {
            protected function makeSoapClient($wsdl) {
                return new SoapClientMock();
            }
        };

        // sendRequest returns $response->{Method}Result, and the module then
        // probes that value with isset($x['error']); so the inner Result object
        // must be array-tolerant (SoapResponseMock).

        // GetQueueMessage -> GetQueueMessageResult
        SoapClientMock::setResponse('GetQueueMessage', (object) [
            'GetQueueMessageResult' => new SoapResponseMock([
                'ResultCode' => 200,
                'DomainName' => self::DOMAIN,
                'ObjectName' => self::DOMAIN,
                'StatusList' => (object) ['CallbackStatus' => []],
            ]),
        ]);

        // GetOrder -> GetOrderResult. TransactionComment carries the WHMCS
        // domainId, exactly like a real WHMCS-initiated order.
        $orderDomain = (object) ['DomainName' => self::DOMAIN];
        if ($handle !== null) {
            $orderDomain->DomainHandle = $handle;
        }
        SoapClientMock::setResponse('GetOrder', (object) [
            'GetOrderResult' => new SoapResponseMock([
                'ResultCode' => 200,
                'Order' => (object) [
                    'Type' => $orderType,
                    'Status' => $orderStatus,
                    'Domain' => $orderDomain,
                    'TransactionComment' => json_encode([
                        'application' => 'WHMCS',
                        'domainId' => self::WHMCS_ID,
                        'objectType' => 'Domain',
                    ]),
                ],
            ]),
        ]);

        // GetDomain(handle) -> GetDomainResult.Domain, echoing that same handle.
        // getCallbackData array-probes the returned domain too, so it is also
        // array-tolerant.
        if ($handle !== null) {
            SoapClientMock::setResponse('GetDomain', (object) [
                'GetDomainResult' => (object) [
                    'ResultCode' => 200,
                    'Domain' => new SoapResponseMock([
                        'DomainName' => self::DOMAIN,
                        'DomainHandle' => $handle,
                        'Status' => 'ACTIVE',
                    ]),
                ],
            ]);
        }

        // AckQueueMessage -> generic 200.
        SoapClientMock::setResponse('AckQueueMessage', (object) [
            'AckQueueMessageResult' => new SoapResponseMock(['ResultCode' => 200]),
        ]);

        return $request;
    }

    private function storedHandle(Request $request): ?string
    {
        return $request->getHandle('domain', self::WHMCS_ID, self::DOMAIN);
    }

    // =========================================================================
    // D1 — failed / invalid callbacks must not persist a handle
    // =========================================================================

    #[Test]
    public function failedTransferCallbackDoesNotStoreHandle(): void
    {
        $request = $this->makeRequest('Transfer', 'Failed', 'MISERVEI63560');

        $request->getCallbackData('Failed', 'MSG-1', 'A100624334', 'Poll-Message');

        $this->assertNull(
            $this->storedHandle($request),
            'A failed transfer must not persist its handle into tblasciohandles'
        );
    }

    #[Test]
    public function invalidTransferCallbackDoesNotStoreHandle(): void
    {
        // Invalid order in the wild carried no handle at all (see screenshot).
        $request = $this->makeRequest('Transfer', 'Invalid', null);

        $request->getCallbackData('Invalid', 'MSG-2', 'A101712949', 'Poll-Message');

        $this->assertNull(
            $this->storedHandle($request),
            'An invalid transfer must not persist a handle'
        );
    }

    // =========================================================================
    // D2 — completed callbacks persist / overwrite the handle
    // =========================================================================

    #[Test]
    public function completedTransferStoresHandle(): void
    {
        $request = $this->makeRequest('Transfer', 'Completed', 'MISERVEI31291');

        $request->getCallbackData('Completed', 'MSG-3', 'A101588707', 'Poll-Message');

        $this->assertEquals(
            'MISERVEI31291',
            $this->storedHandle($request),
            'A completed transfer must persist its registry handle'
        );
    }

    /**
     * The exact miserve.info sequence: a failed transfer runs first, then the
     * successful one. The final stored handle must be the completed order's.
     */
    #[Test]
    public function completedTransferOverwritesStaleFailedHandle(): void
    {
        // Stale handle from the earlier failed transfer already in the cache.
        CapsuleMock::setTableData('tblasciohandles', [
            [
                'type' => 'domain',
                'whmcs_id' => self::WHMCS_ID,
                'domain' => self::DOMAIN,
                'ascio_id' => 'MISERVEI63560',
            ],
        ]);

        $request = $this->makeRequest('Transfer', 'Completed', 'MISERVEI31291');

        $request->getCallbackData('Completed', 'MSG-4', 'A101588707', 'Poll-Message');

        $this->assertEquals(
            'MISERVEI31291',
            $this->storedHandle($request),
            'A completed transfer must overwrite the stale failed-transfer handle'
        );
    }
}
