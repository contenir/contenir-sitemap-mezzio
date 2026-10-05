<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Tests\Unit\Factory;

use Contenir\Sitemap\Mezzio\Factory\SitemapHandlerFactory;
use Contenir\Sitemap\Mezzio\SitemapEntry;
use Contenir\Sitemap\Mezzio\SitemapSourceInterface;
use Contenir\Sitemap\Mezzio\Tests\TestAsset\Container\InMemoryContainer;
use Contenir\Sitemap\Mezzio\Tests\TestAsset\Source\InMemorySitemapSource;
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
final class SitemapHandlerFactoryTest extends TestCase
{
    /**
     * @param array<string, mixed> $services
     */
    private static function container(array $services = []): InMemoryContainer
    {
        return new InMemoryContainer([
            SitemapSourceInterface::class   => new InMemorySitemapSource([new SitemapEntry('/about')]),
            ResponseFactoryInterface::class => new ResponseFactory(),
            StreamFactoryInterface::class   => new StreamFactory(),
            ...$services,
        ]);
    }

    #[Test]
    public function buildsTheHandlerAroundTheSitemapSource(): void
    {
        $handler = (new SitemapHandlerFactory())(self::container());

        $body = (string) $handler->handle(new ServerRequest(uri: 'https://www.example.com/sitemap.xml'))->getBody();

        static::assertStringContainsString('<loc>https://www.example.com/about</loc>', $body);
    }

    #[Test]
    public function rejectsAResponseFactoryOfTheWrongType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Service "' . ResponseFactoryInterface::class . '" must be a ');

        (new SitemapHandlerFactory())(self::container([ResponseFactoryInterface::class => new StreamFactory()]));
    }

    #[Test]
    public function rejectsASitemapSourceOfTheWrongType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Service "' . SitemapSourceInterface::class . '" must be a ' . SitemapSourceInterface::class,
        );

        (new SitemapHandlerFactory())(self::container([SitemapSourceInterface::class => []]));
    }

    #[Test]
    public function rejectsAStreamFactoryOfTheWrongType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Service "' . StreamFactoryInterface::class . '" must be a ');

        (new SitemapHandlerFactory())(self::container([StreamFactoryInterface::class => new ResponseFactory()]));
    }

    #[Test]
    public function usesTheConfiguredBaseUrl(): void
    {
        $handler = (new SitemapHandlerFactory())(self::container([
            'config' => ['sitemap' => ['base_url' => 'https://example.org']],
        ]));

        $body = (string) $handler->handle(new ServerRequest(uri: 'https://forged.example.net/sitemap.xml'))->getBody();

        static::assertStringContainsString('<loc>https://example.org/about</loc>', $body);
    }
}
