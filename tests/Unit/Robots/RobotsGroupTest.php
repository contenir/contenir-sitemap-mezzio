<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Tests\Unit\Robots;

use Contenir\Sitemap\Mezzio\Robots\RobotsGroup;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class RobotsGroupTest extends TestCase
{
    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidGroupProvider(): array
    {
        return [
            'not an array'      => ['/admin/'],
            'unknown directive' => [['dissallow' => ['/admin/']]],
            'list of paths'     => [['/admin/']],
        ];
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidPathsProvider(): array
    {
        return [
            'allow not a list'           => [['allow' => '/'], 'Robots paths for "*" must be a list'],
            'disallow not a list'        => [['disallow' => '/admin/'], 'Robots paths for "*" must be a list'],
            'empty allow path'           => [
                ['allow' => ['']],
                'Robots path for "*" must be a non-empty string without control characters',
            ],
            'disallow path not a string' => [
                ['disallow' => [42]],
                'Robots path for "*" must be a non-empty string without control characters',
            ],
            'line break in a path'       => [
                ['disallow' => ["/admin/\nSitemap: https://evil.example.net/"]],
                'Robots path for "*" must be a non-empty string without control characters',
            ],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidUserAgentProvider(): array
    {
        return [
            'empty'           => [''],
            'line break'      => ["*\nDisallow: /"],
            'carriage return' => ["*\rDisallow: /"],
            'tab'             => ["Googlebot\t"],
            'null byte'       => ["Googlebot\0"],
            'delete char'     => ["Googlebot\x7F"],
        ];
    }

    #[Test]
    public function readsAGroupWithOnlyAllowPaths(): void
    {
        $group = RobotsGroup::fromConfig('*', ['allow' => ['/public/']]);

        static::assertSame("User-agent: *\nAllow: /public/", $group->render());
    }

    #[Test]
    public function readsAGroupWithOnlyDisallowPaths(): void
    {
        $group = RobotsGroup::fromConfig('*', ['disallow' => ['/admin/']]);

        static::assertSame("User-agent: *\nDisallow: /admin/", $group->render());
    }

    #[Test]
    public function readsAllowAndDisallowListsFromConfig(): void
    {
        $group = RobotsGroup::fromConfig('Googlebot', ['allow' => ['/'], 'disallow' => ['/admin/', '/tmp/']]);

        static::assertSame("User-agent: Googlebot\nAllow: /\nDisallow: /admin/\nDisallow: /tmp/", $group->render());
    }

    #[DataProvider('invalidGroupProvider')]
    #[Test]
    public function rejectsGroupsThatAreNotAllowAndDisallowLists(mixed $group): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Robots rules for "*" must be an array with only "allow" and "disallow" lists',
        );

        RobotsGroup::fromConfig('*', $group);
    }

    /**
     * @param array<string, mixed> $group
     */
    #[DataProvider('invalidPathsProvider')]
    #[Test]
    public function rejectsInvalidPaths(array $group, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        RobotsGroup::fromConfig('*', $group);
    }

    #[DataProvider('invalidUserAgentProvider')]
    #[Test]
    public function rejectsInvalidUserAgents(string $userAgent): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Robots user agent must be a non-empty string without control characters');

        new RobotsGroup($userAgent);
    }

    #[Test]
    public function rendersAGroupWithoutPathsAsDisallowingNothing(): void
    {
        static::assertSame("User-agent: Bingbot\nDisallow:", (new RobotsGroup('Bingbot'))->render());
    }
}
