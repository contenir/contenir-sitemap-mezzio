<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Factory;

use Contenir\Sitemap\Mezzio\Container\SitemapConfig;
use Contenir\Sitemap\Mezzio\Handler\RobotsHandler;
use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Builds the RobotsHandler from "sitemap.robots.rules", "sitemap.base_url",
 * the sitemap route's path ("sitemap.routes.sitemap") and the PSR-17
 * factories.
 *
 * @api
 */
final class RobotsHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException When a service has the wrong type or the configuration is invalid.
     */
    public function __invoke(ContainerInterface $container): RobotsHandler
    {
        $config = SitemapConfig::fromContainer($container);

        return new RobotsHandler(
            $config->robotsRules(),
            $config->baseUrl(),
            $config->sitemapPath(),
            Service::get($container, ResponseFactoryInterface::class, ResponseFactoryInterface::class),
            Service::get($container, StreamFactoryInterface::class, StreamFactoryInterface::class),
        );
    }
}
