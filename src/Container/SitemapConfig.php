<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Container;

use Contenir\Sitemap\Mezzio\Http\BaseUrl;
use Contenir\Sitemap\Mezzio\Robots\RobotsRules;
use Contenir\Workflow\Strategy\ResourceStrategy;
use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

use function array_key_exists;
use function is_array;
use function is_string;
use function sprintf;
use function str_starts_with;

/**
 * Reads the "sitemap" section of the application config. The defaults live
 * here rather than in the ConfigProvider, so a site's robots rules replace
 * the default rules instead of being merged into them.
 *
 * @internal
 */
final readonly class SitemapConfig
{
    public const string DEFAULT_ROBOTS_PATH = '/robots.txt';

    public const string DEFAULT_SITEMAP_PATH = '/sitemap.xml';

    /**
     * @param array<array-key, mixed> $values The "sitemap" section.
     * @param array<array-key, mixed> $routes The "sitemap.routes" section.
     * @param mixed $navigation "sitemap.navigation", else "workflow_manager.strategy".
     */
    private function __construct(
        private array $values,
        private array $routes,
        private mixed $navigation,
    ) {}

    /**
     * @throws ContainerExceptionInterface
     *
     * @mago-expect analysis:mixed-assignment The config service is untyped; its shape is checked here.
     */
    public static function fromContainer(ContainerInterface $container): self
    {
        $config  = $container->has('config') ? $container->get('config') : [];
        $sitemap = self::section($config, 'sitemap');

        return new self(
            $sitemap,
            self::section($sitemap, 'routes'),
            $sitemap['navigation'] ?? self::section($config, 'workflow_manager')['strategy'] ?? null,
        );
    }

    /**
     * @return array<array-key, mixed>
     *
     * @mago-expect analysis:mixed-assignment Config sections are untyped; the type is checked here.
     */
    private static function section(mixed $config, string $key): array
    {
        $section = is_array($config) ? $config[$key] ?? [] : [];

        return is_array($section) ? $section : [];
    }

    /**
     * "sitemap.base_url": an absolute http(s) URL, or null (the default) to
     * take the scheme and host from the request.
     *
     * @throws InvalidArgumentException When the value is neither null nor a valid base URL.
     */
    public function baseUrl(): BaseUrl
    {
        return BaseUrl::fromConfig($this->values['base_url'] ?? null);
    }

    /**
     * "sitemap.navigation": the ResourceStrategyInterface service the sitemap
     * is built from. Defaults to "workflow_manager.strategy", then to
     * ResourceStrategy.
     *
     * @return non-empty-string
     */
    public function navigationService(): string
    {
        return is_string($this->navigation) && '' !== $this->navigation ? $this->navigation : ResourceStrategy::class;
    }

    /**
     * "sitemap.routes.robots": the robots.txt route's path, or false to not register it.
     *
     * @return ?non-empty-string The path, or null when the route is disabled.
     *
     * @throws InvalidArgumentException When the value is neither false nor a path starting with "/".
     */
    public function robotsPath(): ?string
    {
        return $this->routePath('robots', self::DEFAULT_ROBOTS_PATH);
    }

    /**
     * "sitemap.robots.rules": user agent => allow and disallow paths, replacing
     * RobotsRules::DEFAULT_RULES.
     *
     * @throws InvalidArgumentException When the rules are invalid.
     */
    public function robotsRules(): RobotsRules
    {
        return RobotsRules::fromConfig($this->values['robots'] ?? []);
    }

    /**
     * "sitemap.routes.sitemap": the sitemap route's path, or false to not register it.
     *
     * @return ?non-empty-string The path, or null when the route is disabled.
     *
     * @throws InvalidArgumentException When the value is neither false nor a path starting with "/".
     */
    public function sitemapPath(): ?string
    {
        return $this->routePath('sitemap', self::DEFAULT_SITEMAP_PATH);
    }

    /**
     * @return ?non-empty-string
     *
     * @throws InvalidArgumentException When the value is neither false nor a path starting with "/".
     *
     * @mago-expect analysis:mixed-assignment Config values are untyped; the type is checked here.
     * @mago-expect analysis:invalid-return-statement A string starting with "/" is never empty.
     */
    private function routePath(string $route, string $default): ?string
    {
        $path = array_key_exists($route, $this->routes) ? $this->routes[$route] : $default;

        if (false === $path) {
            return null;
        }

        if (! is_string($path) || ! str_starts_with($path, '/')) {
            throw new InvalidArgumentException(sprintf(
                'sitemap.routes.%s must be a path starting with "/", or false to disable the route',
                $route,
            ));
        }

        return $path;
    }
}
