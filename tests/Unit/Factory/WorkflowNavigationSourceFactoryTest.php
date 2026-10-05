<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Tests\Unit\Factory;

use Contenir\Sitemap\Mezzio\Factory\WorkflowNavigationSourceFactory;
use Contenir\Sitemap\Mezzio\SitemapEntry;
use Contenir\Sitemap\Mezzio\Tests\TestAsset\Container\InMemoryContainer;
use Contenir\Sitemap\Mezzio\Tests\TestAsset\Strategy\FixedNavigationStrategy;
use Contenir\Workflow\Strategy\ResourceStrategy;
use Contenir\Workflow\Strategy\ResourceStrategyInterface;
use InvalidArgumentException;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

use function array_map;

#[Group('unit')]
final class WorkflowNavigationSourceFactoryTest extends TestCase
{
    private static function strategy(string $route): FixedNavigationStrategy
    {
        return new FixedNavigationStrategy([['route' => $route]], [$route => "/{$route}"]);
    }

    #[Test]
    public function readsTheConfiguredNavigationService(): void
    {
        $locations = $this->locations([
            'config'            => ['sitemap' => ['navigation' => 'SitemapNavigation']],
            'SitemapNavigation' => self::strategy('configured'),
        ]);

        static::assertSame(['/configured'], $locations);
    }

    #[Test]
    public function readsTheResourceStrategyByDefault(): void
    {
        static::assertSame(['/default'], $this->locations([ResourceStrategy::class => self::strategy('default')]));
    }

    #[Test]
    public function rejectsANavigationServiceThatIsNotAStrategy(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Service "'
                . ResourceStrategy::class
                . '" must be a '
                . ResourceStrategyInterface::class
                . ', stdClass given',
        );

        $this->locations([ResourceStrategy::class => new stdClass()]);
    }

    #[Test]
    public function rejectsARouterOfTheWrongType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Service "' . RouterInterface::class . '" must be a ' . RouterInterface::class);

        (new WorkflowNavigationSourceFactory())(new InMemoryContainer([
            ResourceStrategy::class => self::strategy('default'),
            RouterInterface::class  => new stdClass(),
        ]));
    }

    /**
     * @param array<string, mixed> $services
     *
     * @return list<string>
     */
    private function locations(array $services): array
    {
        $router = $this->createStub(RouterInterface::class);
        $router->method('generateUri')->willReturnCallback(static fn(string $name): string => "/{$name}");

        $source = (new WorkflowNavigationSourceFactory())(new InMemoryContainer([
            RouterInterface::class => $router,
            ...$services,
        ]));

        return array_map(static fn(SitemapEntry $entry): string => $entry->location, $source->getEntries());
    }
}
