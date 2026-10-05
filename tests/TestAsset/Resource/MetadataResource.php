<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Tests\TestAsset\Resource;

use Contenir\Metadata\MetadataInterface;
use Contenir\Workflow\ResourceInterface;
use DateTimeInterface;
use Override;

/**
 * A routed page resource with metadata, of which only the modified date is set.
 */
final readonly class MetadataResource implements ResourceInterface, MetadataInterface
{
    /**
     * @param list<ResourceInterface> $children
     */
    public function __construct(
        private int $id,
        private string $slug,
        private ?DateTimeInterface $modified,
        private array $children = [],
    ) {}

    #[Override]
    public function getChildren(): iterable
    {
        return $this->children;
    }

    #[Override]
    public function getId(): int
    {
        return $this->id;
    }

    #[Override]
    public function getMetaDescription(): ?string
    {
        return null;
    }

    #[Override]
    public function getMetaImage(): ?string
    {
        return null;
    }

    #[Override]
    public function getMetaModified(): ?DateTimeInterface
    {
        return $this->modified;
    }

    #[Override]
    public function getMetaPublish(): ?DateTimeInterface
    {
        return null;
    }

    #[Override]
    public function getMetaTitle(): ?string
    {
        return null;
    }

    public function getMiddleware(): string
    {
        return 'page.handler';
    }

    #[Override]
    public function getPrimaryKeys(): array
    {
        return ['page_id' => $this->id];
    }

    #[Override]
    public function getSlug(): string
    {
        return $this->slug;
    }

    #[Override]
    public function getType(): string
    {
        return 'page';
    }
}
