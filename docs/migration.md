# Coming from contenir-sitemap-laminas-mvc

contenir-sitemap-laminas-mvc renders the `cms` laminas-navigation container
through laminas-view's sitemap helper. Mezzio sites have no view helper
layer by default, and contenir-workflow-mezzio builds navigation as plain
arrays rather than a laminas-navigation container, so this adapter writes
the XML itself from a `SitemapSourceInterface`. The output is the same
sitemap protocol, and the robots.txt is the same file by default.

## Feature map

| laminas-mvc | Mezzio |
| --- | --- |
| `Contenir\Mvc\Sitemap\Module` | `Contenir\Sitemap\Mezzio\ConfigProvider` |
| Route `sitemap`, `Literal` `/sitemap.xml` | Route `sitemap`, `GET /sitemap.xml`, path in `sitemap.routes.sitemap` |
| Route `robots`, `Literal` `/robots.txt` | Route `robots`, `GET /robots.txt`, path in `sitemap.routes.robots` |
| Overriding the routes in `router.routes` | `sitemap.routes.*`, or `false` and your own route |
| `SitemapController::indexAction()` | `Handler\SitemapHandler` |
| `SitemapController::robotsAction()` | `Handler\RobotsHandler` |
| `Factory\SitemapControllerFactory` | `Factory\SitemapHandlerFactory`, `Factory\RobotsHandlerFactory` |
| `SitemapController::CONTAINER` (`cms`), not configurable | `sitemap.navigation`, defaulting to `workflow_manager.strategy` |
| A laminas-navigation container as the source | `SitemapSourceInterface`; `Source\WorkflowNavigationSource` by default |
| laminas-view `Navigation\Sitemap` helper | `SitemapHandler` (XMLWriter); no laminas-view needed |
| Helper skips invisible pages and their children | Same |
| Helper drops repeated URLs | Same |
| Helper omits invalid `lastmod`/`changefreq`/`priority` | Same (`WorkflowNavigationSource`) |
| `lastmod` via `date('c')` | ATOM, the same format |
| `serverUrl()` helper (request host) | Request scheme, host and port, or fixed `sitemap.base_url` |
| Workflow strategy `lastmod` from `MetadataInterface::getMetaModified()` | `Strategy\MetadataResourceStrategy` |
| robots.txt rules fixed in the controller | `sitemap.robots.rules`, same rules by default |
| robots.txt comment `{origin}{home route}` | `# robots.txt for {base URL}/`; no `home` route required |
| `Content-Type: application/xml; charset=utf-8` | Same |
| `Content-Type: text/plain; charset=utf-8` | Same |
| `DomainException` without an HTTP request/response | Not applicable: PSR-15 handlers always receive a PSR-7 request |
| `ServiceNotCreatedException` for a wrong helper type | `InvalidArgumentException` for a service of the wrong type |

## Steps

1. Require the package and register its `ConfigProvider` (the component
   installer does it). Remove `Contenir\Mvc\Sitemap` from the module list.
2. Set the workflow strategy to `MetadataResourceStrategy` to keep
   `<lastmod>`, as in the [sitemap docs](sitemap.md#lastmod-from-page-metadata).
3. If your `cms` container was built from something other than the workflow
   tree, implement `SitemapSourceInterface` over the same data instead.
4. Move any custom routes or robots rules into the `sitemap` config.
5. Set `sitemap.base_url` to the site's public URL.
