# Configuration

All settings live under the `sitemap` key of the application config, and
all of them are optional. The `ConfigProvider` contributes no `sitemap`
key: the defaults are applied when the services are built, so your
`sitemap.robots.rules` replace the default rules instead of being merged
into them.

| Key | Default | Used by |
| --- | --- | --- |
| `base_url` | `null` (from the request) | `SitemapHandlerFactory`, `RobotsHandlerFactory` |
| `navigation` | `workflow_manager.strategy`, then `Contenir\Workflow\Strategy\ResourceStrategy` | `WorkflowNavigationSourceFactory` |
| `routes.sitemap` | `/sitemap.xml` | `SitemapRoutesDelegatorFactory`, `RobotsHandlerFactory` (Sitemap line) |
| `routes.robots` | `/robots.txt` | `SitemapRoutesDelegatorFactory` |
| `robots.rules` | `RobotsRules::DEFAULT_RULES` | `RobotsHandlerFactory` |

## `base_url`

An absolute `http` or `https` URL, with an optional base path:
`https://www.example.com`, `https://www.example.com/site`. A trailing slash
is dropped. Credentials, a query, a fragment or whitespace are rejected:

```
InvalidArgumentException: Base URL "https://www.example.com/?a=b" must be an absolute http(s) URL without credentials, query, fragment or whitespace
```

When it is `null`, each request supplies the scheme, host and non-standard
port of its URI. A request without a host then fails with a
`RuntimeException` asking for `sitemap.base_url`. Behind a reverse proxy,
either set `base_url` or configure laminas-diactoros' trusted proxies so
the request URI carries the public scheme and host.

## `navigation`

The container service the default `WorkflowNavigationSource` reads; it
must be a `Contenir\Workflow\Strategy\ResourceStrategyInterface`. Most
sites leave it unset, so the sitemap reads the same strategy that
registers the routes (`workflow_manager.strategy`).

## `routes`

The path of each route, or `false` to leave the route out (for example when
the web server serves a static robots.txt). Paths must start with `/`:

```
InvalidArgumentException: sitemap.routes.sitemap must be a path starting with "/", or false to disable the route
```

The routes are named `sitemap` (`SitemapHandler::ROUTE`) and `robots`
(`RobotsHandler::ROUTE`) and accept `GET` (and `HEAD`, through Mezzio's
`ImplicitHeadMiddleware`). With the sitemap route disabled, robots.txt has
no Sitemap line.

To route the handlers yourself instead, disable both and add them in
`config/routes.php`:

```php
$app->get('/sitemap.xml', \Contenir\Sitemap\Mezzio\Handler\SitemapHandler::class, 'sitemap');
```

## `robots.rules`

See [robots.txt](robots.md).

## Services

| Service | Factory or alias |
| --- | --- |
| `Handler\SitemapHandler` | `Factory\SitemapHandlerFactory` |
| `Handler\RobotsHandler` | `Factory\RobotsHandlerFactory` |
| `SitemapSourceInterface` | alias of `Source\WorkflowNavigationSource` |
| `Source\WorkflowNavigationSource` | `Factory\WorkflowNavigationSourceFactory` |
| `Strategy\MetadataResourceStrategy` | `Factory\MetadataResourceStrategyFactory` |
| `Mezzio\Application` | delegator `Factory\SitemapRoutesDelegatorFactory` |

The handlers also need `Psr\Http\Message\ResponseFactoryInterface` and
`StreamFactoryInterface`, and the workflow source `Mezzio\Router\RouterInterface`.
A service of the wrong type fails with `Service "X" must be a Y, Z given`.

The provider holds only class names, so the merged configuration can be
cached by laminas-config-aggregator.
