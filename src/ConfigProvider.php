<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio;

use Mezzio\Application;

/**
 * Registers the sitemap and robots.txt handlers, the sitemap sources, and
 * the delegator that routes /sitemap.xml and /robots.txt.
 *
 * Contributes no "sitemap" key: the defaults live in the factories, so a
 * site's robots rules replace the default rules rather than being merged
 * into them.
 *
 * @api
 */
final class ConfigProvider
{
    /**
     * @return array{
     *     aliases: array<class-string, class-string>,
     *     delegators: array<class-string, list<class-string>>,
     *     factories: array<class-string, class-string>,
     * }
     */
    public function getDependencies(): array
    {
        return [
            'aliases'    => [
                SitemapSourceInterface::class => Source\WorkflowNavigationSource::class,
            ],
            'delegators' => [
                Application::class => [Factory\SitemapRoutesDelegatorFactory::class],
            ],
            'factories'  => [
                Handler\RobotsHandler::class             => Factory\RobotsHandlerFactory::class,
                Handler\SitemapHandler::class            => Factory\SitemapHandlerFactory::class,
                Source\WorkflowNavigationSource::class   => Factory\WorkflowNavigationSourceFactory::class,
                Strategy\MetadataResourceStrategy::class => Factory\MetadataResourceStrategyFactory::class,
            ],
        ];
    }

    /**
     * @return array{dependencies: array{
     *     aliases: array<class-string, class-string>,
     *     delegators: array<class-string, list<class-string>>,
     *     factories: array<class-string, class-string>,
     * }}
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }
}
