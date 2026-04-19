<?php

namespace Utopia\Tests\Adapter;

use Utopia\Messaging\Adapter\FailoverSMS;
use Utopia\Messaging\Adapter\SMS;
use Utopia\Messaging\Messages\Email as EmailMessage;
use Utopia\Messaging\Messages\SMS as SMSMessage;

class FailoverSMSTest extends Base
{
    public function testPrimarySuccessDoesNotFallback(): void
    {
        $primary = new TestSMSAdapter('Primary', [
            [
                'deliveredTo' => 1,
                'type' => 'sms',
                'results' => [['recipient' => '+12025550139', 'status' => 'success', 'error' => '']],
            ],
        ]);

        $secondary = new TestSMSAdapter('Secondary', [
            [
                'deliveredTo' => 1,
                'type' => 'sms',
                'results' => [['recipient' => '+12025550139', 'status' => 'success', 'error' => '']],
            ],
        ]);

        $adapter = new FailoverSMS([$primary, $secondary]);

        $response = $adapter->send(new SMSMessage(to: ['+12025550139'], content: 'Hello'));

        $this->assertSame(1, $response['deliveredTo']);
        $this->assertCount(1, $response['results']);
        $this->assertSame('Primary', $response['results'][0]['adapter']);
        $this->assertSame(1, $response['results'][0]['attempt']);
    }

    public function testPrimaryFailureFallsBackToSecondary(): void
    {
        $primary = new TestSMSAdapter('Primary', [
            [
                'deliveredTo' => 0,
                'type' => 'sms',
                'results' => [['recipient' => '+12025550139', 'status' => 'failure', 'error' => 'Provider unavailable']],
            ],
        ]);

        $secondary = new TestSMSAdapter('Secondary', [
            [
                'deliveredTo' => 1,
                'type' => 'sms',
                'results' => [['recipient' => '+12025550139', 'status' => 'success', 'error' => '']],
            ],
        ]);

        $adapter = new FailoverSMS([$primary, $secondary]);

        $response = $adapter->send(new SMSMessage(to: ['+12025550139'], content: 'Hello'));

        $this->assertSame(1, $response['deliveredTo']);
        $this->assertCount(2, $response['results']);
        $this->assertSame('Primary', $response['results'][0]['adapter']);
        $this->assertSame(1, $response['results'][0]['attempt']);
        $this->assertSame('Secondary', $response['results'][1]['adapter']);
        $this->assertSame(2, $response['results'][1]['attempt']);
    }

    public function testAllFailReturnsFailureWithAttemptTrace(): void
    {
        $primary = new TestSMSAdapter('Primary', [new \RuntimeException('Primary exception')]);
        $secondary = new TestSMSAdapter('Secondary', [
            [
                'deliveredTo' => 0,
                'type' => 'sms',
                'results' => [['recipient' => '+12025550139', 'status' => 'failure', 'error' => 'No route']],
            ],
        ]);

        $adapter = new FailoverSMS([$primary, $secondary]);

        $response = $adapter->send(new SMSMessage(to: ['+12025550139'], content: 'Hello'));

        $this->assertSame(0, $response['deliveredTo']);
        $this->assertCount(2, $response['results']);
        $this->assertSame('Primary', $response['results'][0]['adapter']);
        $this->assertSame(1, $response['results'][0]['attempt']);
        $this->assertSame('Primary exception', $response['results'][0]['error']);
        $this->assertSame('transport_exception', $response['results'][0]['failureType']);
        $this->assertFalse($response['results'][0]['retryable']);
        $this->assertSame('Secondary', $response['results'][1]['adapter']);
        $this->assertSame(2, $response['results'][1]['attempt']);
    }

    public function testPartialSuccessShortCircuitsFailover(): void
    {
        $primary = new TestSMSAdapter('Primary', [
            [
                'deliveredTo' => 1,
                'type' => 'sms',
                'results' => [
                    ['recipient' => '+12025550139', 'status' => 'success', 'error' => ''],
                    ['recipient' => '+12025550140', 'status' => 'failure', 'error' => 'Rejected'],
                ],
            ],
        ]);

        $secondary = new TestSMSAdapter('Secondary', [
            [
                'deliveredTo' => 2,
                'type' => 'sms',
                'results' => [
                    ['recipient' => '+12025550139', 'status' => 'success', 'error' => ''],
                    ['recipient' => '+12025550140', 'status' => 'success', 'error' => ''],
                ],
            ],
        ]);

        $adapter = new FailoverSMS([$primary, $secondary]);
        $response = $adapter->send(new SMSMessage(to: ['+12025550139', '+12025550140'], content: 'Hello'));

        $this->assertSame(1, $response['deliveredTo']);
        $this->assertCount(2, $response['results']);
        $this->assertSame('Primary', $response['results'][0]['adapter']);
        $this->assertSame('Primary', $response['results'][1]['adapter']);
    }

    public function testConstructorValidatesInnerAdapterTypeAndMessageType(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new FailoverSMS([
            new TestSMSAdapter('Primary', [[
                'deliveredTo' => 1,
                'type' => 'sms',
                'results' => [],
            ]]),
            new TestSMSAdapter('Mismatch', [[
                'deliveredTo' => 1,
                'type' => 'sms',
                'results' => [],
            ]], 'sms', EmailMessage::class),
        ]);
    }

    public function testFailoverUsesLowestMaxMessagesPerRequest(): void
    {
        $primary = new TestSMSAdapter('Primary', [], 'sms', SMSMessage::class, 50);
        $secondary = new TestSMSAdapter('Secondary', [], 'sms', SMSMessage::class, 10);

        $adapter = new FailoverSMS([$primary, $secondary]);

        $this->assertSame(10, $adapter->getMaxMessagesPerRequest());
    }
}

class TestSMSAdapter extends SMS
{
    /**
     * @param  array<int, array<string, mixed>|\Throwable>  $responses
     */
    public function __construct(
        private string $name,
        private array $responses,
        private string $type = 'sms',
        private string $messageType = SMSMessage::class,
        private int $maxMessages = 100,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getMessageType(): string
    {
        return $this->messageType;
    }

    public function getMaxMessagesPerRequest(): int
    {
        return $this->maxMessages;
    }

    /**
     * @return array{deliveredTo: int, type: string, results: array<array<string, mixed>>}
     */
    protected function process(SMSMessage $message): array
    {
        $next = \array_shift($this->responses);

        if ($next instanceof \Throwable) {
            throw $next;
        }

        return $next ?? [
            'deliveredTo' => 0,
            'type' => $this->getType(),
            'results' => [],
        ];
    }
}
