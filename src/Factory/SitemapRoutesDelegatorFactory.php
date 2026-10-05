<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Factory;

use Contenir\Sitemap\Mezzio\Container\SitemapConfig;
use Contenir\Sitemap\Mezzio\Handler\RobotsHandler;
use Contenir\Sitemap\Mezzio\Handler\SitemapHandler;
use InvalidArgumentException;
use Mezzio\Application;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * Delegator factory for Mezzio\Application that registers the GET routes
 * "sitemap" (/sitemap.xml) and "robots" (/robots.txt), at the paths in
 * "sitemap.routes". A path of false leaves that route out.
 *
 * @api
 */
final class SitemapRoutesDelegatorFactory
{
    /**
     * @param callable(): Application $callback
     *
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException When a route path is invalid.
     *
     * @mago-expect analysis:unused-parameter The delegator signature passes the service name.
     */
    public function __invoke(ContainerInterface $container, string $name, callable $callback): Application
    {
        $app    = $callback();
        $config = SitemapConfig::fromContainer($container);

        $sitemap = $config->sitemapPath();
        if (null !== $sitemap) {
            $app->get($sitemap, SitemapHandler::class, SitemapHandler::ROUTE);
        }

        $robots = $config->robotsPath();
        if (null !== $robots) {
            $app->get($robots, RobotsHandler::class, RobotsHandler::ROUTE);
        }

        return $app;
    }
}
