<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Tests\Unit;

use Contenir\Sitemap\Mezzio\SitemapEntry;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use const NAN;

#[Group('unit')]
final class SitemapEntryTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function absoluteLocationProvider(): array
    {
        return [
            'root path'        => ['/', 'https://www.example.com/'],
            'nested path'      => ['/about/team', 'https://www.example.com/about/team'],
            'http URL'         => ['http://cdn.example.com/a', 'http://cdn.example.com/a'],
            'https URL'        => ['https://other.example.com/b?c=d', 'https://other.example.com/b?c=d'],
            'upper-case https' => ['HTTPS://other.example.com/', 'HTTPS://other.example.com/'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidLocationProvider(): array
    {
        return [
            'empty'             => [''],
            'relative path'     => ['about'],
            'protocol-relative' => ['//evil.example.com/'],
            'ftp URL'           => ['ftp://example.com/file'],
            'javascript URL'    => ['javascript:alert(1)'],
            'URL without host'  => ['https:///path'],
            'mailto'            => ['mailto:someone@example.com'],
        ];
    }

    /**
     * @return array<string, array{float, string}>
     */
    public static function invalidPriorityProvider(): array
    {
        return [
            'below zero'   => [-0.1, '-0.1'],
            'above one'    => [1.1, '1.1'],
            'not a number' => [NAN, 'NaN'],
        ];
    }

    /**
     * @return array<string, array{float}>
     */
    public static function validPriorityProvider(): array
    {
        return [
            'zero'   => [0.0],
            'middle' => [0.5],
            'one'    => [1.0],
        ];
    }

    #[DataProvider('validPriorityProvider')]
    #[Test]
    public function acceptsPrioritiesFromZeroToOne(float $priority): void
    {
        static::assertSame($priority, (new SitemapEntry('/', priority: $priority))->priority);
    }

    #[DataProvider('absoluteLocationProvider')]
    #[Test]
    public function makesTheLocationAbsoluteAgainstTheBaseUrl(string $location, string $expected): void
    {
        static::assertSame($expected, (new SitemapEntry($location))->absoluteLocation('https://www.example.com'));
    }

    #[DataProvider('invalidLocationProvider')]
    #[Test]
    public function rejectsLocationsThatAreNeitherPathsNorHttpUrls(string $location): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Sitemap location \"{$location}\" must be a path starting with \"/\" or an absolute http(s) URL",
        );

        new SitemapEntry($location);
    }

    #[DataProvider('invalidPriorityProvider')]
    #[Test]
    public function rejectsPrioritiesOutsideZeroToOne(float $priority, string $shown): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Sitemap priority must be between 0.0 and 1.0, {$shown} given");

        new SitemapEntry('/', priority: $priority);
    }

    #[Test]
    public function treatsAnAbsoluteUrlAsNotAPath(): void
    {
        static::assertFalse((new SitemapEntry('https://www.example.com/'))->isPath());
    }

    #[Test]
    public function treatsARootRelativeLocationAsAPath(): void
    {
        static::assertTrue((new SitemapEntry('/about'))->isPath());
    }
}
