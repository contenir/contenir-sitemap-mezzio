<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Factory;

use Contenir\Sitemap\Mezzio\Strategy\MetadataResourceStrategy;
use Contenir\Workflow\Repository\ResourceAdapterInterface;
use Contenir\Workflow\Workflow\WorkflowPluginManager;
use InvalidArgumentException;
use Laminas\Cache\Storage\StorageInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

use function is_array;
use function is_string;
use function sprintf;

/**
 * Builds the MetadataResourceStrategy from the same "workflow_manager"
 * config as contenir-workflow-mezzio's ResourceStrategyFactory: the
 * "repository" service (required), the "cache" storage service (default
 * "FilesystemCache") and "cache_key" (default "WorkflowResourceCache").
 *
 * @api
 */
final class MetadataResourceStrategyFactory
{
    public const string DEFAULT_CACHE = 'FilesystemCache';

    public const string DEFAULT_CACHE_KEY = 'WorkflowResourceCache';

    /**
     * @param array<array-key, mixed> $config
     *
     * @mago-expect analysis:mixed-assignment Config values are untyped; the type is checked here.
     */
    private static function string(array $config, string $key, ?string $default): ?string
    {
        $value = $config[$key] ?? null;

        return is_string($value) && '' !== $value ? $value : $default;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException When the repository is not configured or a service has the wrong type.
     *
     * @mago-expect analysis:mixed-assignment The config service is untyped; its shape is checked here.
     */
    public function __invoke(ContainerInterface $container): MetadataResourceStrategy
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $config = is_array($config) && is_array($config['workflow_manager'] ?? null) ? $config['workflow_manager'] : [];

        $repository = self::string($config, 'repository', null) ?? throw new InvalidArgumentException(sprintf(
            'No repository configured in workflow_manager for %s',
            MetadataResourceStrategy::class,
        ));

        return new MetadataResourceStrategy(
            Service::get($container, $repository, ResourceAdapterInterface::class),
            Service::get($container, WorkflowPluginManager::class, WorkflowPluginManager::class),
            Service::get(
                $container,
                (string) self::string($config, 'cache', self::DEFAULT_CACHE),
                StorageInterface::class,
            ),
            ['cache_key' => (string) self::string($config, 'cache_key', self::DEFAULT_CACHE_KEY)],
        );
    }
}
