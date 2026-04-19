<?php

namespace Utopia\Messaging;

class Response
{
    private int $deliveredTo;

    private string $type;

    /**
     * @var array<array<string, mixed>>
     */
    private array $results;

    public function __construct(string $type)
    {
        $this->type = $type;
        $this->deliveredTo = 0;
        $this->results = [];
    }

    public function setDeliveredTo(int $deliveredTo): void
    {
        $this->deliveredTo = $deliveredTo;
    }

    public function incrementDeliveredTo(): void
    {
        $this->deliveredTo++;
    }

    public function getDeliveredTo(): int
    {
        return $this->deliveredTo;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getDetails(): array
    {
        return $this->results;
    }

    public function addResult(string $recipient, string $error = ''): void
    {
        if (empty($error)) {
            $this->addSuccessResult($recipient);
            return;
        }

        $this->addFailureResult($recipient, $error);
    }

    public function addSuccessResult(
        string $recipient,
        ?string $provider = null,
        ?int $rawStatusCode = null
    ): void {
        $this->results[] = $this->createResult(
            recipient: $recipient,
            status: 'success',
            error: '',
            provider: $provider,
            rawStatusCode: $rawStatusCode
        );
    }

    public function addFailureResult(
        string $recipient,
        string $error,
        ?string $provider = null,
        string|int|null $providerCode = null,
        ?bool $retryable = null,
        ?int $rawStatusCode = null
    ): void {
        $this->results[] = $this->createResult(
            recipient: $recipient,
            status: 'failure',
            error: $error,
            provider: $provider,
            providerCode: $providerCode,
            retryable: $retryable,
            rawStatusCode: $rawStatusCode
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function createResult(
        string $recipient,
        string $status,
        string $error,
        ?string $provider = null,
        string|int|null $providerCode = null,
        ?bool $retryable = null,
        ?int $rawStatusCode = null
    ): array
    {
        $result = [
            'recipient' => $recipient,
            'status' => $status,
            'error' => $error,
        ];

        if (!\is_null($provider)) {
            $result['provider'] = $provider;
        }
        if (!\is_null($providerCode)) {
            $result['providerCode'] = $providerCode;
        }
        if (!\is_null($retryable)) {
            $result['retryable'] = $retryable;
        }
        if (!\is_null($rawStatusCode)) {
            $result['rawStatusCode'] = $rawStatusCode;
        }

        return $result;
    }

    /**
     * @return array{deliveredTo: int, type: string, results: array<array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'deliveredTo' => $this->deliveredTo,
            'type' => $this->type,
            'results' => $this->results,
        ];
    }
}
