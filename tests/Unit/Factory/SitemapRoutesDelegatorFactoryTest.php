<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Tests\Unit\Factory;

use Contenir\Sitemap\Mezzio\Factory\SitemapRoutesDelegatorFactory;
use Contenir\Sitemap\Mezzio\Handler\RobotsHandler;
use Contenir\Sitemap\Mezzio\Handler\SitemapHandler;
use Contenir\Sitemap\Mezzio\Tests\TestAsset\Application\ApplicationFactory;
use Contenir\Sitemap\Mezzio\Tests\TestAsset\Container\InMemoryContainer;
use Contenir\Sitemap\Mezzio\Tests\TestAsset\Router\RecordingRouter;
use Laminas\HttpHandlerRunner\RequestHandlerRunnerInterface;
use Mezzio\Application;
use Mezzio\Router\Route;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_map;
use function array_values;

#[Group('unit')]
final class SitemapRoutesDelegatorFactoryTest extends TestCase
{
    private RecordingRouter $router;

    #[Test]
    public function leavesOutDisabledRoutes(): void
    {
        $this->delegate(['sitemap' => ['routes' => ['sitemap' => false, 'robots' => false]]]);

        static::assertSame([], $this->router->routes);
    }

    #[Test]
    public function registersTheConfiguredPaths(): void
    {
        $this->delegate(['sitemap' => ['routes' => ['sitemap' => '/map.xml', 'robots' => '/bots.txt']]]);

        static::assertSame(
            ['sitemap' => ['/map.xml', ['GET']], 'robots' => ['/bots.txt', ['GET']]],
            $this->routes(),
        );
    }

    #[Test]
    public function registersTheDefaultGetRoutes(): void
    {
        $this->delegate();

        static::assertSame(
            ['sitemap' => ['/sitemap.xml', ['GET']], 'robots' => ['/robots.txt', ['GET']]],
            $this->routes(),
        );
    }

    #[Test]
    public function returnsTheDelegatedApplication(): void
    {
        $app = ApplicationFactory::recordingRoutes(
            $this->router,
            $this->createStub(RequestHandlerRunnerInterface::class),
        );

        static::assertSame($app, (new SitemapRoutesDelegatorFactory())(
            new InMemoryContainer(),
            Application::class,
            static fn(): Application => $app,
        ));
    }

    #[Test]
    public function routesEachPathToItsHandler(): void
    {
        $this->delegate(['sitemap' => ['routes' => ['robots' => false]]]);

        static::assertSame(['sitemap' => SitemapHandler::ROUTE], array_map(
            static fn(Route $route): string => $route->getName(),
            $this->router->routes,
        ));
    }

    #[Test]
    public function routesTheRobotsPathToTheRobotsRoute(): void
    {
        $this->delegate(['sitemap' => ['routes' => ['sitemap' => false]]]);

        static::assertSame([RobotsHandler::ROUTE], array_map(
            static fn(Route $route): string => $route->getName(),
            array_values($this->router->routes),
        ));
    }

    protected function setUp(): void
    {
        $this->router = new RecordingRouter();
    }

    /**
     * @param array<string, mixed> $config
     */
    private function delegate(array $config = []): Application
    {
        $app = ApplicationFactory::recordingRoutes(
            $this->router,
            $this->createStub(RequestHandlerRunnerInterface::class),
        );

        return (new SitemapRoutesDelegatorFactory())(
            new InMemoryContainer(['config' => $config]),
            Application::class,
            static fn(): Application => $app,
        );
    }

    /**
     * @return array<string, array{string, list<string>|null}>
     */
    private function routes(): array
    {
        return array_map(
            static fn(Route $route): array => [$route->getPath(), $route->getAllowedMethods()],
            $this->router->routes,
        );
    }
}
