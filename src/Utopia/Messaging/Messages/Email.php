<?php

namespace Utopia\Messaging\Messages;

use Utopia\Messaging\Message;
use Utopia\Messaging\Messages\Email\Attachment;
use Utopia\Messaging\Messages\Email\Recipient;

class Email implements Message
{
    /**
     * @param  array<Recipient>  $to The recipients of the email.
     * @param  string  $subject The subject of the email.
     * @param  string  $content The content of the email.
     * @param  string  $fromName The name of the sender.
     * @param  string  $fromEmail The email address of the sender.
     * @param  string|null  $replyToName The name of the reply to.
     * @param  string|null  $replyToEmail The email address of the reply to.
     * @param  array<Recipient>|null  $cc The CC recipients of the email.
     * @param  array<Recipient>|null  $bcc The BCC recipients of the email.
     * @param  array<Attachment>|null  $attachments The attachments of the email.
     * @param  bool  $html Whether the message is HTML or not.
     */
    public function __construct(
        private array $to,
        private string $subject,
        private string $content,
        private string $fromName,
        private string $fromEmail,
        private ?string $replyToName = null,
        private ?string $replyToEmail = null,
        private ?array $cc = null,
        private ?array $bcc = null,
        private ?array $attachments = null,
        private bool $html = false,
    ) {
        if (\is_null($this->replyToName)) {
            $this->replyToName = $this->fromName;
        }

        if (\is_null($this->replyToEmail)) {
            $this->replyToEmail = $this->fromEmail;
        }
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
