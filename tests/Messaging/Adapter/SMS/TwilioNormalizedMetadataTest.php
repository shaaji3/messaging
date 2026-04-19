<?php

namespace Utopia\Tests\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS\Twilio;
use Utopia\Messaging\Messages\SMS;
use Utopia\Tests\Adapter\Base;

class TwilioNormalizedMetadataTest extends Base
{
    public function testFailureIncludesNormalizedMetadata(): void
    {
        $adapter = new class ('sid', 'token') extends Twilio {
            protected function request(
                string $method,
                string $url,
                array $headers = [],
                ?array $body = null,
                int $timeout = 30,
                int $connectTimeout = 10
            ): array {
                return [
                    'url' => $url,
                    'statusCode' => 429,
                    'response' => [
                        'message' => 'Rate limit reached',
                        'code' => 20429,
                    ],
                    'error' => null,
                ];
            }
        };

        $result = $adapter->send(new SMS(
            to: ['+15550000000'],
            content: 'hello',
            from: '+15551111111'
        ));

        $entry = $result['results'][0];
        $this->assertSame('failure', $entry['status']);
        $this->assertSame('Twilio', $entry['provider']);
        $this->assertSame(20429, $entry['providerCode']);
        $this->assertTrue($entry['retryable']);
        $this->assertSame(429, $entry['rawStatusCode']);
        $this->assertSame('Rate limit reached', $entry['error']);
    }
}
