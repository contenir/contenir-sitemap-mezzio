<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Tests\Unit\Robots;

use Contenir\Sitemap\Mezzio\Robots\RobotsGroup;
use Contenir\Sitemap\Mezzio\Robots\RobotsRules;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class RobotsRulesTest extends TestCase
{
    private const string LAMINAS_MVC_ROBOTS = <<<'ROBOTS'
        # robots.txt for https://www.example.com/

        User-agent: *
        Allow: /
        Disallow: /.well-known/

        Sitemap: https://www.example.com/sitemap.xml

        ROBOTS;

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidConfigProvider(): array
    {
        return [
            'robots not an array' => ['Disallow: /'],
            'rules not an array'  => [['rules' => 'Disallow: /']],
        ];
    }

    #[Test]
    public function defaultsToTheLaminasMvcModuleRules(): void
    {
        static::assertSame(
            self::LAMINAS_MVC_ROBOTS,
            RobotsRules::fromConfig([])->render('https://www.example.com', 'https://www.example.com/sitemap.xml'),
        );
    }

    #[Test]
    public function leavesTheSitemapLineOutWithoutASitemapUrl(): void
    {
        static::assertSame(
            "# robots.txt for https://www.example.com/\n\nUser-agent: *\nDisallow:\n",
            (new RobotsRules([new RobotsGroup('*')]))->render('https://www.example.com', null),
        );
    }

    #[Test]
    public function rejectsANumericUserAgent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Robots user agent must be a non-empty string without control characters');

        RobotsRules::fromConfig(['rules' => [['disallow' => ['/']]]]);
    }

    #[DataProvider('invalidConfigProvider')]
    #[Test]
    public function rejectsRulesThatAreNotAnArray(mixed $robots): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('sitemap.robots.rules must be an array of user agent => rules');

        RobotsRules::fromConfig($robots);
    }

    #[Test]
    public function rendersOnlyTheCommentAndSitemapWithoutGroups(): void
    {
        static::assertSame(
            "# robots.txt for https://www.example.com/\n\nSitemap: https://www.example.com/sitemap.xml\n",
            RobotsRules::fromConfig(['rules' => []])->render(
                'https://www.example.com',
                'https://www.example.com/sitemap.xml',
            ),
        );
    }

    #[Test]
    public function replacesTheDefaultRulesWithConfiguredRules(): void
    {
        $rules = RobotsRules::fromConfig([
            'rules' => [
                'Googlebot' => ['disallow' => ['/search/']],
                '*'         => ['disallow' => ['/']],
            ],
        ]);

        static::assertSame(
            "# robots.txt for https://www.example.com/\n\n"
                . "User-agent: Googlebot\nDisallow: /search/\n\n"
                . "User-agent: *\nDisallow: /\n\n"
                . "Sitemap: https://www.example.com/sitemap.xml\n",
            $rules->render('https://www.example.com', 'https://www.example.com/sitemap.xml'),
        );
    }
}
