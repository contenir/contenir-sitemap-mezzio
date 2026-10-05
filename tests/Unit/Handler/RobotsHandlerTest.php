<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Tests\Unit\Handler;

use Contenir\Sitemap\Mezzio\Handler\RobotsHandler;
use Contenir\Sitemap\Mezzio\Http\BaseUrl;
use Contenir\Sitemap\Mezzio\Robots\RobotsGroup;
use Contenir\Sitemap\Mezzio\Robots\RobotsRules;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\StreamFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

#[Group('unit')]
#[Group('handler')]
final class RobotsHandlerTest extends TestCase
{
    #[Test]
    public function answersWithAPlainTextContentType(): void
    {
        static::assertSame(['text/plain; charset=utf-8'], $this->handle('/sitemap.xml')->getHeader('Content-Type'));
    }

    #[Test]
    public function answersWithStatus200(): void
    {
        static::assertSame(200, $this->handle('/sitemap.xml')->getStatusCode());
    }

    #[Test]
    public function leavesTheSitemapLineOutWhenTheSitemapRouteIsDisabled(): void
    {
        static::assertSame(
            "# robots.txt for https://www.example.com/\n\nUser-agent: *\nDisallow: /private/\n",
            (string) $this->handle(null)->getBody(),
        );
    }

    #[Test]
    public function pointsAtTheSitemapUnderTheBaseUrl(): void
    {
        static::assertSame(
            "# robots.txt for https://www.example.com/\n\n"
                . "User-agent: *\nDisallow: /private/\n\n"
                . "Sitemap: https://www.example.com/sitemap-index.xml\n",
            (string) $this->handle('/sitemap-index.xml')->getBody(),
        );
    }

    private function handle(?string $sitemapPath): ResponseInterface
    {
        $handler = new RobotsHandler(
            new RobotsRules([new RobotsGroup('*', disallow: ['/private/'])]),
            new BaseUrl(),
            $sitemapPath,
            new ResponseFactory(),
            new StreamFactory(),
        );

        return $handler->handle(new ServerRequest(uri: 'https://www.example.com/robots.txt'));
    }
}
