# contenir/contenir-sitemap-mezzio

[![Continuous Integration](https://github.com/contenir/contenir-sitemap-mezzio/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/contenir/contenir-sitemap-mezzio/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/contenir/contenir-sitemap-mezzio/graph/badge.svg)](https://codecov.io/gh/contenir/contenir-sitemap-mezzio)

Mezzio adapter for [Contenir CMS](https://contenir.com.au) sitemaps: PSR-15
handlers that serve `/sitemap.xml` and `/robots.txt`, for Mezzio sites that
do not run the full CMS. It is the Mezzio counterpart of
[contenir/contenir-sitemap-laminas-mvc](https://github.com/contenir/contenir-sitemap-laminas-mvc).

The sitemap is built from a `SitemapSourceInterface`. The default source
reads the navigation of
[contenir/contenir-workflow-mezzio](https://github.com/contenir/contenir-workflow-mezzio),
the same resource tree that generates the site's routes, and asks the Mezzio
router for each page's URL. With the bundled `MetadataResourceStrategy`,
each page's `<lastmod>` comes from its resource's
[contenir-metadata](https://github.com/contenir/contenir-metadata)
`MetadataInterface::getMetaModified()`. Any other source is one small class
away.

## Requirements

- PHP 8.3, 8.4 or 8.5, with the `xmlwriter` extension
- mezzio/mezzio 3.18+ and mezzio/mezzio-router 3.17+ with a router
  (for example mezzio/mezzio-fastroute)
- A PSR-17 `ResponseFactoryInterface` and `StreamFactoryInterface` in the
  container (laminas-diactoros' ConfigProvider registers both)
- contenir/contenir-workflow-mezzio 2.1+ and contenir/contenir-metadata 2.0+
  (installed as dependencies; the workflow navigation is only needed if you
  keep the default source)

## Installation

```bash
composer require contenir/contenir-sitemap-mezzio
```

With [laminas-component-installer](https://docs.laminas.dev/laminas-component-installer/)
the `Contenir\Sitemap\Mezzio\ConfigProvider` is added to
`config/config.php` for you. Otherwise add it yourself:

```php
$aggregator = new ConfigAggregator([
    // ...
    \Contenir\Workflow\ConfigProvider::class,
    \Contenir\Sitemap\Mezzio\ConfigProvider::class,
    // ...
]);
```

The provider registers the handlers, the default sitemap source and a
delegator on `Mezzio\Application` that routes `GET /sitemap.xml` (route
`sitemap`) and `GET /robots.txt` (route `robots`). There is nothing to add
to `config/routes.php`.

## Usage

### With contenir-workflow-mezzio

Point the workflow manager at the `MetadataResourceStrategy` so the sitemap
carries each page's modified date. Everything else is the workflow
configuration you already have:

```php
// config/autoload/workflow.global.php
use App\Repository\PageRepositoryAdapter;
use Contenir\Sitemap\Mezzio\Strategy\MetadataResourceStrategy;
use Contenir\Workflow\Factory\WorkflowApplicationDelegatorFactory;
use Mezzio\Application;

return [
    'workflow_manager' => [
        'strategy'   => MetadataResourceStrategy::class,
        'repository' => PageRepositoryAdapter::class,
        'cache'      => 'FilesystemCache',
    ],
    'dependencies' => [
        'delegators' => [
            Application::class => [WorkflowApplicationDelegatorFactory::class],
        ],
    ],
];
```

Your page entities implement both `Contenir\Workflow\ResourceInterface` and
`Contenir\Metadata\MetadataInterface`. The sitemap lists every visible page
that has a route, nested pages included, in navigation order:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://www.example.com/about</loc>
    <lastmod>2026-03-04T05:06:07+10:00</lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.6</priority>
  </url>
</urlset>
```

With workflow-mezzio's own `ResourceStrategy` the sitemap works the same,
without `<lastmod>`.

### With your own source

Implement `SitemapSourceInterface` and alias it:

```php
use Contenir\Sitemap\Mezzio\ChangeFrequency;
use Contenir\Sitemap\Mezzio\SitemapEntry;
use Contenir\Sitemap\Mezzio\SitemapSourceInterface;

final readonly class ProductSitemapSource implements SitemapSourceInterface
{
    public function __construct(private ProductRepository $products) {}

    public function getEntries(): iterable
    {
        foreach ($this->products->findPublished() as $product) {
            yield new SitemapEntry(
                "/products/{$product->slug}",           // a path, or an absolute http(s) URL
                $product->updatedAt,                    // ?DateTimeInterface
                ChangeFrequency::Weekly,                // ?ChangeFrequency
                0.8,                                    // ?float, 0.0 to 1.0
            );
        }
    }
}

// config/autoload/sitemap.global.php
return [
    'dependencies' => [
        'aliases'   => [SitemapSourceInterface::class => ProductSitemapSource::class],
        'factories' => [ProductSitemapSource::class => ProductSitemapSourceFactory::class],
    ],
];
```

### robots.txt

By default `/robots.txt` reproduces the laminas-mvc module's file:

```text
# robots.txt for https://www.example.com/

User-agent: *
Allow: /
Disallow: /.well-known/

Sitemap: https://www.example.com/sitemap.xml
```

The rules are configurable; see below.

## Configuration

Everything is optional and lives under the `sitemap` key:

```php
// config/autoload/sitemap.global.php
return [
    'sitemap' => [
        // Fixed scheme and host (and optional base path) for every URL.
        // null (the default) takes them from the request.
        'base_url'   => 'https://www.example.com',

        // The ResourceStrategyInterface service the default source reads.
        // Defaults to workflow_manager.strategy, then ResourceStrategy.
        'navigation' => null,

        // Route paths; false leaves the route out.
        'routes'     => [
            'sitemap' => '/sitemap.xml',
            'robots'  => '/robots.txt',
        ],

        // robots.txt groups, keyed by user agent. Replaces the default rules.
        'robots'     => [
            'rules' => [
                '*'         => ['allow' => ['/'], 'disallow' => ['/.well-known/', '/admin/']],
                'Googlebot' => ['disallow' => ['/search/']],
                'GPTBot'    => ['disallow' => ['/']],
            ],
        ],
    ],
];
```

Invalid values fail when the service is built, with an
`InvalidArgumentException` naming the key. The
[configuration reference](docs/configuration.md) lists every key.

## Security

- **Set `sitemap.base_url` in production.** Without it, the URLs in both
  files are built from the request's scheme and Host header. A forged Host
  then lands in the response, and in any cache in front of it. With it, the
  response never depends on the request.
- **The base URL is validated:** an absolute http(s) URL without
  credentials, query, fragment or whitespace.
- **robots.txt values cannot add lines.** User agents and paths that are
  empty or contain a line break or other control character are rejected
  when the handler is built, as are unknown directives (a typo such as
  `dissallow` fails instead of silently allowing everything).
- **Sitemap locations are checked.** A `SitemapEntry` takes only a path
  starting with `/` or an absolute `http`/`https` URL, so a source cannot
  emit `javascript:` or protocol-relative locations. URLs are XML-escaped.

## Public API

| Class | Purpose |
| --- | --- |
| `Handler\SitemapHandler` | PSR-15 handler: `application/xml; charset=utf-8` sitemap from the `SitemapSourceInterface` |
| `Handler\RobotsHandler` | PSR-15 handler: `text/plain; charset=utf-8` robots.txt from the rules, with the Sitemap line |
| `SitemapSourceInterface` | Supplies the sitemap entries; aliased to `WorkflowNavigationSource` |
| `SitemapEntry` | One `<url>`: location, last modified, change frequency, priority |
| `ChangeFrequency` | The protocol's `<changefreq>` values |
| `Source\WorkflowNavigationSource` | Entries from a contenir-workflow-mezzio navigation, URLs from the Mezzio router |
| `Strategy\MetadataResourceStrategy` | workflow-mezzio's default strategy, with `lastmod` from `MetadataInterface::getMetaModified()` |
| `Robots\RobotsRules`, `Robots\RobotsGroup` | The robots.txt groups, validated |
| `Http\BaseUrl` | The configured or request base URL |
| `ConfigProvider`, `Factory\*` | Container wiring, including `Factory\SitemapRoutesDelegatorFactory` |

Every concrete class is `final`; `SitemapSourceInterface` is the extension
point, and workflow-mezzio's `AbstractResourceStrategy` for strategies.

The [docs](docs/) folder covers each area:

- [Configuration](docs/configuration.md)
- [Sitemap and sources](docs/sitemap.md)
- [robots.txt](docs/robots.md)
- [Coming from contenir-sitemap-laminas-mvc](docs/migration.md)

## Coming from contenir-sitemap-laminas-mvc

| laminas-mvc | Mezzio |
| --- | --- |
| `Module`, routes `sitemap` and `robots` in `router.routes` | `ConfigProvider`; the same route names, registered by `SitemapRoutesDelegatorFactory`. Paths in `sitemap.routes` |
| `SitemapController::indexAction()` | `Handler\SitemapHandler` |
| `SitemapController::robotsAction()` | `Handler\RobotsHandler` |
| `SitemapControllerFactory` | `Factory\SitemapHandlerFactory`, `Factory\RobotsHandlerFactory` |
| Navigation container service `cms` (`SitemapController::CONTAINER`), fixed | `sitemap.navigation`: any `ResourceStrategyInterface` service, by default `workflow_manager.strategy`; or your own `SitemapSourceInterface` |
| laminas-view `navigation()->sitemap()` helper | `SitemapHandler` writes the XML itself; no view layer needed |
| `serverUrl()` helper for absolute URLs | The request URI, or the fixed `sitemap.base_url` |
| `lastmod` from the workflow strategy (`getMetaModified()`) | `Strategy\MetadataResourceStrategy` |
| Fixed robots.txt rules | `sitemap.robots.rules` (the same rules by default) |
| robots.txt comment from the `home` route | `# robots.txt for <base URL>/`; no `home` route needed |
| `Content-Type` `application/xml` / `text/plain` | Unchanged, with `charset=utf-8` |

See [docs/migration.md](docs/migration.md) for the step-by-step move.

## Development

The QA toolchain is [contenir/contenir-qa-tools](https://github.com/contenir/contenir-qa-tools).
[Mago](https://mago.carthage.software/) is a standalone binary, installed
separately (`brew install mago`).

```bash
composer check             # everything below
composer cs-check          # mago format --check && mago lint
composer static-analysis   # mago analyze
composer test              # unit suite: collaborators doubled, no I/O
composer test-integration  # integration suite: real ServiceManager, Mezzio pipeline and FastRoute router
composer test-coverage     # both suites, clover.xml for Codecov
composer mutation-test     # Infection mutation testing over both suites (needs Xdebug or PCOV)
```

## License

BSD-3-Clause. See [LICENSE.md](LICENSE.md).
