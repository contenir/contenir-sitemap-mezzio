<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Tests\Unit\Strategy;

use Contenir\Sitemap\Mezzio\Strategy\MetadataResourceStrategy;
use Contenir\Sitemap\Mezzio\Tests\TestAsset\Repository\InMemoryResourceAdapter;
use Contenir\Sitemap\Mezzio\Tests\TestAsset\Resource\ResourceFactory;
use Contenir\Sitemap\Mezzio\Tests\Trait\InMemoryCacheTrait;
use Contenir\Workflow\ResourceInterface;
use Contenir\Workflow\Workflow\PageWorkflow;
use Laminas\ServiceManager\PluginManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_column;

#[Group('unit')]
final class MetadataResourceStrategyTest extends TestCase
{
    use InMemoryCacheTrait;

    #[Test]
    public function cachesTheLastModifiedWithTheNavigation(): void
    {
        $this->strategy([ResourceFactory::modifiedPage(1, 'about')])->getNavigationConfig();

        $cached = $this->cacheItems['SitemapNavigation'] ?? null;

        static::assertSame(
            ['2026-03-04T05:06:07+10:00'],
            array_column((array) ($cached['navigation'] ?? []), 'lastmod'),
        );
    }

    #[Test]
    public function keepsTheRestOfTheWorkflowPage(): void
    {
        $navigation = $this->strategy([ResourceFactory::modifiedPage(1, 'about')])->getNavigationConfig();

        static::assertSame(
            [
                'label'      => 'Untitled',
                'route'      => 'page-1',
                'visible'    => true,
                'lastmod'    => '2026-03-04T05:06:07+10:00',
                'changefreq' => 'monthly',
                'priority'   => '0.6',
                'pages'      => [],
            ],
            $navigation[0],
        );
    }

    #[Test]
    public function keepsTheWorkflowLastModifiedForResourcesWithoutMetadata(): void
    {
        $navigation = $this->strategy([ResourceFactory::page(1, 'about')])->getNavigationConfig();

        static::assertSame([null], array_column($navigation, 'lastmod'));
    }

    #[Test]
    public function keepsTheWorkflowLastModifiedWhenTheMetadataHasNoModifiedDate(): void
    {
        $navigation = $this->strategy([ResourceFactory::modifiedPage(1, 'about', modified: null)])
            ->getNavigationConfig();

        static::assertSame([null], array_column($navigation, 'lastmod'));
    }

    #[Test]
    public function setsEachPageLastModifiedFromItsResourceMetadata(): void
    {
        $navigation = $this->strategy([
            ResourceFactory::modifiedPage(1, 'about', '2026-01-02T03:04:05+00:00', [
                ResourceFactory::modifiedPage(2, 'about/team', '2026-02-03T04:05:06+11:00'),
            ]),
        ])->getNavigationConfig();

        static::assertSame(
            ['2026-01-02T03:04:05+00:00', '2026-02-03T04:05:06+11:00'],
            [$navigation[0]['lastmod'], $navigation[0]['pages'][0]['lastmod'] ?? null],
        );
    }

    /**
     * @param list<ResourceInterface> $resources
     */
    private function strategy(array $resources): MetadataResourceStrategy
    {
        $workflows = $this->createStub(PluginManagerInterface::class);
        $workflows->method('get')->willReturnCallback(static fn(): PageWorkflow => new PageWorkflow());

        return new MetadataResourceStrategy(
            new InMemoryResourceAdapter($resources),
            $workflows,
            $this->createInMemoryCache(),
            ['cache_key' => 'SitemapNavigation'],
        );
    }
}
