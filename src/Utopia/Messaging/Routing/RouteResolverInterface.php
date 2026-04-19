<?php

namespace Utopia\Messaging\Routing;

use Utopia\Messaging\Adapter;
use Utopia\Messaging\Message;

interface RouteResolverInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function resolve(Message $message, array $context = []): Adapter;
}
