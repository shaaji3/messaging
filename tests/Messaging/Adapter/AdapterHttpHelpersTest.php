<?php

namespace Utopia\Tests\Adapter;

use Utopia\Messaging\Adapter;

class AdapterHttpHelpersTest extends Base
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

            /**
             * @param array<string> $headers
             * @return array<string>
             */
            public function callBuildRequestHeaders(array $headers, mixed $body): array
            {
                return $this->buildRequestHeaders($headers, $body);
            }

            /**
             * @return array{
             *     url: string,
             *     statusCode: int,
             *     response: array<string, mixed>|string|null,
             *     error: string|null
             * }
             */
            public function callNormalizeHttpResult(string $url, int $statusCode, mixed $response, string $curlError): array
            {
                return $this->normalizeHttpResult($url, $statusCode, $response, $curlError);
            }
        };
    }

    public function testNormalizeHttpResultPreservesHttpStatus(): void
    {
        $result = $this->adapter->callNormalizeHttpResult(
            'https://example.test/messages',
            429,
            '{"error":"Too Many Requests"}',
            ''
        );

        $this->assertSame(429, $result['statusCode']);
        $this->assertSame('Too Many Requests', $result['response']['error']);
        $this->assertNull($result['error']);
    }

    public function testNormalizeHttpResultPreservesTransportErrors(): void
    {
        $result = $this->adapter->callNormalizeHttpResult(
            'https://example.test/messages',
            0,
            false,
            'Could not resolve host: example.test'
        );

        $this->assertSame(0, $result['statusCode']);
        $this->assertNull($result['response']);
        $this->assertSame('Transport error: Could not resolve host: example.test', $result['error']);
    }

    public function testBuildRequestHeadersDoesNotMutateInputAcrossIterations(): void
    {
        $headers = ['Content-Type: application/json'];

        $first = $this->adapter->callBuildRequestHeaders($headers, '{"a":1}');
        $second = $this->adapter->callBuildRequestHeaders($headers, '{"a":1}');

        $this->assertCount(2, $first);
        $this->assertCount(2, $second);
        $this->assertSame('Content-Length: 7', $first[1]);
        $this->assertSame('Content-Length: 7', $second[1]);
        $this->assertCount(1, $headers);
    }
}
