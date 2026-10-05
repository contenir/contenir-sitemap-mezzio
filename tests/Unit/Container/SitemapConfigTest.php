<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Tests\Unit\Container;

use Contenir\Sitemap\Mezzio\Container\SitemapConfig;
use Contenir\Sitemap\Mezzio\Tests\TestAsset\Container\InMemoryContainer;
use Contenir\Workflow\Strategy\ResourceStrategy;
use InvalidArgumentException;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class SitemapConfigTest extends TestCase
{
    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidRoutePathProvider(): array
    {
        return [
            'relative path' => ['sitemap.xml'],
            'empty'         => [''],
            'null'          => [null],
            'true'          => [true],
        ];
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function navigationServiceProvider(): array
    {
        return [
            'no config'                        => [[], ResourceStrategy::class],
            'workflow strategy'                => [
                ['workflow_manager' => ['strategy' => 'app.strategy']],
                'app.strategy',
            ],
            'sitemap navigation over workflow' => [
                [
                    'sitemap'          => ['navigation' => 'sitemap.navigation'],
                    'workflow_manager' => ['strategy' => 'app.strategy'],
                ],
                'sitemap.navigation',
            ],
            'empty navigation'                 => [['sitemap' => ['navigation' => '']], ResourceStrategy::class],
            'navigation not a string'          => [['sitemap' => ['navigation' => 42]], ResourceStrategy::class],
            'workflow section not an array'    => [['workflow_manager' => 'app.strategy'], ResourceStrategy::class],
            'sitemap section not an array'     => [['sitemap' => 'sitemap.navigation'], ResourceStrategy::class],
        ];
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function config(array $config): SitemapConfig
    {
        return SitemapConfig::fromContainer(new InMemoryContainer(['config' => $config]));
    }

    #[Test]
    public function defaultsTheRoutePaths(): void
    {
        $config = SitemapConfig::fromContainer(new InMemoryContainer());

        static::assertSame(['/sitemap.xml', '/robots.txt'], [$config->sitemapPath(), $config->robotsPath()]);
    }

    #[Test]
    public function defaultsTheRoutePathsWhenTheRoutesAreNotAnArray(): void
    {
        $config = self::config(['sitemap' => ['routes' => '/map.xml']]);

        static::assertSame(['/sitemap.xml', '/robots.txt'], [$config->sitemapPath(), $config->robotsPath()]);
    }

    #[Test]
    public function defaultsToTheDefaultRobotsRules(): void
    {
        $robots = self::config([])->robotsRules()->render('https://www.example.com', null);

        static::assertSame(
            "# robots.txt for https://www.example.com/\n\nUser-agent: *\nAllow: /\nDisallow: /.well-known/\n",
            $robots,
        );
    }

    #[Test]
    public function disablesARouteSetToFalse(): void
    {
        $config = self::config(['sitemap' => ['routes' => ['sitemap' => false, 'robots' => false]]]);

        static::assertSame([null, null], [$config->sitemapPath(), $config->robotsPath()]);
    }

    #[Test]
    public function ignoresAConfigServiceThatIsNotAnArray(): void
    {
        $config = SitemapConfig::fromContainer(new InMemoryContainer(['config' => 'sitemap']));

        static::assertSame(ResourceStrategy::class, $config->navigationService());
    }

    #[Test]
    public function readsTheBaseUrl(): void
    {
        $baseUrl = self::config(['sitemap' => ['base_url' => 'https://example.org/']])->baseUrl();

        static::assertSame(
            'https://example.org',
            $baseUrl->resolve(new ServerRequest(uri: 'https://forged.example.net/')),
        );
    }

    #[Test]
    public function readsTheRequestBaseUrlByDefault(): void
    {
        $baseUrl = self::config([])->baseUrl();

        static::assertSame(
            'https://www.example.com',
            $baseUrl->resolve(new ServerRequest(uri: 'https://www.example.com/')),
        );
    }

    #[Test]
    public function readsTheRobotsRules(): void
    {
        $robots = self::config(['sitemap' => ['robots' => ['rules' => ['*' => ['disallow' => ['/']]]]]])
            ->robotsRules()
            ->render('https://www.example.com', null);

        static::assertSame("# robots.txt for https://www.example.com/\n\nUser-agent: *\nDisallow: /\n", $robots);
    }

    #[Test]
    public function readsTheRoutePaths(): void
    {
        $config = self::config(['sitemap' => ['routes' => ['sitemap' => '/map.xml', 'robots' => '/bots.txt']]]);

        static::assertSame(['/map.xml', '/bots.txt'], [$config->sitemapPath(), $config->robotsPath()]);
    }

    #[DataProvider('invalidRoutePathProvider')]
    #[Test]
    public function rejectsARobotsRouteThatIsNeitherFalseNorAPath(mixed $path): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'sitemap.routes.robots must be a path starting with "/", or false to disable the route',
        );

        self::config(['sitemap' => ['routes' => ['robots' => $path]]])->robotsPath();
    }

    #[DataProvider('invalidRoutePathProvider')]
    #[Test]
    public function rejectsASitemapRouteThatIsNeitherFalseNorAPath(mixed $path): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'sitemap.routes.sitemap must be a path starting with "/", or false to disable the route',
        );

        self::config(['sitemap' => ['routes' => ['sitemap' => $path]]])->sitemapPath();
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProvider('navigationServiceProvider')]
    #[Test]
    public function resolvesTheNavigationService(array $config, string $expected): void
    {
        static::assertSame($expected, self::config($config)->navigationService());
    }
}
