<?php

namespace Utopia\Tests\Adapter;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Utopia\Messaging\Adapter;

class AdapterHttpHelpersTest extends TestCase
{
    private Adapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adapter = new class extends Adapter {
            public function getName(): string
            {
                return 'Mock Adapter';
            }

            public function getType(): string
            {
                return 'mock';
            }

            public function getMessageType(): string
            {
                return '';
            }

            public function getMaxMessagesPerRequest(): int
            {
                return 1;
            }
        };
    }

    public function testNormalizeHttpResultPreservesHttpStatus(): void
    {
        $result = $this->invokeAdapterMethod('normalizeHttpResult', [
            'https://example.test/messages',
            429,
            '{"error":"Too Many Requests"}',
            '',
        ]);

        $this->assertSame(429, $result['statusCode']);
        $this->assertSame('Too Many Requests', $result['response']['error']);
        $this->assertNull($result['error']);
    }

    public function testNormalizeHttpResultPreservesTransportErrors(): void
    {
        $result = $this->invokeAdapterMethod('normalizeHttpResult', [
            'https://example.test/messages',
            0,
            false,
            'Could not resolve host: example.test',
        ]);

        $this->assertSame(0, $result['statusCode']);
        $this->assertNull($result['response']);
        $this->assertSame('Transport error: Could not resolve host: example.test', $result['error']);
    }

    public function testBuildRequestHeadersDoesNotMutateInputAcrossIterations(): void
    {
        $headers = ['Content-Type: application/json'];

        $first = $this->invokeAdapterMethod('buildRequestHeaders', [$headers, '{"a":1}']);
        $second = $this->invokeAdapterMethod('buildRequestHeaders', [$headers, '{"a":1}']);

        $this->assertCount(2, $first);
        $this->assertCount(2, $second);
        $this->assertSame('Content-Length: 7', $first[1]);
        $this->assertSame('Content-Length: 7', $second[1]);
        $this->assertCount(1, $headers);
    }

    /**
     * @param array<mixed> $arguments
     * @return mixed
     */
    private function invokeAdapterMethod(string $method, array $arguments): mixed
    {
        $reflection = new ReflectionMethod($this->adapter, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($this->adapter, $arguments);
    }
}
