<?php

namespace Utopia\Tests;

use PHPUnit\Framework\TestCase;
use Utopia\Messaging\Response;

class ResponseTest extends TestCase
{
    public function testAddResultPreservesLegacyShape(): void
    {
        $response = new Response('sms');
        $response->addResult('+123456789');

        $result = $response->toArray()['results'][0];

        $this->assertSame('+123456789', $result['recipient']);
        $this->assertSame('success', $result['status']);
        $this->assertSame('', $result['error']);
        $this->assertArrayNotHasKey('provider', $result);
        $this->assertArrayNotHasKey('providerCode', $result);
        $this->assertArrayNotHasKey('retryable', $result);
        $this->assertArrayNotHasKey('rawStatusCode', $result);
    }
}
