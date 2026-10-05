<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Tests\TestAsset\Router;

use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Mezzio\Router\RouterInterface;
use Override;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A router that only records the routes added to it.
 */
final class RecordingRouter implements RouterInterface
{
    /** @var array<string, Route> */
    public array $routes = [];

    #[Override]
    public function addRoute(Route $route): void
    {
        $this->routes[$route->getName()] = $route;
    }

    #[Override]
    public function generateUri(string $name, array $substitutions = [], array $options = []): string
    {
        return $this->routes[$name]->getPath();
    }

    #[Override]
    public function match(ServerRequestInterface $request): RouteResult
    {
        return RouteResult::fromRouteFailure(null);
    }
}
