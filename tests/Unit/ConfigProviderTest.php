<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Tests\Unit;

use Contenir\Sitemap\Mezzio\ConfigProvider;
use Contenir\Sitemap\Mezzio\Factory\MetadataResourceStrategyFactory;
use Contenir\Sitemap\Mezzio\Factory\RobotsHandlerFactory;
use Contenir\Sitemap\Mezzio\Factory\SitemapHandlerFactory;
use Contenir\Sitemap\Mezzio\Factory\SitemapRoutesDelegatorFactory;
use Contenir\Sitemap\Mezzio\Factory\WorkflowNavigationSourceFactory;
use Contenir\Sitemap\Mezzio\Handler\RobotsHandler;
use Contenir\Sitemap\Mezzio\Handler\SitemapHandler;
use Contenir\Sitemap\Mezzio\SitemapSourceInterface;
use Contenir\Sitemap\Mezzio\Source\WorkflowNavigationSource;
use Contenir\Sitemap\Mezzio\Strategy\MetadataResourceStrategy;
use Mezzio\Application;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ConfigProviderTest extends TestCase
{
    #[Test]
    public function aliasesTheSitemapSourceToTheWorkflowNavigation(): void
    {
        static::assertSame(
            [SitemapSourceInterface::class => WorkflowNavigationSource::class],
            (new ConfigProvider())->getDependencies()['aliases'],
        );
    }

    #[Test]
    public function contributesOnlyDependencies(): void
    {
        $provider = new ConfigProvider();

        static::assertSame(['dependencies' => $provider->getDependencies()], $provider());
    }

    #[Test]
    public function registersAFactoryForEveryService(): void
    {
        static::assertSame(
            [
                RobotsHandler::class            => RobotsHandlerFactory::class,
                SitemapHandler::class           => SitemapHandlerFactory::class,
                WorkflowNavigationSource::class => WorkflowNavigationSourceFactory::class,
                MetadataResourceStrategy::class => MetadataResourceStrategyFactory::class,
            ],
            (new ConfigProvider())->getDependencies()['factories'],
        );
    }

    #[Test]
    public function registersTheRoutesDelegatorOnTheApplication(): void
    {
        static::assertSame(
            [Application::class => [SitemapRoutesDelegatorFactory::class]],
            (new ConfigProvider())->getDependencies()['delegators'],
        );
    }
}
