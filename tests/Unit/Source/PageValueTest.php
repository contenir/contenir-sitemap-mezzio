<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Tests\Unit\Source;

use Contenir\Sitemap\Mezzio\ChangeFrequency;
use Contenir\Sitemap\Mezzio\Source\PageValue;
use DateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use const DATE_ATOM;

#[Group('unit')]
final class PageValueTest extends TestCase
{
    /**
     * @return array<string, array{mixed, ?ChangeFrequency}>
     */
    public static function changeFrequencyProvider(): array
    {
        return [
            'monthly'      => ['monthly', ChangeFrequency::Monthly],
            'never'        => ['never', ChangeFrequency::Never],
            'unknown'      => ['fortnightly', null],
            'capitalised'  => ['Weekly', null],
            'not a string' => [7, null],
            'missing'      => [null, null],
        ];
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidLastModifiedProvider(): array
    {
        return [
            'missing'      => [null],
            'empty'        => [''],
            'unparseable'  => ['not a date'],
            'not a string' => [1_700_000_000],
        ];
    }

    /**
     * @return array<string, array{mixed, ?float}>
     */
    public static function priorityProvider(): array
    {
        return [
            'workflow string' => ['0.6', 0.6],
            'zero'            => ['0', 0.0],
            'one'             => ['1.0', 1.0],
            'float'           => [0.25, 0.25],
            'integer'         => [1, 1.0],
            'above one'       => ['1.5', null],
            'below zero'      => ['-0.1', null],
            'not numeric'     => ['high', null],
            'missing'         => [null, null],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function validLastModifiedProvider(): array
    {
        return [
            'laminas-mvc strategy format' => ['2026-03-04 05:06:07', '2026-03-04T05:06:07+00:00'],
            'atom'                        => ['2026-03-04T05:06:07+10:00', '2026-03-04T05:06:07+10:00'],
            'date only'                   => ['2026-03-04', '2026-03-04T00:00:00+00:00'],
        ];
    }

    #[DataProvider('validLastModifiedProvider')]
    #[Test]
    public function parsesLastModifiedStrings(string $value, string $expected): void
    {
        static::assertSame($expected, PageValue::lastModified($value)?->format(DATE_ATOM));
    }

    #[DataProvider('invalidLastModifiedProvider')]
    #[Test]
    public function readsAnInvalidLastModifiedAsNone(mixed $value): void
    {
        static::assertNull(PageValue::lastModified($value));
    }

    #[DataProvider('changeFrequencyProvider')]
    #[Test]
    public function readsOnlyChangeFrequenciesTheProtocolAllows(mixed $value, ?ChangeFrequency $expected): void
    {
        static::assertSame($expected, PageValue::changeFrequency($value));
    }

    #[DataProvider('priorityProvider')]
    #[Test]
    public function readsOnlyPrioritiesFromZeroToOne(mixed $value, ?float $expected): void
    {
        static::assertSame($expected, PageValue::priority($value));
    }

    #[Test]
    public function usesALastModifiedDateAsIs(): void
    {
        $date = new DateTime('2026-03-04 05:06:07');

        static::assertSame($date, PageValue::lastModified($date));
    }
}
