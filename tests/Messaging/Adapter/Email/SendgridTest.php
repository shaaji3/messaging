<?php

namespace Utopia\Tests\Adapter\Email;

use Utopia\Messaging\Adapter\Email\Sendgrid;
use Utopia\Messaging\Messages\Email;
use Utopia\Messaging\Messages\Email\Attachment;
use Utopia\Messaging\Messages\Email\Recipient;
use Utopia\Tests\Adapter\Base;

class SendgridTest extends Base
{
    public function testSendEmail(): void
    {
        $key = getenv('SENDGRID_API_KEY');
        $sender = new Sendgrid($key);

        $to = \getenv('TEST_EMAIL');
        $subject = 'Test Subject';
        $content = 'Test Content';
        $fromEmail = \getenv('TEST_FROM_EMAIL');
        $cc = [new Recipient(\getenv('TEST_CC_EMAIL'))];
        $bcc = [new Recipient(\getenv('TEST_BCC_EMAIL'), \getenv('TEST_BCC_NAME'))];

        $message = new Email(
            to: [new Recipient($to)],
            subject: $subject,
            content: $content,
            fromName: 'Tester',
            fromEmail: $fromEmail,
            cc: $cc,
            bcc: $bcc,
        );

        $response = $sender->send($message);

        $this->assertResponse($response);
    }

    public function testSendEmailWithAttachment(): void
    {
        $key = \getenv('SENDGRID_API_KEY');
        $sender = new Sendgrid($key);

        $to = \getenv('TEST_EMAIL');
        $subject = 'Test Subject';
        $content = 'Test Content';
        $fromEmail = \getenv('TEST_FROM_EMAIL');

        $message = new Email(
            to: [new Recipient($to)],
            subject: $subject,
            content: $content,
            fromName: 'Tester',
            fromEmail: $fromEmail,
            attachments: [new Attachment(
                name: 'image.png',
                path: __DIR__ . '/../../../assets/image.png',
                type: 'image/png'
            )],
        );

        $response = $sender->send($message);

        $this->assertResponse($response);
    }
}
