<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Tests\TestAsset\Resource;

use Contenir\Workflow\ResourceInterface;
use DateTimeImmutable;

/**
 * Builds resource trees for tests.
 */
final class ResourceFactory
{
    /**
     * A folder: a page without middleware, so without a route of its own.
     *
     * @param list<ResourceInterface> $children
     */
    public static function folder(int $id, string $slug, array $children = []): PlainResource
    {
        return new PlainResource($id, $slug, middleware: null, children: $children);
    }

    /**
     * A routed page with metadata, modified at the given date, if any.
     *
     * @param list<ResourceInterface> $children
     */
    public static function modifiedPage(
        int $id,
        string $slug,
        ?string $modified = '2026-03-04T05:06:07+10:00',
        array $children = [],
    ): MetadataResource {
        return new MetadataResource(
            $id,
            $slug,
            null === $modified ? null : new DateTimeImmutable($modified),
            $children,
        );
    }

    /**
     * A routed page without metadata.
     *
     * @param list<ResourceInterface> $children
     */
    public static function page(int $id, string $slug, array $children = []): PlainResource
    {
        return new PlainResource($id, $slug, children: $children);
    }
}
