<?php

namespace Utopia\Messaging\Adapter;

use Utopia\Messaging\Messages\Email as EmailMessage;

class FailoverEmail extends Email
{
    use Failover;

    /**
     * @param  array<Email>  $adapters
     */
    public function __construct(array $adapters)
    {
        $this->setAdapters($adapters, 'FailoverEmail');
    }

    public function getName(): string
    {
        return 'FailoverEmail';
    }

    public function getMaxMessagesPerRequest(): int
    {
        return $this->getMaxMessagesPerRequestFromAdapters();
    }

    /**
     * @return array{deliveredTo: int, type: string, results: array<array<string, mixed>>}
     */
    protected function process(EmailMessage $message): array
    {
        return $this->processFailover($message);
    }
}
