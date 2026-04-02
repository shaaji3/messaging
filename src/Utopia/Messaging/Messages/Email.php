<?php

namespace Utopia\Messaging\Messages;

use Utopia\Messaging\Message;
use Utopia\Messaging\Messages\Email\Attachment;
use Utopia\Messaging\Messages\Email\Recipient;

class Email implements Message
{
    /**
     * @var array<Recipient>
     */
    private array $to;

    /**
     * @var array<Recipient>|null
     */
    private ?array $cc;

    /**
     * @var array<Recipient>|null
     */
    private ?array $bcc;

    /**
     * @param  array<string|Recipient|array<string,string>>  $to The recipients of the email. Each entry can be an email string, a Recipient object, or an associative array with 'email' and optional 'name' keys.
     * @param  string  $subject The subject of the email.
     * @param  string  $content The content of the email.
     * @param  string  $fromName The name of the sender.
     * @param  string  $fromEmail The email address of the sender.
     * @param  string|null  $replyToName The name of the reply to.
     * @param  string|null  $replyToEmail The email address of the reply to.
     * @param  array<string|Recipient|array<string,string>>|null  $cc The CC recipients of the email. Same format as $to.
     * @param  array<string|Recipient|array<string,string>>|null  $bcc The BCC recipients of the email. Same format as $to.
     * @param  array<Attachment>|null  $attachments The attachments of the email.
     * @param  bool  $html Whether the message is HTML or not.
     */
    public function __construct(
        array $to,
        private string $subject,
        private string $content,
        private string $fromName,
        private string $fromEmail,
        private ?string $replyToName = null,
        private ?string $replyToEmail = null,
        ?array $cc = null,
        ?array $bcc = null,
        private ?array $attachments = null,
        private bool $html = false,
    ) {
        $this->to = \array_map([self::class, 'toRecipient'], $to);
        $this->cc = !\is_null($cc) ? \array_map([self::class, 'toRecipient'], $cc) : null;
        $this->bcc = !\is_null($bcc) ? \array_map([self::class, 'toRecipient'], $bcc) : null;

        if (\is_null($this->replyToName)) {
            $this->replyToName = $this->fromName;
        }

        if (\is_null($this->replyToEmail)) {
            $this->replyToEmail = $this->fromEmail;
        }
    }

    /**
     * @param  string|Recipient|array<string,string>  $value
     */
    private static function toRecipient(string|Recipient|array $value): Recipient
    {
        if ($value instanceof Recipient) {
            return $value;
        }

        if (\is_string($value)) {
            return new Recipient($value);
        }

        if (!\is_array($value) || !isset($value['email'])) {
            throw new \InvalidArgumentException('Each recipient must be a string, a Recipient object, or an array with at least an "email" key.');
        }

        return new Recipient($value['email'], $value['name'] ?? '');
    }

    /**
     * @return array<Recipient>
     */
    public function getTo(): array
    {
        return $this->to;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getFromName(): string
    {
        return $this->fromName;
    }

    public function getFromEmail(): string
    {
        return $this->fromEmail;
    }

    public function getReplyToName(): string
    {
        return $this->replyToName;
    }

    public function getReplyToEmail(): string
    {
        return $this->replyToEmail;
    }

    /**
     * @return array<Recipient>|null
     */
    public function getCC(): ?array
    {
        return $this->cc;
    }

    /**
     * @return array<Recipient>|null
     */
    public function getBCC(): ?array
    {
        return $this->bcc;
    }

    /**
     * @return array<Attachment>|null
     */
    public function getAttachments(): ?array
    {
        return $this->attachments;
    }

    public function isHtml(): bool
    {
        return $this->html;
    }
}
