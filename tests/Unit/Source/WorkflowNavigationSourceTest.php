<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Tests\Unit\Source;

use Contenir\Sitemap\Mezzio\ChangeFrequency;
use Contenir\Sitemap\Mezzio\SitemapEntry;
use Contenir\Sitemap\Mezzio\Source\WorkflowNavigationSource;
use Contenir\Sitemap\Mezzio\Tests\TestAsset\Strategy\FixedNavigationStrategy;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_map;

use const DATE_ATOM;

#[Group('unit')]
final class WorkflowNavigationSourceTest extends TestCase
{
    private const array ROUTES = [
        'page-1' => '/about',
        'page-2' => '/about/team',
        'page-3' => '/contact',
        'page-4' => '/news',
    ];

    /**
     * @param list<SitemapEntry> $entries
     *
     * @return list<string>
     */
    private static function locations(array $entries): array
    {
        return array_map(static fn(SitemapEntry $entry): string => $entry->location, $entries);
    }

    /**
     * A navigation page in the shape contenir-workflow-mezzio's strategy builds.
     *
     * @param list<array<string, mixed>> $pages
     *
     * @return array<string, mixed>
     */
    private static function page(string $route, array $pages = [], bool $visible = true): array
    {
        return [
            'label'      => 'Untitled',
            'route'      => $route,
            'visible'    => $visible,
            'lastmod'    => null,
            'changefreq' => 'monthly',
            'priority'   => '0.6',
            'pages'      => $pages,
        ];
    }

    #[Test]
    public function generatesEachPageUrlWithTheRouter(): void
    {
        $entries = $this->entries([self::page('page-1', [self::page('page-2')]), self::page('page-3')]);

        static::assertSame(['/about', '/about/team', '/contact'], self::locations($entries));
    }

    #[Test]
    public function hidesTheWholeBranchOfAnInvisiblePage(): void
    {
        $entries = $this->entries([
            self::page('page-1', [self::page('page-2')], visible: false),
            self::page('page-3'),
        ]);

        static::assertSame(['/contact'], self::locations($entries));
    }

    #[Test]
    public function includesPagesThatDoNotSayWhetherTheyAreVisible(): void
    {
        $entries = $this->entries([['route' => 'page-1']]);

        static::assertSame(['/about'], self::locations($entries));
    }

    #[Test]
    public function leavesOutValuesThePageDoesNotGive(): void
    {
        $entry = $this->entries([['route' => 'page-1']])[0];

        static::assertSame([null, null, null], [$entry->lastModified, $entry->changeFrequency, $entry->priority]);
    }

    #[Test]
    public function leavesOutValuesTheSitemapProtocolDoesNotAccept(): void
    {
        $entry = $this->entries([[
            'route'      => 'page-1',
            'lastmod'    => 'soon',
            'changefreq' => 'often',
            'priority'   => '2',
        ]])[0];

        static::assertSame([null, null, null], [$entry->lastModified, $entry->changeFrequency, $entry->priority]);
    }

    #[Test]
    public function readsTheLastModifiedChangeFrequencyAndPriority(): void
    {
        $page            = self::page('page-1');
        $page['lastmod'] = '2026-03-04T05:06:07+10:00';

        $entry = $this->entries([$page])[0];

        static::assertSame(
            ['2026-03-04T05:06:07+10:00', ChangeFrequency::Monthly, 0.6],
            [$entry->lastModified?->format(DATE_ATOM), $entry->changeFrequency, $entry->priority],
        );
    }

    #[Test]
    public function skipsPagesAndChildListsThatAreNotArrays(): void
    {
        $entries = $this->entries(['page-1', ['route' => 'page-1', 'pages' => 'page-2'], self::page('page-3')]);

        static::assertSame(['/about', '/contact'], self::locations($entries));
    }

    #[Test]
    public function skipsPagesWithoutARegisteredRouteButNotTheirChildren(): void
    {
        $entries = $this->entries([
            self::page('folder-9', [self::page('page-2')]),
            ['label' => 'No route', 'pages' => [self::page('page-3')]],
            ['route' => 42, 'pages' => [self::page('page-4')]],
        ]);

        static::assertSame(['/about/team', '/contact', '/news'], self::locations($entries));
    }

    /**
     * @param list<mixed> $navigation
     *
     * @return list<SitemapEntry>
     */
    private function entries(array $navigation): array
    {
        $router = $this->createStub(RouterInterface::class);
        $router->method('generateUri')->willReturnCallback(static fn(string $name): string => self::ROUTES[$name]);

        /** @var list<array<string, mixed>> $navigation */
        $source = new WorkflowNavigationSource(new FixedNavigationStrategy($navigation, self::ROUTES), $router);

        return $source->getEntries();
    }
}
