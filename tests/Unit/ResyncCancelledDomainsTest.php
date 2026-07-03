<?php

namespace Ascio\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Ascio\Tests\Mocks\CapsuleMock;
use WHMCS\Database\Capsule;

/**
 * Unit tests for the PS-166 remediation script (resync-cancelled-domains.php).
 *
 * Verifies domain selection (only Ascio domains currently Cancelled) and
 * classification (corrected / left cancelled / error) using a fake Request
 * factory, without touching the real Ascio SOAP API.
 *
 * @covers resyncCancelledDomains
 */
class ResyncCancelledDomainsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        CapsuleMock::reset();

        if (!function_exists('resyncCancelledDomains')) {
            require_once __DIR__ . '/../../resync-cancelled-domains.php';
        }
    }

    private function seedDomain(int $id, string $domain, string $registrar, string $status): void
    {
        Capsule::table('tbldomains')->insert([
            'id' => $id,
            'domain' => $domain,
            'registrar' => $registrar,
            'status' => $status,
        ]);
    }

    private function runResync(callable $requestFactory): array
    {
        ob_start();
        $summary = resyncCancelledDomains($requestFactory);
        ob_get_clean();
        return $summary;
    }

    #[Test]
    public function onlyProcessesCancelledAscioDomains(): void
    {
        $this->seedDomain(1, 'active-already.com', 'ascio', 'Active');
        $this->seedDomain(2, 'other-registrar.com', 'opensrspro', 'Cancelled');
        $this->seedDomain(3, 'wrongly-cancelled.com', 'ascio', 'Cancelled');
        $this->seedDomain(4, 'usd-account.com', 'ascio_usd', 'Cancelled');

        $processed = [];
        $summary = $this->runResync(function (string $registrar, int $domainId, string $domainName) use (&$processed) {
            $processed[] = $domainName;
            return new class {
                public function searchDomain() {
                    return (object) ['Status' => 'ACTIVE'];
                }
            };
        });

        sort($processed);
        $this->assertEquals(['usd-account.com', 'wrongly-cancelled.com'], $processed);
        $this->assertEquals(2, $summary['checked']);
    }

    #[Test]
    public function correctsDomainThatIsActiveAtAscio(): void
    {
        $this->seedDomain(10, 'miserve.info', 'ascio', 'Cancelled');

        $summary = $this->runResync(function () {
            return new class {
                public function searchDomain() {
                    Capsule::table('tbldomains')->where('id', 10)->update(['status' => 'Active']);
                    return (object) ['Status' => 'ACTIVE'];
                }
            };
        });

        $this->assertEquals(1, $summary['corrected']);
        $this->assertEquals(0, $summary['stillCancelled']);
        $this->assertEquals('Active', Capsule::table('tbldomains')->where('id', 10)->value('status'));
    }

    #[Test]
    public function leavesGenuinelyDeletedDomainCancelled(): void
    {
        $this->seedDomain(11, 'really-deleted.com', 'ascio', 'Cancelled');

        $summary = $this->runResync(function () {
            return new class {
                public function searchDomain() {
                    return (object) ['error' => 'Domain not found'];
                }
            };
        });

        $this->assertEquals(0, $summary['corrected']);
        $this->assertEquals(1, $summary['stillCancelled']);
        $this->assertEquals('Cancelled', Capsule::table('tbldomains')->where('id', 11)->value('status'));
    }

    #[Test]
    public function countsLookupErrorsSeparatelyWithoutChangingStatus(): void
    {
        $this->seedDomain(12, 'api-timeout.com', 'ascio', 'Cancelled');

        $summary = $this->runResync(function () {
            return new class {
                public function searchDomain() {
                    return ['error' => 'Temporary error. Please try later.'];
                }
            };
        });

        $this->assertEquals(1, $summary['errors']);
        $this->assertEquals(0, $summary['corrected']);
        $this->assertEquals(0, $summary['stillCancelled']);
        $this->assertEquals('Cancelled', Capsule::table('tbldomains')->where('id', 12)->value('status'));
    }

    #[Test]
    public function countsExceptionsAsErrorsWithoutStoppingTheRun(): void
    {
        $this->seedDomain(13, 'throws.com', 'ascio', 'Cancelled');
        $this->seedDomain(14, 'after-throw.com', 'ascio', 'Cancelled');

        $summary = $this->runResync(function (string $registrar, int $domainId) {
            if ($domainId === 13) {
                return new class {
                    public function searchDomain() {
                        throw new \RuntimeException('SOAP fault');
                    }
                };
            }
            return new class {
                public function searchDomain() {
                    return (object) ['error' => 'Domain not found'];
                }
            };
        });

        $this->assertEquals(2, $summary['checked']);
        $this->assertEquals(1, $summary['errors']);
        $this->assertEquals(1, $summary['stillCancelled']);
    }
}
