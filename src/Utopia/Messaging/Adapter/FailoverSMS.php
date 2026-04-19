<?php

namespace Utopia\Messaging\Adapter;

use Utopia\Messaging\Messages\SMS as SMSMessage;

class FailoverSMS extends SMS
{
    /**
     * @var array<SMS>
     */
    private array $adapters;

    /**
     * @param  array<SMS>  $adapters
     */
    public function __construct(array $adapters)
    {
        if (empty($adapters)) {
            throw new \InvalidArgumentException('FailoverSMS requires at least one adapter.');
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

    public function getName(): string
    {
        return 'FailoverSMS';
    }

    public function getMaxMessagesPerRequest(): int
    {
        return $this->adapters[0]->getMaxMessagesPerRequest();
    }

    /**
     * @return array{deliveredTo: int, type: string, results: array<array<string, mixed>>}
     */
    protected function process(SMSMessage $message): array
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
