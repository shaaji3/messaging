<?php

namespace Utopia\Tests\Adapter;

use Utopia\Messaging\Adapter\Email;
use Utopia\Messaging\Adapter\FailoverEmail;
use Utopia\Messaging\Messages\Email as EmailMessage;

class FailoverEmailTest extends Base
{
    public function testPrimarySuccessDoesNotFallback(): void
    {
        $primary = new TestEmailAdapter('Primary', [[
            'deliveredTo' => 1,
            'type' => 'email',
            'results' => [['recipient' => 'test@localhost.test', 'status' => 'success', 'error' => '']],
        ]]);

        $secondary = new TestEmailAdapter('Secondary', [[
            'deliveredTo' => 1,
            'type' => 'email',
            'results' => [['recipient' => 'test@localhost.test', 'status' => 'success', 'error' => '']],
        ]]);

        $adapter = new FailoverEmail([$primary, $secondary]);

        $message = new EmailMessage(
            to: ['test@localhost.test'],
            subject: 'Subject',
            content: 'Body',
            fromName: 'Sender',
            fromEmail: 'sender@localhost.test'
        );

        $response = $adapter->send($message);

        $this->assertSame(1, $response['deliveredTo']);
        $this->assertCount(1, $response['results']);
        $this->assertSame('Primary', $response['results'][0]['adapter']);
    }

    public function testPrimaryFailureFallsBackToSecondary(): void
    {
        $primary = new TestEmailAdapter('Primary', [
            ['deliveredTo' => 0, 'type' => 'email', 'results' => [['recipient' => 'test@localhost.test', 'status' => 'failure', 'error' => 'blocked']]],
        ]);

        $secondary = new TestEmailAdapter('Secondary', [
            ['deliveredTo' => 1, 'type' => 'email', 'results' => [['recipient' => 'test@localhost.test', 'status' => 'success', 'error' => '']]],
        ]);

        $adapter = new FailoverEmail([$primary, $secondary]);
        $message = new EmailMessage(['test@localhost.test'], 'Subject', 'Body', 'Sender', 'sender@localhost.test');

        $response = $adapter->send($message);

        $this->assertSame(1, $response['deliveredTo']);
        $this->assertCount(2, $response['results']);
        $this->assertSame(2, $response['results'][1]['attempt']);
    }
}

class TestEmailAdapter extends Email
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
    protected function process(EmailMessage $message): array
    {
        $next = \array_shift($this->responses);

        if ($next instanceof \Throwable) {
            throw $next;
        }

        return $next ?? ['deliveredTo' => 0, 'type' => 'email', 'results' => []];
    }
}
