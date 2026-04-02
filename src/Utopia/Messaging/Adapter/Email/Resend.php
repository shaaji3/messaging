<?php

namespace Utopia\Messaging\Adapter\Email;

use Utopia\Messaging\Adapter\Email as EmailAdapter;
use Utopia\Messaging\Messages\Email as EmailMessage;
use Utopia\Messaging\Response;

class Resend extends EmailAdapter
{
    protected const NAME = 'Resend';

    /**
     * @param  string  $apiKey  Your Resend API key to authenticate with the API.
     */
    public function __construct(
        private string $apiKey
    ) {
    }

    public function getName(): string
    {
        return static::NAME;
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 100;
    }

    /**
     * Uses Resend's batch sending API to send multiple emails at once.
     *
     * @link https://resend.com/docs/api-reference/emails/send-batch-emails
     */
    protected function process(EmailMessage $message): array
    {
        $attachments = [];
        if (! \is_null($message->getAttachments()) && ! empty($message->getAttachments())) {
            $size = 0;
            foreach ($message->getAttachments() as $attachment) {
                if ($attachment->getContent() !== null) {
                    $size += \strlen($attachment->getContent());
                } else {
                    $fileSize = \filesize($attachment->getPath());
                    if ($fileSize === false) {
                        throw new \Exception('Failed to read attachment file: '.$attachment->getPath());
                    }
                    $size += $fileSize;
                }
            }

            if ($size > self::MAX_ATTACHMENT_BYTES) {
                throw new \Exception('Total attachment size exceeds '.self::MAX_ATTACHMENT_BYTES.' bytes');
            }

            foreach ($message->getAttachments() as $attachment) {
                if ($attachment->getContent() !== null) {
                    $content = \base64_encode($attachment->getContent());
                } else {
                    $data = \file_get_contents($attachment->getPath());
                    if ($data === false) {
                        throw new \Exception('Failed to read attachment file: '.$attachment->getPath());
                    }
                    $content = \base64_encode($data);
                }

                $attachments[] = [
                    'filename' => $attachment->getName(),
                    'content' => $content,
                    'content_type' => $attachment->getType(),
                ];
            }
        }

        $response = new Response($this->getType());

        $emails = [];
        foreach ($message->getTo() as $to) {
            $toFormatted = !empty($to['name'])
                ? "{$to['name']} <{$to['email']}>"
                : $to['email'];

            $email = [
                'from' => $message->getFromName()
                    ? "{$message->getFromName()} <{$message->getFromEmail()}>"
                    : $message->getFromEmail(),
                'to' => [$toFormatted],
                'subject' => $message->getSubject(),
            ];

            if ($message->isHtml()) {
                $email['html'] = $message->getContent();
            } else {
                $email['text'] = $message->getContent();
            }

            if (! empty($message->getReplyToEmail())) {
                $email['reply_to'] = $message->getReplyToName()
                    ? ["{$message->getReplyToName()} <{$message->getReplyToEmail()}>"]
                    : [$message->getReplyToEmail()];
            }

            if (! \is_null($message->getCC()) && ! empty($message->getCC())) {
                $ccList = \array_map(
                    fn ($cc) => ! empty($cc['name'])
                        ? "{$cc['name']} <{$cc['email']}>"
                        : $cc['email'],
                    $message->getCC()
                );
                $email['cc'] = $ccList;
            }

            if (! empty($attachments)) {
                $email['attachments'] = $attachments;
            }

            if (! \is_null($message->getBCC()) && ! empty($message->getBCC())) {
                $bccList = \array_map(
                    fn ($bcc) => ! empty($bcc['name'])
                        ? "{$bcc['name']} <{$bcc['email']}>"
                        : $bcc['email'],
                    $message->getBCC()
                );
                $email['bcc'] = $bccList;
            }

            $emails[] = $email;
        }

        $headers = [
            'Authorization: Bearer '.$this->apiKey,
            'Content-Type: application/json',
        ];

        $result = $this->request(
            method: 'POST',
            url: 'https://api.resend.com/emails/batch',
            headers: $headers,
            body: $emails, // @phpstan-ignore-line
        );

        $statusCode = $result['statusCode'];

        if ($statusCode === 200) {
            $responseData = $result['response'];

            if (isset($responseData['errors']) && ! empty($responseData['errors'])) {
                $failedIndices = [];
                foreach ($responseData['errors'] as $error) {
                    $failedIndices[$error['index']] = $error['message'];
                }

                foreach ($message->getTo() as $index => $to) {
                    if (isset($failedIndices[$index])) {
                        $response->addResult($to['email'], $failedIndices[$index]);
                    } else {
                        $response->addResult($to['email']);
                    }
                }

                $successCount = \count($message->getTo()) - \count($failedIndices);
                $response->setDeliveredTo($successCount);
            } else {
                $response->setDeliveredTo(\count($message->getTo()));
                foreach ($message->getTo() as $to) {
                    $response->addResult($to['email']);
                }
            }
        } elseif ($statusCode >= 400 && $statusCode < 500) {
            $errorMessage = 'Unknown error';

            if (\is_string($result['response'])) {
                $errorMessage = $result['response'];
            } elseif (isset($result['response']['message'])) {
                $errorMessage = $result['response']['message'];
            } elseif (isset($result['response']['error'])) {
                $errorMessage = $result['response']['error'];
            }

            foreach ($message->getTo() as $to) {
                $response->addResult($to['email'], $errorMessage);
            }
        } elseif ($statusCode >= 500) {
            $errorMessage = 'Server error';

            if (\is_string($result['response'])) {
                $errorMessage = $result['response'];
            } elseif (isset($result['response']['message'])) {
                $errorMessage = $result['response']['message'];
            }

            foreach ($message->getTo() as $to) {
                $response->addResult($to['email'], $errorMessage);
            }
        }

        return $response->toArray();
    }
}
