<?php

namespace Utopia\Messaging\Adapter;

use Utopia\Messaging\Adapter;
use Utopia\Messaging\Message;

trait Failover
{
    /**
     * @var array<Adapter>
     */
    private array $adapters = [];

    /**
     * @param  array<Adapter>  $adapters
     */
    private function setAdapters(array $adapters, string $name): void
    {
        if (empty($adapters)) {
            throw new \InvalidArgumentException("{$name} requires at least one adapter.");
        }

        foreach ($adapters as $adapter) {
            if (!$adapter instanceof Adapter) {
                throw new \InvalidArgumentException('All failover adapters must extend '.Adapter::class.'.');
            }
        }

        $type = $adapters[0]->getType();
        $messageType = $adapters[0]->getMessageType();

        foreach ($adapters as $adapter) {
            if ($adapter->getType() !== $type || $adapter->getMessageType() !== $messageType) {
                throw new \InvalidArgumentException('All adapters must share the same type and message type.');
            }
        }

        $this->adapters = \array_values($adapters);
    }

    private function getMaxMessagesPerRequestFromAdapters(): int
    {
        return \min(\array_map(static fn (Adapter $adapter): int => $adapter->getMaxMessagesPerRequest(), $this->adapters));
    }

    /**
     * @return array{deliveredTo: int, type: string, results: array<array<string, mixed>>}
     */
    private function processFailover(Message $message): array
    {
        $results = [];

        foreach ($this->adapters as $index => $adapter) {
            $attempt = $index + 1;

            try {
                $response = $adapter->send($message);
                foreach ($response['results'] as $result) {
                    $result['adapter'] = $adapter->getName();
                    $result['attempt'] = $attempt;
                    $results[] = $result;
                }

                if (($response['deliveredTo'] ?? 0) > 0) {
                    return [
                        'deliveredTo' => $response['deliveredTo'],
                        'type' => $this->getType(),
                        'results' => $results,
                    ];
                }
            } catch (\Throwable $error) {
                $results[] = [
                    'status' => 'failure',
                    'error' => $error->getMessage(),
                    'failureType' => 'transport_exception',
                    'retryable' => false,
                    'adapter' => $adapter->getName(),
                    'attempt' => $attempt,
                ];
            }
        }

        return [
            'deliveredTo' => 0,
            'type' => $this->getType(),
            'results' => $results,
        ];
    }
}
