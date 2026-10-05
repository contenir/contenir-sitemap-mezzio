<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Tests\TestAsset\Repository;

use Contenir\Workflow\Repository\ResourceAdapterInterface;
use Contenir\Workflow\ResourceInterface;
use Override;

/**
 * Serves a fixed resource tree and counts how often it was asked.
 */
final class InMemoryResourceAdapter implements ResourceAdapterInterface
{
    public int $calls = 0;

    /**
     * @param list<ResourceInterface> $resources
     */
    public function __construct(
        private readonly array $resources = [],
    ) {}

    #[Override]
    public function getWorkflowResources(): iterable
    {
        ++$this->calls;

        return $this->resources;
    }
}
