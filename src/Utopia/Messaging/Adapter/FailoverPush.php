<?php

namespace Utopia\Messaging\Adapter;

use Utopia\Messaging\Messages\Push as PushMessage;

class FailoverPush extends Push
{
    use Failover;

    /**
     * @param  array<Push>  $adapters
     */
    public function __construct(array $adapters)
    {
        $this->setAdapters($adapters, 'FailoverPush');
    }

    public function getName(): string
    {
        return 'FailoverPush';
    }

    public function getMaxMessagesPerRequest(): int
    {
        return $this->getMaxMessagesPerRequestFromAdapters();
    }

    /**
     * @return array{deliveredTo: int, type: string, results: array<array<string, mixed>>}
     */
    protected function process(PushMessage $message): array
    {
        return $this->processFailover($message);
    }
}
