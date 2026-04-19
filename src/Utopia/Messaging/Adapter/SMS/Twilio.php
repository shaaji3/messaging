<?php

namespace Utopia\Messaging\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS as SMSMessage;
use Utopia\Messaging\Response;

class Twilio extends SMSAdapter
{
    protected const NAME = 'Twilio';

    /**
     * @param  string  $accountSid Twilio Account SID
     * @param  string  $authToken Twilio Auth Token
     */
    public function __construct(
        private string $accountSid,
        private string $authToken,
        private ?string $from = null,
        private ?string $messagingServiceSid = null
    ) {
    }

    public function getName(): string
    {
        return static::NAME;
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    protected function process(SMSMessage $message): array
    {
        $response = new Response($this->getType());

        $result = $this->request(
            method: 'POST',
            url: "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json",
            headers: [
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization: Basic '.base64_encode("{$this->accountSid}:{$this->authToken}"),
            ],
            body: [
                'Body' => $message->getContent(),
                'From' => $this->from ?? $message->getFrom(),
                'MessagingServiceSid' => $this->messagingServiceSid ?? null,
                'To' => $message->getTo()[0],
            ],
        );

        if ($result['statusCode'] >= 200 && $result['statusCode'] < 300) {
            $response->setDeliveredTo(1);
            $response->addResult($message->getTo()[0]);
        } else {
            $providerCode = $result['response']['code'] ?? null;
            $errorMessage = $result['response']['message'] ?? 'Unknown error';
            if (!\is_null($result['response']['message'] ?? null)) {
                $errorMessage = $result['response']['message'];
            }

            $response->addFailureResult(
                recipient: $message->getTo()[0],
                error: $errorMessage,
                provider: $this->getName(),
                providerCode: \is_scalar($providerCode) ? $providerCode : null,
                retryable: $this->isRetryable(
                    statusCode: $result['statusCode'],
                    providerCode: \is_scalar($providerCode) ? (string)$providerCode : null
                ),
                rawStatusCode: $result['statusCode']
            );
        }

        return $response->toArray();
    }

    private function isRetryable(int $statusCode, ?string $providerCode): bool
    {
        if ($statusCode === 429 || $statusCode >= 500) {
            return true;
        }

        if (\in_array($providerCode, ['20003', '21211', '21614'], true)) {
            return false;
        }

        return false;
    }
}
