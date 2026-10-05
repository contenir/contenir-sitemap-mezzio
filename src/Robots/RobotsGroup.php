<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Robots;

use InvalidArgumentException;

use function array_diff;
use function array_keys;
use function implode;
use function is_array;
use function is_string;
use function preg_match;
use function sprintf;

/**
 * One robots.txt group: a user agent and the paths it may and may not crawl.
 *
 * The user agent and every path must be a non-empty string without line
 * breaks or other control characters, so configuration cannot add lines of
 * its own to the file.
 *
 * @api
 */
final readonly class RobotsGroup
{
    /**
     * @param list<string> $allow
     * @param list<string> $disallow
     *
     * @throws InvalidArgumentException When the user agent or a path is empty or holds a control character.
     */
    public function __construct(
        public string $userAgent,
        public array $allow = [],
        public array $disallow = [],
    ) {
        self::check($userAgent, 'user agent');
        foreach ([...$allow, ...$disallow] as $path) {
            self::check($path, sprintf('path for "%s"', $userAgent));
        }
    }

    /**
     * @param mixed $group ['allow' => list of paths, 'disallow' => list of paths], both optional.
     *
     * @throws InvalidArgumentException When the user agent, the group or a path is invalid.
     */
    public static function fromConfig(string $userAgent, mixed $group): self
    {
        if (! is_array($group) || [] !== array_diff(array_keys($group), ['allow', 'disallow'])) {
            throw new InvalidArgumentException(sprintf(
                'Robots rules for "%s" must be an array with only "allow" and "disallow" lists',
                $userAgent,
            ));
        }

        return new self(
            $userAgent,
            self::paths($userAgent, $group['allow'] ?? []),
            self::paths($userAgent, $group['disallow'] ?? []),
        );
    }

    /**
     * @throws InvalidArgumentException When the value is empty or holds a control character.
     */
    private static function check(string $value, string $name): void
    {
        if ('' === $value || 1 === preg_match('/[\x00-\x1F\x7F]/', $value)) {
            throw new InvalidArgumentException(sprintf(
                'Robots %s must be a non-empty string without control characters',
                $name,
            ));
        }
    }

    /**
     * @return list<string>
     *
     * @throws InvalidArgumentException When the paths are not a list of strings.
     *
     * @mago-expect analysis:mixed-assignment Config values are untyped; each path is checked here.
     */
    private static function paths(string $userAgent, mixed $paths): array
    {
        if (! is_array($paths)) {
            throw new InvalidArgumentException(sprintf('Robots paths for "%s" must be a list', $userAgent));
        }

        $strings = [];
        foreach ($paths as $path) {
            $strings[] = is_string($path) ? $path : '';
        }

        return $strings;
    }

    /**
     * The group's lines. A group with no paths disallows nothing, which
     * robots.txt spells as an empty Disallow line.
     */
    public function render(): string
    {
        $lines = ["User-agent: {$this->userAgent}"];
        foreach ($this->allow as $path) {
            $lines[] = "Allow: {$path}";
        }

        foreach ($this->disallow as $path) {
            $lines[] = "Disallow: {$path}";
        }

        if ([] === $this->allow && [] === $this->disallow) {
            $lines[] = 'Disallow:';
        }

        return implode("\n", $lines);
    }
}
