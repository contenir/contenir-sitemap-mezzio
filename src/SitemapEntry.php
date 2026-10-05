<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio;

use DateTimeInterface;
use InvalidArgumentException;

use function in_array;
use function is_array;
use function parse_url;
use function sprintf;
use function str_starts_with;
use function strtolower;

/**
 * One <url> of the sitemap.
 *
 * The location is either a path from the site root ("/about/team"), which
 * the sitemap handler prefixes with the site's base URL, or an absolute
 * http(s) URL, used as is. Optional values that are null are left out of
 * the sitemap.
 *
 * @api
 */
final readonly class SitemapEntry
{
    /**
     * @param string $location A root-relative path or an absolute http(s) URL.
     * @param ?float $priority Between 0.0 and 1.0.
     *
     * @throws InvalidArgumentException When the location or priority is invalid.
     */
    public function __construct(
        public string $location,
        public ?DateTimeInterface $lastModified = null,
        public ?ChangeFrequency $changeFrequency = null,
        public ?float $priority = null,
    ) {
        if (! $this->isPath() && ! self::isAbsoluteUrl($location)) {
            throw new InvalidArgumentException(sprintf(
                'Sitemap location "%s" must be a path starting with "/" or an absolute http(s) URL',
                $location,
            ));
        }

        if (null !== $priority && ! ($priority >= 0.0 && $priority <= 1.0)) {
            throw new InvalidArgumentException(sprintf(
                'Sitemap priority must be between 0.0 and 1.0, %g given',
                $priority,
            ));
        }
    }

    private static function isAbsoluteUrl(string $location): bool
    {
        $parts = parse_url($location);

        return (
            is_array($parts)
                && in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], strict: true)
                && '' !== ($parts['host'] ?? '')
        );
    }

    /**
     * The absolute URL: the location itself, or the base URL followed by the path.
     *
     * @param string $baseUrl Scheme, host and optional path, without a trailing slash.
     */
    public function absoluteLocation(string $baseUrl): string
    {
        return $this->isPath() ? $baseUrl . $this->location : $this->location;
    }

    /**
     * Whether the location is a path from the site root rather than a URL.
     */
    public function isPath(): bool
    {
        return str_starts_with($this->location, '/') && ! str_starts_with($this->location, '//');
    }
}
