# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project
adheres to [Semantic Versioning](https://semver.org/).

## [2.0.0] - 2026-10-06

First release: the Mezzio counterpart of
`contenir/contenir-sitemap-laminas-mvc` 2.1, versioned with the Contenir
2.x packages.

### Added

- `Handler\SitemapHandler`: PSR-15 handler serving the sitemap XML from a
  `SitemapSourceInterface`, with paths made absolute against the base URL
  and repeated URLs left out.
- `Handler\RobotsHandler`: PSR-15 handler serving robots.txt from
  configurable rules, with a Sitemap line pointing at the sitemap route.
- `SitemapSourceInterface`, `SitemapEntry` and `ChangeFrequency`.
- `Source\WorkflowNavigationSource`: entries from a contenir-workflow-mezzio
  navigation, URLs from the Mezzio router.
- `Strategy\MetadataResourceStrategy`: workflow-mezzio's default strategy
  with each page's `lastmod` from `MetadataInterface::getMetaModified()`.
- `Robots\RobotsRules`, `Robots\RobotsGroup` and `Http\BaseUrl`.
- `ConfigProvider` and factories, with `Factory\SitemapRoutesDelegatorFactory`
  registering the `sitemap` and `robots` routes.
- Configuration: `sitemap.base_url`, `sitemap.navigation`,
  `sitemap.routes.sitemap`, `sitemap.routes.robots` and
  `sitemap.robots.rules`.
- Unit and integration test suites with 100% line and branch coverage, and
  Infection mutation testing at 100% MSI.
