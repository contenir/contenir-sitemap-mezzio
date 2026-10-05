<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Robots;

use InvalidArgumentException;

use function implode;
use function is_array;
use function is_string;

/**
 * The robots.txt body: a comment naming the site, one block per user agent
 * group, then the sitemap URL when there is one.
 *
 * @api
 */
final readonly class RobotsRules
{
    /**
     * The laminas-mvc module's rules: everything allowed but /.well-known/.
     */
    public const array DEFAULT_RULES = [
        '*' => [
            'allow'    => ['/'],
            'disallow' => ['/.well-known/'],
        ],
    ];

    /**
     * @param list<RobotsGroup> $groups
     */
    public function __construct(
        private array $groups,
    ) {}

    /**
     * Rules from the "sitemap.robots" config: its "rules" map user agents
     * to ['allow' => paths, 'disallow' => paths], and default to DEFAULT_RULES.
     *
     * @throws InvalidArgumentException When the config, a user agent, a group or a path is invalid.
     *
     * @mago-expect analysis:mixed-assignment Config values are untyped; each group is checked here.
     */
    public static function fromConfig(mixed $robots): self
    {
        $rules = is_array($robots) ? $robots['rules'] ?? self::DEFAULT_RULES : null;
        if (! is_array($rules)) {
            throw new InvalidArgumentException('sitemap.robots.rules must be an array of user agent => rules');
        }

        $groups = [];
        foreach ($rules as $userAgent => $group) {
            $groups[] = RobotsGroup::fromConfig(is_string($userAgent) ? $userAgent : '', $group);
        }

        return new self($groups);
    }

    /**
     * @param string $baseUrl The site's base URL, without a trailing slash.
     * @param ?string $sitemapUrl The sitemap's absolute URL, or null to leave the Sitemap line out.
     */
    public function render(string $baseUrl, ?string $sitemapUrl): string
    {
        $blocks = ["# robots.txt for {$baseUrl}/"];
        foreach ($this->groups as $group) {
            $blocks[] = $group->render();
        }

        if (null !== $sitemapUrl) {
            $blocks[] = "Sitemap: {$sitemapUrl}";
        }

        return implode("\n\n", $blocks) . "\n";
    }
}
