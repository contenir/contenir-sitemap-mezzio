<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Factory;

use Contenir\Sitemap\Mezzio\Container\SitemapConfig;
use Contenir\Sitemap\Mezzio\Handler\SitemapHandler;
use Contenir\Sitemap\Mezzio\SitemapSourceInterface;
use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Builds the SitemapHandler from the SitemapSourceInterface service, the
 * PSR-17 factories and "sitemap.base_url".
 *
 * @api
 */
final class SitemapHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException When a service has the wrong type or the base URL is invalid.
     */
    public function __invoke(ContainerInterface $container): SitemapHandler
    {
        return new SitemapHandler(
            Service::get($container, SitemapSourceInterface::class, SitemapSourceInterface::class),
            SitemapConfig::fromContainer($container)->baseUrl(),
            Service::get($container, ResponseFactoryInterface::class, ResponseFactoryInterface::class),
            Service::get($container, StreamFactoryInterface::class, StreamFactoryInterface::class),
        );
    }
}
