<?php

namespace Utopia\Tests\Adapter\Email;

use Utopia\Messaging\Adapter\Email\Mock;
use Utopia\Messaging\Messages\Email;
use Utopia\Messaging\Messages\Email\Recipient;
use Utopia\Tests\Adapter\Base;

class EmailTest extends Base
{
    public function testSendEmail(): void
    {
        $sender = new Mock();

        $to = new Recipient('tester@localhost.test');
        $subject = 'Test Subject';
        $content = 'Test Content';
        $fromName = 'Test Sender';
        $fromEmail = 'sender@localhost.test';
        $cc = [new Recipient('tester2@localhost.test')];
        $bcc = [new Recipient('tester3@localhost.test', 'Tester3')];

        $message = new Email(
            to: [$to],
            subject: $subject,
            content: $content,
            fromName: $fromName,
            fromEmail: $fromEmail,
            cc: $cc,
            bcc: $bcc,
        );

        $response = $sender->send($message);

        $lastEmail = $this->getLastEmail();

        $this->assertResponse($response);
        $this->assertEquals($to->getEmail(), $lastEmail['to'][0]['address']);
        $this->assertEquals($fromEmail, $lastEmail['from'][0]['address']);
        $this->assertEquals($fromName, $lastEmail['from'][0]['name']);
        $this->assertEquals($subject, $lastEmail['subject']);
        $this->assertEquals($content, \trim($lastEmail['text']));
        $this->assertEquals($cc[0]->getEmail(), $lastEmail['cc'][0]['address']);
        $this->assertEquals($bcc[0]->getEmail(), $lastEmail['envelope']['to'][2]['address']);
    }
}
