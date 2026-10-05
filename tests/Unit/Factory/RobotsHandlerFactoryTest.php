<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Tests\Unit\Factory;

use Contenir\Sitemap\Mezzio\Factory\RobotsHandlerFactory;
use Contenir\Sitemap\Mezzio\Tests\TestAsset\Container\InMemoryContainer;
use InvalidArgumentException;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\StreamFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

#[Group('unit')]
final class RobotsHandlerFactoryTest extends TestCase
{
    /**
     * @param array<string, mixed> $services
     */
    private static function robots(array $services = []): string
    {
        $handler = (new RobotsHandlerFactory())(new InMemoryContainer([
            ResponseFactoryInterface::class => new ResponseFactory(),
            StreamFactoryInterface::class   => new StreamFactory(),
            ...$services,
        ]));

        return (string) $handler->handle(new ServerRequest(uri: 'https://www.example.com/robots.txt'))->getBody();
    }

    #[Test]
    public function buildsTheLaminasMvcRobotsByDefault(): void
    {
        static::assertSame(
            "# robots.txt for https://www.example.com/\n\n"
                . "User-agent: *\nAllow: /\nDisallow: /.well-known/\n\n"
                . "Sitemap: https://www.example.com/sitemap.xml\n",
            self::robots(),
        );
    }

    #[Test]
    public function leavesTheSitemapOutWhenItsRouteIsDisabled(): void
    {
        $robots = self::robots(['config' => ['sitemap' => ['routes' => ['sitemap' => false]]]]);

        static::assertStringNotContainsString('Sitemap:', $robots);
    }

    #[Test]
    public function pointsAtTheConfiguredSitemapPathUnderTheConfiguredBaseUrl(): void
    {
        $robots = self::robots([
            'config' => ['sitemap' => ['base_url' => 'https://example.org', 'routes' => ['sitemap' => '/map.xml']]],
        ]);

        static::assertStringEndsWith("\n\nSitemap: https://example.org/map.xml\n", $robots);
    }

    #[Test]
    public function rejectsAResponseFactoryOfTheWrongType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Service "' . ResponseFactoryInterface::class . '" must be a ');

        self::robots([ResponseFactoryInterface::class => new StreamFactory()]);
    }

    #[Test]
    public function rejectsAStreamFactoryOfTheWrongType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Service "' . StreamFactoryInterface::class . '" must be a ');

        self::robots([StreamFactoryInterface::class => new ResponseFactory()]);
    }

    #[Test]
    public function usesTheConfiguredRules(): void
    {
        $robots = self::robots(['config' => ['sitemap' => ['robots' => ['rules' => ['*' => ['disallow' => ['/']]]]]]]);

        static::assertStringContainsString("User-agent: *\nDisallow: /\n\n", $robots);
    }
}
