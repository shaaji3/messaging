<?php

namespace Utopia\Messaging\Routing;

use Utopia\Messaging\Adapter;
use Utopia\Messaging\Adapter\SMS\GEOSMS\CallingCode;
use Utopia\Messaging\Message;
use Utopia\Messaging\Priority;

class ConditionalRouteResolver implements RouteResolverInterface
{
    /**
     * @var array<int, array{messageClass: string, adapter: Adapter, when: array<string, mixed>}>
     */
    private array $rules = [];

    public function __construct(private ?RouteResolverInterface $fallbackResolver = null)
    {
    }

    /**
     * @param array<string, mixed> $when
     */
    public function addRule(string $messageClass, Adapter $adapter, array $when = []): self
    {
        $this->rules[] = [
            'messageClass' => $messageClass,
            'adapter' => $adapter,
            'when' => $when,
        ];

        return $this;
    }

    public function resolve(Message $message, array $context = []): Adapter
    {
        foreach ($this->rules as $rule) {
            if (!\is_a($message, $rule['messageClass'])) {
                continue;
            }

            if ($this->matches($message, $context, $rule['when'])) {
                return $rule['adapter'];
            }
        }

        if (!\is_null($this->fallbackResolver)) {
            return $this->fallbackResolver->resolve($message, $context);
        }

        throw new UnknownRouteException('No conditional route matched for '.$message::class.'.');
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $conditions
     */
    private function matches(Message $message, array $context, array $conditions): bool
    {
        if (isset($conditions['environment']) && !$this->matchesValue($context['environment'] ?? null, $conditions['environment'])) {
            return false;
        }

        if (isset($conditions['priority']) && !$this->matchesValue($this->resolvePriority($message, $context), $conditions['priority'])) {
            return false;
        }

        if (isset($conditions['countryCode']) && !$this->matchesValue($this->resolveCountryCode($message, $context), $conditions['countryCode'])) {
            return false;
        }

        return true;
    }

    /**
     * @param mixed $actual
     * @param mixed $expected
     */
    private function matchesValue(mixed $actual, mixed $expected): bool
    {
        $actual = $this->normalizeComparableValue($actual);

        if (\is_array($expected)) {
            foreach ($expected as $value) {
                if ($actual === $this->normalizeComparableValue($value)) {
                    return true;
                }
            }

            return false;
        }

        return $actual === $this->normalizeComparableValue($expected);
    }

    private function normalizeComparableValue(mixed $value): mixed
    {
        if ($value instanceof Priority) {
            return $value->name;
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function resolvePriority(Message $message, array $context): mixed
    {
        if (isset($context['priority'])) {
            return $context['priority'];
        }

        if (\method_exists($message, 'getPriority')) {
            return $message->getPriority();
        }

        return null;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function resolveCountryCode(Message $message, array $context): mixed
    {
        if (isset($context['countryCode'])) {
            return $context['countryCode'];
        }

        if (!\method_exists($message, 'getTo')) {
            return null;
        }

        $recipients = $message->getTo();

        if (!isset($recipients[0]) || !\is_string($recipients[0])) {
            return null;
        }

        $phone = $recipients[0];

        try {
            return (string) (new class extends Adapter {
                public function getName(): string
                {
                    return 'RoutingCountryCodeHelper';
                }

                public function getType(): string
                {
                    return 'helper';
                }

                public function getMessageType(): string
                {
                    return Message::class;
                }

                public function getMaxMessagesPerRequest(): int
                {
                    return PHP_INT_MAX;
                }
            })->getCountryCode($phone);
        } catch (\Throwable) {
            return CallingCode::fromPhoneNumber($phone);
        }
    }
}
