<?php

namespace Utopia\Tests\Adapter;

use Utopia\Messaging\Adapter\FailoverPush;
use Utopia\Messaging\Adapter\Push;
use Utopia\Messaging\Messages\Push as PushMessage;

class FailoverPushTest extends Base
{
    public function testAllFailReturnsAttemptTrace(): void
    {
        $primary = new TestPushAdapter('Primary', [new \RuntimeException('Network down')]);
        $secondary = new TestPushAdapter('Secondary', [
            ['deliveredTo' => 0, 'type' => 'push', 'results' => [['recipient' => 'token-1', 'status' => 'failure', 'error' => 'invalid token']]],
        ]);

        $adapter = new FailoverPush([$primary, $secondary]);
        $response = $adapter->send(new PushMessage(to: ['token-1'], title: 'Hello'));

        $this->assertSame(0, $response['deliveredTo']);
        $this->assertCount(2, $response['results']);
        $this->assertSame('transport_exception', $response['results'][0]['failureType']);
        $this->assertFalse($response['results'][0]['retryable']);
    }
}

class TestPushAdapter extends Push
{
    /**
     * @param  array<int, array<string, mixed>|\Throwable>  $responses
     */
    public function __construct(private string $name, private array $responses)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 100;
    }

    /**
     * @return array{deliveredTo: int, type: string, results: array<array<string, mixed>>}
     */
    protected function process(PushMessage $message): array
    {
        $next = \array_shift($this->responses);

        if ($next instanceof \Throwable) {
            throw $next;
        }

        return $next ?? ['deliveredTo' => 0, 'type' => 'push', 'results' => []];
    }
}
