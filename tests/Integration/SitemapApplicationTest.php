<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Tests\Integration;

use Contenir\Sitemap\Mezzio\SitemapEntry;
use Contenir\Sitemap\Mezzio\SitemapSourceInterface;
use Contenir\Sitemap\Mezzio\Tests\TestAsset\Resource\ResourceFactory;
use Contenir\Sitemap\Mezzio\Tests\TestAsset\Source\InMemorySitemapSource;
use Contenir\Sitemap\Mezzio\Tests\Trait\SitemapApplicationTrait;
use Contenir\Sitemap\Mezzio\Tests\Trait\TemporaryDirectoryTrait;
use Contenir\Workflow\Strategy\ResourceStrategy;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * /sitemap.xml and /robots.txt through a real Mezzio pipeline, router and
 * container, built from a contenir-workflow-mezzio resource tree.
 */
#[Group('integration')]
#[Group('handler')]
final class SitemapApplicationTest extends TestCase
{
    use SitemapApplicationTrait;
    use TemporaryDirectoryTrait;

    #[Test]
    public function answersHeadRequestsForTheSitemap(): void
    {
        $this->createSitemapApplication([ResourceFactory::page(1, 'about')]);

        $response = $this->request('/sitemap.xml', 'HEAD');

        static::assertSame(
            [200, ['application/xml; charset=utf-8'], ''],
            [$response->getStatusCode(), $response->getHeader('Content-Type'), (string) $response->getBody()],
        );
    }

    #[Test]
    public function buildsTheSitemapFromAnotherSource(): void
    {
        $this->createSitemapApplication([]);
        $this->container->setAllowOverride(true);
        $this->container->setAlias(SitemapSourceInterface::class, InMemorySitemapSource::class);
        $this->container->setService(InMemorySitemapSource::class, new InMemorySitemapSource([
            new SitemapEntry('/catalogue/widgets'),
        ]));

        static::assertStringContainsString(
            '<loc>https://www.example.com/catalogue/widgets</loc>',
            (string) $this->request('/sitemap.xml')->getBody(),
        );
    }

    #[Test]
    public function doesNotRouteADisabledSitemap(): void
    {
        $this->createSitemapApplication([], ['routes' => ['sitemap' => false]]);

        static::assertSame(404, $this->request('/sitemap.xml')->getStatusCode());
    }

    #[Test]
    public function leavesLastModifiedOutWithTheDefaultWorkflowStrategy(): void
    {
        $this->createSitemapApplication(
            [ResourceFactory::modifiedPage(1, 'about')],
            workflowManager: ['strategy' => ResourceStrategy::class],
        );

        static::assertStringNotContainsString('<lastmod>', (string) $this->request('/sitemap.xml')->getBody());
    }

    #[Test]
    public function servesRobotsAtAConfiguredPath(): void
    {
        $this->createSitemapApplication([], ['routes' => ['robots' => '/bots.txt']]);

        static::assertSame(
            [404, 200],
            [$this->request('/robots.txt')->getStatusCode(), $this->request('/bots.txt')->getStatusCode()],
        );
    }

    #[Test]
    public function servesRobotsPointingAtTheSitemap(): void
    {
        $this->createSitemapApplication([], ['robots' => ['rules' => ['*' => ['disallow' => ['/admin/']]]]]);

        $response = $this->request('/robots.txt');

        static::assertSame(
            [
                200,
                ['text/plain; charset=utf-8'],
                "# robots.txt for https://www.example.com/\n\n"
                    . "User-agent: *\nDisallow: /admin/\n\n"
                    . "Sitemap: https://www.example.com/sitemap.xml\n",
            ],
            [$response->getStatusCode(), $response->getHeader('Content-Type'), (string) $response->getBody()],
        );
    }

    #[Test]
    public function servesTheWorkflowPagesAsTheSitemap(): void
    {
        $this->createSitemapApplication([
            ResourceFactory::modifiedPage(1, '', '2026-01-02T03:04:05+00:00'),
            ResourceFactory::modifiedPage(2, 'about', '2026-03-04T05:06:07+10:00', [
                ResourceFactory::page(3, 'about/team'),
            ]),
            ResourceFactory::folder(4, 'legal', [ResourceFactory::modifiedPage(5, 'legal/privacy', modified: null)]),
        ]);

        $response = $this->request('/sitemap.xml');

        static::assertSame(
            [
                200,
                ['application/xml; charset=utf-8'],
                <<<'XML'
                    <?xml version="1.0" encoding="UTF-8"?>
                    <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
                      <url>
                        <loc>https://www.example.com/</loc>
                        <lastmod>2026-01-02T03:04:05+00:00</lastmod>
                        <changefreq>monthly</changefreq>
                        <priority>0.6</priority>
                      </url>
                      <url>
                        <loc>https://www.example.com/about</loc>
                        <lastmod>2026-03-04T05:06:07+10:00</lastmod>
                        <changefreq>monthly</changefreq>
                        <priority>0.6</priority>
                      </url>
                      <url>
                        <loc>https://www.example.com/about/team</loc>
                        <changefreq>monthly</changefreq>
                        <priority>0.6</priority>
                      </url>
                      <url>
                        <loc>https://www.example.com/legal/privacy</loc>
                        <changefreq>monthly</changefreq>
                        <priority>0.6</priority>
                      </url>
                    </urlset>

                    XML,
            ],
            [$response->getStatusCode(), $response->getHeader('Content-Type'), (string) $response->getBody()],
        );
    }

    #[Test]
    public function usesTheConfiguredBaseUrlWhateverTheHost(): void
    {
        $this->createSitemapApplication([ResourceFactory::page(1, 'about')], ['base_url' => 'https://example.org']);

        static::assertStringContainsString(
            '<loc>https://example.org/about</loc>',
            (string) $this->request('/sitemap.xml')->getBody(),
        );
    }

    protected function setUp(): void
    {
        $this->setUpTemporaryDirectory();
    }

    protected function tearDown(): void
    {
        $this->tearDownTemporaryDirectory();
    }
}
