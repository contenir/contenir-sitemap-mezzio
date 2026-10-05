<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Tests\TestAsset\Source;

use Contenir\Sitemap\Mezzio\SitemapEntry;
use Contenir\Sitemap\Mezzio\SitemapSourceInterface;
use Override;

/**
 * Serves a fixed list of entries.
 */
final readonly class InMemorySitemapSource implements SitemapSourceInterface
{
    /**
     * @param list<SitemapEntry> $entries
     */
    public function __construct(
        private array $entries = [],
    ) {}

    #[Override]
    public function getEntries(): iterable
    {
        return $this->entries;
    }
}
