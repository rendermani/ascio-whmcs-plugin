<?php

namespace Ascio\Core\Tests;

use PHPUnit\Framework\TestCase;
use Ascio\Core\ResponseHandler;
use Ascio\Core\AscioApiException;

/**
 * Unit tests for ResponseHandler.
 */
class ResponseHandlerTest extends TestCase
{
    protected ResponseHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new ResponseHandler('test_module');
    }

    public function testHandleResponseReturnsResultOnSuccess(): void
    {
        $result = new class {
            public function getResultCode() { return 200; }
            public function getResultMessage() { return 'Success'; }
        };

        $response = (object)['TestOperationResult' => $result];

        $output = $this->handler->handleResponse($response, 'TestOperation');

        $this->assertSame($result, $output);
    }

    public function testHandleResponseThrowsOnError(): void
    {
        $result = new class {
            public function getResultCode() { return 400; }
            public function getResultMessage() { return 'Bad Request'; }
            public function getErrors() { return null; }
        };

        $response = (object)['TestOperationResult' => $result];

        $this->expectException(AscioApiException::class);
        $this->expectExceptionMessage('Bad Request');
        $this->expectExceptionCode(400);

        $this->handler->handleResponse($response, 'TestOperation');
    }

    public function testHandleResponseThrowsOnMissingResult(): void
    {
        $response = (object)['WrongResult' => null];

        $this->expectException(AscioApiException::class);
        $this->expectExceptionMessage('Invalid response');

        $this->handler->handleResponse($response, 'TestOperation');
    }

    public function testExtractErrorsReturnsEmptyArrayWhenNoErrors(): void
    {
        $result = new class {
            public function getErrors() { return null; }
        };

        $errors = $this->handler->extractErrors($result);

        $this->assertEmpty($errors);
    }

    public function testFormatErrorsFormatsFieldErrors(): void
    {
        $errors = [
            ['field' => 'email', 'message' => 'Invalid email format'],
            ['field' => 'name', 'message' => 'Name is required'],
        ];

        $formatted = $this->handler->formatErrors($errors);

        $this->assertStringContainsString('email: Invalid email format', $formatted);
        $this->assertStringContainsString('name: Name is required', $formatted);
    }

    public function testFormatErrorsHandlesMessageOnlyErrors(): void
    {
        $errors = [
            ['message' => 'General error'],
        ];

        $formatted = $this->handler->formatErrors($errors);

        $this->assertEquals('General error', $formatted);
    }

    public function testIsSuccessReturnsTrueFor200(): void
    {
        $result = new class {
            public function getResultCode() { return 200; }
        };

        $response = (object)['TestResult' => $result];

        $this->assertTrue($this->handler->isSuccess($response, 'Test'));
    }

    public function testIsSuccessReturnsFalseForNon200(): void
    {
        $result = new class {
            public function getResultCode() { return 400; }
        };

        $response = (object)['TestResult' => $result];

        $this->assertFalse($this->handler->isSuccess($response, 'Test'));
    }

    public function testToWhmcsResultBuildsCorrectArray(): void
    {
        $result = new class {
            public function getResultCode() { return 200; }
            public function getResultMessage() { return 'Success'; }
            public function getErrors() { return null; }
        };

        $whmcsResult = $this->handler->toWhmcsResult($result, 'TEST123');

        $this->assertEquals(200, $whmcsResult['code']);
        $this->assertEquals('Success', $whmcsResult['message']);
        $this->assertEquals('TEST123', $whmcsResult['order_id']);
        $this->assertNull($whmcsResult['errors']);
    }
}
