<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Strategy;

use Contenir\Metadata\MetadataInterface;
use Contenir\Workflow\Strategy\AbstractResourceStrategy;
use Contenir\Workflow\Workflow\WorkflowInterface;
use Override;
use RuntimeException;

use const DATE_ATOM;

/**
 * contenir-workflow-mezzio's default strategy, with each navigation page's
 * lastmod taken from its resource's MetadataInterface::getMetaModified(),
 * as the laminas-mvc workflow strategy does. The sitemap's lastmod comes
 * from there.
 *
 * Resources without metadata, or without a modified date, keep the lastmod
 * their workflow gives. The date is stored as an ATOM string, so it caches
 * with the rest of the navigation and keeps its time zone.
 *
 * @psalm-import-type NavigationPage from AbstractResourceStrategy
 *
 * @api
 */
final class MetadataResourceStrategy extends AbstractResourceStrategy
{
    /**
     * @return NavigationPage
     *
     * @throws RuntimeException When the workflow has no resource.
     */
    #[Override]
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
}
