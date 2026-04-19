<?php

namespace Utopia\Messaging\Routing;

use Utopia\Messaging\Adapter;
use Utopia\Messaging\Message;

class StaticRouteResolver implements RouteResolverInterface
{
    /**
     * @var array<string, Adapter>
     */
    private array $routes = [];

    /**
     * @param array<string, Adapter> $routes
     */
    public function __construct(array $routes = [])
    {
        $this->routes = $routes;
    }

    public function set(string $messageClass, Adapter $adapter): self
    {
        $this->routes[$messageClass] = $adapter;

        return $this;
    }

    public function resolve(Message $message, array $context = []): Adapter
    {
        $messageClass = $message::class;

        if (isset($this->routes[$messageClass])) {
            return $this->routes[$messageClass];
        }

        foreach ($this->routes as $routeClass => $adapter) {
            if (\is_a($message, $routeClass)) {
                return $adapter;
            }
        }

        throw new UnknownRouteException("No adapter route found for {$messageClass}.");
    }
}
