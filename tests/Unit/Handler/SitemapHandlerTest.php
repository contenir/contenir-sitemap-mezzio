<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Tests\Unit\Handler;

use Contenir\Sitemap\Mezzio\ChangeFrequency;
use Contenir\Sitemap\Mezzio\Handler\SitemapHandler;
use Contenir\Sitemap\Mezzio\Http\BaseUrl;
use Contenir\Sitemap\Mezzio\SitemapEntry;
use Contenir\Sitemap\Mezzio\Tests\TestAsset\Source\InMemorySitemapSource;
use DateTimeImmutable;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\StreamFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

#[Group('unit')]
#[Group('handler')]
final class SitemapHandlerTest extends TestCase
{
    #[Test]
    public function answersWithAnXmlContentType(): void
    {
        $response = $this->handle([]);

        static::assertSame(['application/xml; charset=utf-8'], $response->getHeader('Content-Type'));
    }

    #[Test]
    public function answersWithStatus200(): void
    {
        static::assertSame(200, $this->handle([])->getStatusCode());
    }

    #[Test]
    public function escapesUrlsForXml(): void
    {
        $body = (string) $this->handle([new SitemapEntry('/search?a=1&b=<2>')])->getBody();

        static::assertStringContainsString(
            '<loc>https://www.example.com/search?a=1&amp;b=&lt;2&gt;</loc>',
            $body,
        );
    }

    #[Test]
    public function leavesOutRepeatedUrls(): void
    {
        $response = $this->handle([
            new SitemapEntry('/about', changeFrequency: ChangeFrequency::Daily),
            new SitemapEntry('https://www.example.com/about', changeFrequency: ChangeFrequency::Yearly),
            new SitemapEntry('/contact'),
        ]);

        static::assertSame(<<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
              <url>
                <loc>https://www.example.com/about</loc>
                <changefreq>daily</changefreq>
              </url>
              <url>
                <loc>https://www.example.com/contact</loc>
              </url>
            </urlset>

            XML, (string) $response->getBody());
    }

    #[Test]
    public function rendersAnEmptyUrlSetWithoutEntries(): void
    {
        static::assertSame(<<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/>

            XML, (string) $this->handle([])->getBody());
    }

    #[Test]
    public function rendersEveryEntryWithTheValuesItHas(): void
    {
        $response = $this->handle([
            new SitemapEntry(
                '/',
                new DateTimeImmutable('2026-03-04T05:06:07+10:00'),
                ChangeFrequency::Weekly,
                1.0,
            ),
            new SitemapEntry('/about', priority: 0.6),
            new SitemapEntry('https://cdn.example.com/brochure.pdf', new DateTimeImmutable('2025-12-31 23:59:59 UTC')),
        ]);

        static::assertSame(<<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
              <url>
                <loc>https://www.example.com/</loc>
                <lastmod>2026-03-04T05:06:07+10:00</lastmod>
                <changefreq>weekly</changefreq>
                <priority>1</priority>
              </url>
              <url>
                <loc>https://www.example.com/about</loc>
                <priority>0.6</priority>
              </url>
              <url>
                <loc>https://cdn.example.com/brochure.pdf</loc>
                <lastmod>2025-12-31T23:59:59+00:00</lastmod>
              </url>
            </urlset>

            XML, (string) $response->getBody());
    }

    #[Test]
    public function usesTheConfiguredBaseUrlForPaths(): void
    {
        $handler = new SitemapHandler(
            new InMemorySitemapSource([new SitemapEntry('/about')]),
            new BaseUrl('https://example.org/site'),
            new ResponseFactory(),
            new StreamFactory(),
        );

        $body = (string) $handler->handle(new ServerRequest(uri: 'https://forged.example.net/'))->getBody();

        static::assertStringContainsString('<loc>https://example.org/site/about</loc>', $body);
    }

    /**
     * @param list<SitemapEntry> $entries
     */
    private function handle(array $entries): ResponseInterface
    {
        $handler = new SitemapHandler(
            new InMemorySitemapSource($entries),
            new BaseUrl(),
            new ResponseFactory(),
            new StreamFactory(),
        );

        return $handler->handle(new ServerRequest(uri: 'https://www.example.com/sitemap.xml'));
    }
}
