<?php

namespace Utopia\Tests\Adapter\Push;

use Utopia\Messaging\Adapter\Push\FCM;
use Utopia\Messaging\Messages\Push;
use Utopia\Tests\Adapter\Base;

class FCMNormalizedMetadataTest extends Base
{
    public function testFailureIncludesNormalizedMetadata(): void
    {
        $serviceAccount = \json_encode([
            'private_key' => "-----BEGIN PRIVATE KEY-----\nMIIBVAIBADANBgkqhkiG9w0BAQEFAASCAT4wggE6AgEAAkEAt8Gw9eRcQo25R1aw\nSdZCR4QvxPw0UgbJwvFXH+lTL63qbB7mFiEb4VFsgqdlrWk3oPRlgcmGpXqnDTPd\nQcF8GwIDAQABAkBFZIrcQPGqXHTkgNMv/w6mjT+w5wEnHNMioBsjvOwiK6pzVNcx\njLW6vU67j3jjY5u7I7bSubmOzs5qlWQeSwKxAiEA2jMpDuHKN3VhIKZ39PNmlsYv\nEwK7HcVceLYnu9yhpnkCIQDXlwY4Sa4Gp7ckQ+QCIa561/E7d2LFbT7iziC3wati\nMwIgJOL3MuvaqotuUv2xU7h+BEkWliklBsBhhIqOpwSCU0kCIQCP+OVIdbvn6dze\nNRBxc/jHRKpuof2uBpS2dh1XWdiQDQIgDooD5D8TqzhV/EiC1B1X8woWWht2E/je\nr5h9S6rEYPU=\n-----END PRIVATE KEY-----\n",
            'client_email' => 'test@example.com',
            'project_id' => 'demo-project',
        ]);

        $adapter = new class ($serviceAccount) extends FCM {
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
                    'statusCode' => 200,
                    'response' => ['access_token' => 'fake-token'],
                    'error' => null,
                ];
            }

            protected function requestMulti(
                string $method,
                array $urls,
                array $headers = [],
                array $bodies = [],
                int $timeout = 30,
                int $connectTimeout = 10
            ): array {
                return [
                    [
                        'index' => 0,
                        'url' => $urls[0],
                        'statusCode' => 401,
                        'response' => ['error' => ['status' => 'UNAUTHENTICATED', 'message' => 'Auth failed']],
                        'error' => null,
                    ],
                ];
            }
        };

        $result = $adapter->send(new Push(
            to: ['device-token'],
            title: 'Hello'
        ));

        $entry = $result['results'][0];
        $this->assertSame('failure', $entry['status']);
        $this->assertSame('FCM', $entry['provider']);
        $this->assertSame('UNAUTHENTICATED', $entry['providerCode']);
        $this->assertFalse($entry['retryable']);
        $this->assertSame(401, $entry['rawStatusCode']);
        $this->assertSame('Auth failed', $entry['error']);
    }
}
