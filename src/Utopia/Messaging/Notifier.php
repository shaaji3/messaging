<?php

namespace Utopia\Messaging;

use Utopia\Messaging\Routing\RouteResolverInterface;

class Notifier
{
    public function __construct(private RouteResolverInterface $resolver)
    {
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     *
     * @throws \Exception
     */
    public function send(Message $message, array $context = []): array
    {
        $adapter = $this->resolver->resolve($message, $context);

        return $adapter->send($message);
    }
}
