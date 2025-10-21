<?php

namespace Utopia\Tests\Adapter\Email;

use Utopia\Messaging\Adapter\Email\Resend;
use Utopia\Messaging\Messages\Email;
use Utopia\Messaging\Messages\Email\Attachment;
use Utopia\Tests\Adapter\Base;

class ResendTest extends Base
{
    public function testSendEmail(): void
    {
        $key = \getenv('RESEND_API_KEY');
        $sender = new Resend($key);

        $to = \getenv('TEST_EMAIL');
        $subject = 'Test Subject';
        $content = 'Test Content';
        $fromEmail = \getenv('TEST_FROM_EMAIL');
        $cc = [['email' => \getenv('TEST_CC_EMAIL')]];
        $bcc = [['name' => \getenv('TEST_BCC_NAME'), 'email' => \getenv('TEST_BCC_EMAIL')]];

        $message = new Email(
            to: [$to],
            subject: $subject,
            content: $content,
            fromName: 'Test Sender',
            fromEmail: $fromEmail,
            cc: $cc,
            bcc: $bcc,
        );

        $response = $sender->send($message);

        $this->assertResponse($response);
    }

    public function testSendEmailWithHtml(): void
    {
        $key = \getenv('RESEND_API_KEY');
        $sender = new Resend($key);

        $to = \getenv('TEST_EMAIL');
        $subject = 'Test HTML Subject';
        $content = '<h1>Test HTML Content</h1><p>This is a test email with HTML content.</p>';
        $fromEmail = \getenv('TEST_FROM_EMAIL');

        $message = new Email(
            to: [$to],
            subject: $subject,
            content: $content,
            fromName: 'Test Sender',
            fromEmail: $fromEmail,
            html: true,
        );

        $response = $sender->send($message);

        $this->assertResponse($response);
    }

    public function testSendEmailWithReplyTo(): void
    {
        $key = \getenv('RESEND_API_KEY');
        $sender = new Resend($key);

        $to = \getenv('TEST_EMAIL');
        $subject = 'Test Reply-To Subject';
        $content = 'Test Content with Reply-To';
        $fromEmail = \getenv('TEST_FROM_EMAIL');
        $replyToEmail = \getenv('TEST_CC_EMAIL');

        $message = new Email(
            to: [$to],
            subject: $subject,
            content: $content,
            fromName: 'Test Sender',
            fromEmail: $fromEmail,
            replyToName: 'Reply To Name',
            replyToEmail: $replyToEmail,
        );

        $response = $sender->send($message);

        $this->assertResponse($response);
    }

    public function testSendMultipleEmails(): void
    {
        $key = \getenv('RESEND_API_KEY');
        $sender = new Resend($key);

        $to1 = \getenv('TEST_EMAIL');
        $to2 = \getenv('TEST_CC_EMAIL');
        $subject = 'Test Batch Subject';
        $content = 'Test Batch Content';
        $fromEmail = \getenv('TEST_FROM_EMAIL');

        $message = new Email(
            to: [$to1, $to2],
            subject: $subject,
            content: $content,
            fromName: 'Test Sender',
            fromEmail: $fromEmail,
        );

        $response = $sender->send($message);

        $this->assertEquals(2, $response['deliveredTo'], \var_export($response, true));
        $this->assertEquals('', $response['results'][0]['error'], \var_export($response, true));
        $this->assertEquals('success', $response['results'][0]['status'], \var_export($response, true));
        $this->assertEquals('', $response['results'][1]['error'], \var_export($response, true));
        $this->assertEquals('success', $response['results'][1]['status'], \var_export($response, true));
    }

    public function testSendEmailWithAttachmentsThrowsException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Resend does not support attachments at this time');

        $key = \getenv('RESEND_API_KEY');
        $sender = new Resend($key);

        $to = \getenv('TEST_EMAIL');
        $subject = 'Test Subject';
        $content = 'Test Content';
        $fromEmail = \getenv('TEST_FROM_EMAIL');

        $message = new Email(
            to: [$to],
            subject: $subject,
            content: $content,
            fromName: 'Test Sender',
            fromEmail: $fromEmail,
            attachments: [new Attachment(
                name: 'image.png',
                path: __DIR__.'/../../../assets/image.png',
                type: 'image/png'
            )],
        );

        $sender->send($message);
    }
}
