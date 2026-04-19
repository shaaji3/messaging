<?php

namespace Utopia\Tests\Routing;

use PHPUnit\Framework\TestCase;
use Utopia\Messaging\Adapter;
use Utopia\Messaging\Message;
use Utopia\Messaging\Messages\Push;
use Utopia\Messaging\Messages\SMS;
use Utopia\Messaging\Notifier;
use Utopia\Messaging\Priority;
use Utopia\Messaging\Routing\ConditionalRouteResolver;
use Utopia\Messaging\Routing\StaticRouteResolver;
use Utopia\Messaging\Routing\UnknownRouteException;

class RouteResolverTest extends TestCase
{
    public function testStaticResolverResolvesByMessageClass(): void
    {
        $smsAdapter = new TestAdapter('sms-route', SMS::class);
        $resolver = new StaticRouteResolver([
            SMS::class => $smsAdapter,
        ]);

        $resolved = $resolver->resolve(new SMS(['+12025550139'], 'Hello'));

        $this->assertSame($smsAdapter, $resolved);
    }

    public function testConditionalResolverResolvesByEnvironmentPriorityAndCountryCode(): void
    {
        $staging = new TestAdapter('staging', SMS::class);
        $india = new TestAdapter('india', SMS::class);
        $highPriorityPush = new TestAdapter('high-priority-push', Push::class);

        $resolver = (new ConditionalRouteResolver())
            ->addRule(SMS::class, $staging, ['environment' => 'staging'])
            ->addRule(SMS::class, $india, ['countryCode' => '91'])
            ->addRule(Push::class, $highPriorityPush, ['priority' => Priority::HIGH]);

        $this->assertSame(
            $staging,
            $resolver->resolve(new SMS(['+12025550139'], 'staging'), ['environment' => 'staging'])
        );

        $this->assertSame(
            $india,
            $resolver->resolve(new SMS(['+919876543210'], 'india'))
        );

        $this->assertSame(
            $highPriorityPush,
            $resolver->resolve(new Push(['token-1'], title: 'Hi', priority: Priority::HIGH))
        );

        $this->assertSame(
            $highPriorityPush,
            $resolver->resolve(new Push(['token-2'], title: 'Hi', priority: Priority::HIGH), ['priority' => 'HIGH'])
        );
    }

    public function testNotifierSendsWithResolvedAdapter(): void
    {
        $adapter = new TestAdapter('sms-route', SMS::class);
        $notifier = new Notifier(new StaticRouteResolver([
            SMS::class => $adapter,
        ]));

        $response = $notifier->send(new SMS(['+12025550139'], 'Hello world'));

        $this->assertSame('success', $response['type']);
        $this->assertCount(1, $adapter->sentMessages);
    }

    public function testUnknownRouteThrowsException(): void
    {
        $resolver = new StaticRouteResolver();

        $this->expectException(UnknownRouteException::class);

        $resolver->resolve(new SMS(['+12025550139'], 'No route'));
    }

    public function testConditionalResolverUsesFallbackResolver(): void
    {
        $fallback = new TestAdapter('fallback', SMS::class);
        $fallbackResolver = new StaticRouteResolver([
            SMS::class => $fallback,
        ]);

        $resolver = (new ConditionalRouteResolver($fallbackResolver))
            ->addRule(SMS::class, new TestAdapter('staging', SMS::class), ['environment' => 'staging']);

        $this->assertSame(
            $fallback,
            $resolver->resolve(new SMS(['+12025550139'], 'fallback-route'), ['environment' => 'production'])
        );
    }

    public function testConditionalResolverSupportsPredicateRule(): void
    {
        $predicateAdapter = new TestAdapter('predicate', SMS::class);
        $resolver = (new ConditionalRouteResolver())
            ->addRule(SMS::class, $predicateAdapter, [
                'predicate' => fn (SMS $message, array $context): bool => str_contains($message->getContent(), 'vip')
                    && ($context['environment'] ?? null) === 'production',
            ]);

        $this->assertSame(
            $predicateAdapter,
            $resolver->resolve(new SMS(['+12025550139'], 'vip-message'), ['environment' => 'production'])
        );
    }
}

class TestAdapter extends Adapter
{
    /**
     * @var array<int, Message>
     */
    public array $sentMessages = [];

    public function __construct(private string $name, private string $messageType)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return 'test';
    }

    public function getMessageType(): string
    {
        return $this->messageType;
    }

    public function getMaxMessagesPerRequest(): int
    {
        return PHP_INT_MAX;
    }

    protected function process(Message $message): array
    {
        $this->sentMessages[] = $message;

        return [
            'deliveredTo' => 1,
            'type' => 'success',
            'results' => [
                [
                    'id' => 'test',
                    'status' => 'success',
                    'error' => '',
                ],
            ],
        ];
    }
}
