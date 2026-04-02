<?php

namespace Utopia\Messaging\Messages\Email;

class Recipient
{
    public function __construct(
        private string $email,
        private string $name = '',
    ) {
        if (empty($email)) {
            throw new \InvalidArgumentException('Recipient email must not be empty.');
        }
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
