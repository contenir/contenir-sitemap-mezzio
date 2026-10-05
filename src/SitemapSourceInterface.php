<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio;

/**
 * Supplies the sitemap's URLs.
 *
 * The SitemapHandler depends on this interface, resolved from the container
 * by its class name. The ConfigProvider aliases it to the
 * WorkflowNavigationSource; point the alias at your own implementation to
 * build the sitemap from anything else.
 *
 * @api
 */
interface SitemapSourceInterface
{
    /**
     * The entries, in sitemap order. Entries whose absolute URL repeats an
     * earlier one are left out.
     *
     * @return iterable<SitemapEntry>
     */
    public function getEntries(): iterable;
}
