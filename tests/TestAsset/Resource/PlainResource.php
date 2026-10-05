<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Tests\TestAsset\Resource;

use Contenir\Workflow\ResourceInterface;
use Override;

/**
 * A page resource without metadata. PageWorkflow routes it to its
 * getMiddleware(); a null middleware makes it a folder without a route.
 */
final readonly class PlainResource implements ResourceInterface
{
    /**
     * @param list<ResourceInterface> $children
     */
    public function __construct(
        private int $id,
        private string $slug,
        private ?string $middleware = 'page.handler',
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

    public function getMiddleware(): ?string
    {
        return $this->middleware;
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
