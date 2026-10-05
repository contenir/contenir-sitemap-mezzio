<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Tests\Trait;

use Contenir\Sitemap\Mezzio\ConfigProvider;
use Contenir\Sitemap\Mezzio\Strategy\MetadataResourceStrategy;
use Contenir\Sitemap\Mezzio\Tests\TestAsset\Repository\InMemoryResourceAdapter;
use Contenir\Workflow\ConfigProvider as WorkflowConfigProvider;
use Contenir\Workflow\Factory\WorkflowApplicationDelegatorFactory;
use Contenir\Workflow\ResourceInterface;
use Laminas\Diactoros\ConfigProvider as DiactorosConfigProvider;
use Laminas\Diactoros\ServerRequest;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\ArrayUtils;
use Mezzio\Application;
use Mezzio\ConfigProvider as MezzioConfigProvider;
use Mezzio\Handler\NotFoundHandler;
use Mezzio\Router\ConfigProvider as RouterConfigProvider;
use Mezzio\Router\FastRouteRouter\ConfigProvider as FastRouteConfigProvider;
use Mezzio\Router\Middleware\DispatchMiddleware;
use Mezzio\Router\Middleware\ImplicitHeadMiddleware;
use Mezzio\Router\Middleware\MethodNotAllowedMiddleware;
use Mezzio\Router\Middleware\RouteMiddleware;
use Psr\Http\Message\ResponseInterface;

/**
 * A Mezzio application wired from the ConfigProviders of Mezzio, its
 * FastRoute router, laminas-diactoros, contenir-workflow-mezzio and this
 * package, with the workflow routes registered by the workflow delegator
 * and the sitemap routes by this package's delegator. Pipe it once with
 * createSitemapApplication(), then send it requests with request().
 */
trait SitemapApplicationTrait
{
    use InMemoryCacheTrait;

    private ServiceManager $container;

    /**
     * @param list<ResourceInterface> $resources
     * @param array<string, mixed> $sitemap The "sitemap" config.
     * @param array<string, mixed> $workflowManager Overrides of the "workflow_manager" config.
     */
    private function createSitemapApplication(
        array $resources,
        array $sitemap = [],
        array $workflowManager = [],
    ): void {
        $dependencies = [];
        foreach ([
            new MezzioConfigProvider(),
            new RouterConfigProvider(),
            new FastRouteConfigProvider(),
            new DiactorosConfigProvider(),
            new WorkflowConfigProvider(),
            new ConfigProvider(),
        ] as $provider) {
            $dependencies = ArrayUtils::merge($dependencies, $provider()['dependencies'] ?? []);
        }

        $dependencies['delegators'][Application::class][] = WorkflowApplicationDelegatorFactory::class;

        $this->container = new ServiceManager($dependencies);
        $this->container->setService('config', [
            'sitemap'          => $sitemap,
            'workflow_manager' => [
                'strategy'   => MetadataResourceStrategy::class,
                'repository' => InMemoryResourceAdapter::class,
                'cache'      => 'WorkflowCache',
                ...$workflowManager,
            ],
        ]);
        $this->container->setService(InMemoryResourceAdapter::class, new InMemoryResourceAdapter($resources));
        $this->container->setService('WorkflowCache', $this->createInMemoryCache());
    }

    private function request(string $path, string $method = 'GET'): ResponseInterface
    {
        $app = $this->container->get(Application::class);
        $app->pipe(RouteMiddleware::class);
        $app->pipe(ImplicitHeadMiddleware::class);
        $app->pipe(MethodNotAllowedMiddleware::class);
        $app->pipe(DispatchMiddleware::class);
        $app->pipe(NotFoundHandler::class);

        return $app->handle(new ServerRequest(
            uri: "https://www.example.com{$path}",
            method: $method,
        ));
    }
}
