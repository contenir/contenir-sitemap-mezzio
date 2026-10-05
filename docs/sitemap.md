# Sitemap and sources

`Handler\SitemapHandler` answers with `200` and
`Content-Type: application/xml; charset=utf-8`, and writes one `<url>` per
`SitemapEntry` of the `SitemapSourceInterface` service:

- `<loc>`: the entry's location. A path (`/about`) is prefixed with the
  [base URL](configuration.md#base_url); an absolute URL is used as is.
  A URL that repeats an earlier one is left out.
- `<lastmod>`: the entry's date in W3C (ATOM) format, such as
  `2026-03-04T05:06:07+10:00`, when it has one.
- `<changefreq>` and `<priority>`: when the entry has them.

## `SitemapEntry`

```php
new SitemapEntry(
    location: '/about',                                // required
    lastModified: new DateTimeImmutable('2026-03-04'), // ?DateTimeInterface
    changeFrequency: ChangeFrequency::Monthly,         // ?ChangeFrequency
    priority: 0.6,                                     // ?float
);
```

The location must be a path starting with `/` (not `//`) or an absolute
`http`/`https` URL with a host, and the priority between 0.0 and 1.0.
Anything else throws an `InvalidArgumentException`.

## The workflow navigation source

`Source\WorkflowNavigationSource`, the default, reads a contenir-workflow-mezzio
`ResourceStrategyInterface` (see [configuration](configuration.md#navigation)):

- One entry per navigation page whose `route` is in the strategy's route
  configuration, in navigation order, children after their parent. Its URL
  comes from the Mezzio router's `generateUri()`, so it is the route the
  page is actually served at.
- A page with `visible: false` hides its whole branch, as laminas-view's
  sitemap helper does.
- A page without a route of its own (a folder resource without middleware)
  is skipped; its children are not.
- `lastmod` may be a `DateTimeInterface` or a string PHP can parse; the
  `changefreq` must be one of the protocol's values; the `priority` a
  number from 0 to 1. Values outside that are left out of the entry rather
  than failing the sitemap, again as laminas-view does.
- A route that needs parameters to generate its URL throws the router's
  exception: such a route cannot be listed as a single URL.

The navigation is the strategy's cached navigation, so the sitemap costs no
database queries once the cache is warm, and it changes when you clear the
workflow cache (`ResourceStrategyInterface::clearCache()`).

## `<lastmod>` from page metadata

contenir-workflow-mezzio's own `ResourceStrategy` leaves `lastmod` empty.
`Strategy\MetadataResourceStrategy` is the same strategy with one change,
the one the laminas-mvc workflow strategy makes: each navigation page's
`lastmod` is its resource's `Contenir\Metadata\MetadataInterface::getMetaModified()`.
Resources without metadata, or without a modified date, keep the lastmod
their workflow gives.

Its factory reads the same `workflow_manager` keys as workflow-mezzio's
`ResourceStrategyFactory`: `repository` (required), `cache` (default
`FilesystemCache`) and `cache_key` (default `WorkflowResourceCache`).
Make it the workflow strategy, so routes and sitemap share one cached tree:

```php
'workflow_manager' => [
    'strategy'   => \Contenir\Sitemap\Mezzio\Strategy\MetadataResourceStrategy::class,
    'repository' => \App\Repository\PageRepositoryAdapter::class,
],
```

If you already extend `AbstractResourceStrategy` (to choose a workflow per
resource, say), add the same `getNavigationPage()` override to your class:

```php
protected function getNavigationPage(WorkflowInterface $workflow): array
{
    $page     = parent::getNavigationPage($workflow);
    $resource = $workflow->getResource();
    $modified = $resource instanceof MetadataInterface ? $resource->getMetaModified() : null;
    if (null !== $modified) {
        $page['lastmod'] = $modified->format(DATE_ATOM);
    }

    return $page;
}
```

## Your own source

Implement `SitemapSourceInterface::getEntries()`, returning any iterable of
`SitemapEntry` (an array or a generator), and alias the interface to your
service. To combine sources, write one that yields from each:

```php
final readonly class CombinedSitemapSource implements SitemapSourceInterface
{
    public function __construct(
        private WorkflowNavigationSource $pages,
        private ProductSitemapSource $products,
    ) {}

    public function getEntries(): iterable
    {
        yield from $this->pages->getEntries();
        yield from $this->products->getEntries();
    }
}
```

The handler ignores the iterable's keys, so `yield from` with overlapping
keys is fine.

## Not included

- Sitemap index files and splitting beyond 50,000 URLs. A site that large
  should serve an index from its own handler.
- Image, video and news sitemap extensions.
- HTTP caching headers. Put Mezzio's or your own caching middleware in
  front of the route if you need them.
