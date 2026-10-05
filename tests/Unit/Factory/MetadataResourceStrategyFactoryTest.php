<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Tests\Unit\Factory;

use Contenir\Sitemap\Mezzio\Factory\MetadataResourceStrategyFactory;
use Contenir\Sitemap\Mezzio\Strategy\MetadataResourceStrategy;
use Contenir\Sitemap\Mezzio\Tests\TestAsset\Container\InMemoryContainer;
use Contenir\Sitemap\Mezzio\Tests\TestAsset\Repository\InMemoryResourceAdapter;
use Contenir\Sitemap\Mezzio\Tests\TestAsset\Resource\ResourceFactory;
use Contenir\Sitemap\Mezzio\Tests\Trait\InMemoryCacheTrait;
use Contenir\Workflow\Repository\ResourceAdapterInterface;
use Contenir\Workflow\Workflow\WorkflowPluginManager;
use InvalidArgumentException;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

use function array_keys;

#[Group('unit')]
final class MetadataResourceStrategyFactoryTest extends TestCase
{
    use InMemoryCacheTrait;

    /**
     * @return array<string, array{mixed}>
     */
    public static function missingRepositoryProvider(): array
    {
        return [
            'no workflow_manager'           => [[]],
            'workflow_manager not an array' => [['workflow_manager' => 'app.pages']],
            'no repository'                 => [['workflow_manager' => []]],
            'empty repository'              => [['workflow_manager' => ['repository' => '']]],
            'repository not a string'       => [['workflow_manager' => ['repository' => 42]]],
        ];
    }

    #[Test]
    public function buildsTheStrategyWithTheConfiguredCacheAndKey(): void
    {
        $default = $this->createStub(StorageInterface::class);
        $this->strategy(['cache' => 'PageCache', 'cache_key' => 'Pages'], [
            'FilesystemCache' => $default,
            'PageCache'       => $this->createInMemoryCache(),
        ])->getNavigationConfig();

        static::assertSame(['Pages'], array_keys($this->cacheItems));
    }

    #[Test]
    public function buildsTheStrategyWithTheDefaultCacheAndKey(): void
    {
        $this->strategy()->getNavigationConfig();

        static::assertSame(['WorkflowResourceCache'], array_keys($this->cacheItems));
    }

    #[Test]
    public function readsTheResourcesFromTheRepository(): void
    {
        static::assertSame(['page-1'], array_keys($this->strategy()->getRouteConfig()));
    }

    #[Test]
    public function rejectsACacheOfTheWrongType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Service "FilesystemCache" must be a ' . StorageInterface::class);

        $this->strategy(services: ['FilesystemCache' => new stdClass()]);
    }

    #[Test]
    public function rejectsAMissingConfigService(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No repository configured in workflow_manager');

        (new MetadataResourceStrategyFactory())(new InMemoryContainer());
    }

    #[DataProvider('missingRepositoryProvider')]
    #[Test]
    public function rejectsAMissingRepository(mixed $config): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'No repository configured in workflow_manager for ' . MetadataResourceStrategy::class,
        );

        (new MetadataResourceStrategyFactory())(new InMemoryContainer(['config' => $config]));
    }

    #[Test]
    public function rejectsARepositoryOfTheWrongType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Service "app.pages" must be a ' . ResourceAdapterInterface::class);

        $this->strategy(services: ['app.pages' => new stdClass()]);
    }

    /**
     * @param array<string, mixed> $workflowManager
     * @param array<string, mixed> $services
     */
    private function strategy(array $workflowManager = [], array $services = []): MetadataResourceStrategy
    {
        return (new MetadataResourceStrategyFactory())(new InMemoryContainer([
            'config'                     => ['workflow_manager' => ['repository' => 'app.pages', ...$workflowManager]],
            'app.pages'                  => new InMemoryResourceAdapter([ResourceFactory::modifiedPage(1, 'about')]),
            WorkflowPluginManager::class => new WorkflowPluginManager(new ServiceManager()),
            'FilesystemCache'            => $this->createInMemoryCache(),
            ...$services,
        ]));
    }
}
