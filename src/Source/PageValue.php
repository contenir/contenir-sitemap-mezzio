<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Source;

use Contenir\Sitemap\Mezzio\ChangeFrequency;
use DateTimeImmutable;
use DateTimeInterface;

use function date_create_immutable;
use function is_numeric;
use function is_string;

/**
 * Reads a navigation page's sitemap values, turning a value the sitemap
 * protocol does not accept into null.
 *
 * @internal
 */
final class PageValue
{
    public static function changeFrequency(mixed $changefreq): ?ChangeFrequency
    {
        return is_string($changefreq) ? ChangeFrequency::tryFrom($changefreq) : null;
    }

    /**
     * A date, or a string PHP can parse as one, such as the "Y-m-d H:i:s" the
     * laminas-mvc workflow strategy writes or the ATOM date the
     * MetadataResourceStrategy writes.
     */
    public static function lastModified(mixed $lastmod): ?DateTimeInterface
    {
        if ($lastmod instanceof DateTimeInterface) {
            return $lastmod;
        }

        if (! is_string($lastmod) || '' === $lastmod) {
            return null;
        }

        $date = date_create_immutable($lastmod);

        return $date instanceof DateTimeImmutable ? $date : null;
    }

    /**
     * A number between 0.0 and 1.0, or a numeric string such as the "0.6" workflows give.
     */
    public static function priority(mixed $priority): ?float
    {
        if (! is_numeric($priority)) {
            return null;
        }

        $priority = (float) $priority;

        return $priority >= 0.0 && $priority <= 1.0 ? $priority : null;
    }
}
