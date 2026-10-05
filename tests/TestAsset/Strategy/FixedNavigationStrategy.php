<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Tests\TestAsset\Strategy;

use Contenir\Workflow\Strategy\ResourceStrategyInterface;
use Contenir\Workflow\Workflow\WorkflowInterface;
use Override;

/**
 * A strategy with fixed navigation pages and routes, the routes given by
 * name and path.
 *
 * @psalm-import-type RouteConfig from WorkflowInterface
 */
final readonly class FixedNavigationStrategy implements ResourceStrategyInterface
{
    /**
     * @param list<array<string, mixed>> $navigation
     * @param array<non-empty-string, non-empty-string> $routes Route name => path.
     */
    public function __construct(
        private array $navigation,
        private array $routes,
    ) {}

    #[Override]
    public function clearCache(): void {}

    #[Override]
    public function getNavigationConfig(): array
    {
        return $this->navigation;
    }

    /**
     * @return array<string, RouteConfig>
     */
    #[Override]
    public function getRouteConfig(): array
    {
        $config = [];
        foreach ($this->routes as $name => $path) {
            $config[$name] = ['path' => $path, 'middleware' => 'page.handler', 'methods' => ['GET'], 'name' => $name];
        }

        return $config;
    }
}
