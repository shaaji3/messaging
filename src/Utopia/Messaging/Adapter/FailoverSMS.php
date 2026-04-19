<?php

namespace Utopia\Messaging\Adapter;

use Utopia\Messaging\Messages\SMS as SMSMessage;

class FailoverSMS extends SMS
{
    use Failover;

    /**
     * @param  array<SMS>  $adapters
     */
    public function __construct(array $adapters)
    {
        $this->setAdapters($adapters, 'FailoverSMS');
    }

    public function getName(): string
    {
        return 'FailoverSMS';
    }

    public function getMaxMessagesPerRequest(): int
    {
        return $this->getMaxMessagesPerRequestFromAdapters();
    }

    /**
     * @return array{deliveredTo: int, type: string, results: array<array<string, mixed>>}
     */
    protected function process(SMSMessage $message): array
    {
        return $this->processFailover($message);
    }
}
