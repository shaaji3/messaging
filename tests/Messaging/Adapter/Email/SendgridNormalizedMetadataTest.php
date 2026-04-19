<?php

namespace Utopia\Tests\Adapter\Email;

use Utopia\Messaging\Adapter\Email\Sendgrid;
use Utopia\Messaging\Messages\Email;
use Utopia\Tests\Adapter\Base;

class SendgridNormalizedMetadataTest extends Base
{
    public function testFailureIncludesNormalizedMetadata(): void
    {
        $adapter = new class ('api-key') extends Sendgrid {
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
                    'statusCode' => 400,
                    'response' => [
                        'errors' => [
                            [
                                'message' => 'The to.email field must contain a valid address.',
                                'field' => 'personalizations.0.to.0.email',
                            ],
                        ],
                    ],
                    'error' => null,
                ];
            }
        };

        $result = $adapter->send(new Email(
            to: ['invalid-email'],
            subject: 'subject',
            content: 'body',
            fromName: 'Sender',
            fromEmail: 'sender@example.com'
        ));

        $entry = $result['results'][0];
        $this->assertSame('failure', $entry['status']);
        $this->assertSame('Sendgrid', $entry['provider']);
        $this->assertSame('personalizations.0.to.0.email', $entry['providerCode']);
        $this->assertFalse($entry['retryable']);
        $this->assertSame(400, $entry['rawStatusCode']);
    }
}
