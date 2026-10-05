<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Tests\TestAsset\Application;

use Contenir\Sitemap\Mezzio\Tests\TestAsset\Container\InMemoryContainer;
use Contenir\Sitemap\Mezzio\Tests\TestAsset\Router\RecordingRouter;
use Laminas\HttpHandlerRunner\RequestHandlerRunnerInterface;
use Laminas\Stratigility\MiddlewarePipe;
use Mezzio\Application;
use Mezzio\MiddlewareContainer;
use Mezzio\MiddlewareFactory;
use Mezzio\Router\RouteCollector;

/**
 * Builds a Mezzio Application whose routes land in a RecordingRouter.
 */
final class ApplicationFactory
{
    public static function recordingRoutes(RecordingRouter $router, RequestHandlerRunnerInterface $runner): Application
    {
        return new Application(
            new MiddlewareFactory(new MiddlewareContainer(new InMemoryContainer())),
            new MiddlewarePipe(),
            new RouteCollector($router),
            $runner,
        );
    }
}
