<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Factory;

use Contenir\Sitemap\Mezzio\Container\SitemapConfig;
use Contenir\Sitemap\Mezzio\Source\WorkflowNavigationSource;
use Contenir\Workflow\Strategy\ResourceStrategyInterface;
use InvalidArgumentException;
use Mezzio\Router\RouterInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * Builds the WorkflowNavigationSource from the "sitemap.navigation" strategy
 * service (by default "workflow_manager.strategy", then ResourceStrategy)
 * and the Mezzio router.
 *
 * @api
 */
final class WorkflowNavigationSourceFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException When a service has the wrong type.
     */
    public function __invoke(ContainerInterface $container): WorkflowNavigationSource
    {
        return new WorkflowNavigationSource(
            Service::get(
                $container,
                SitemapConfig::fromContainer($container)->navigationService(),
                ResourceStrategyInterface::class,
            ),
            Service::get($container, RouterInterface::class, RouterInterface::class),
        );
    }
}
