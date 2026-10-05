<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Tests\Unit\Http;

use Contenir\Sitemap\Mezzio\Http\BaseUrl;
use InvalidArgumentException;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[Group('unit')]
final class BaseUrlTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function configuredBaseUrlProvider(): array
    {
        return [
            'https host'               => ['https://www.example.com', 'https://www.example.com'],
            'trailing slash'           => ['https://www.example.com/', 'https://www.example.com'],
            'subdirectory'             => ['https://www.example.com/site/', 'https://www.example.com/site'],
            'http with port'           => ['http://localhost:8080', 'http://localhost:8080'],
            'upper-case scheme'        => ['HTTPS://www.example.com', 'HTTPS://www.example.com'],
            'several trailing slashes' => ['https://www.example.com//', 'https://www.example.com'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidBaseUrlProvider(): array
    {
        return [
            'empty'         => [''],
            'path only'     => ['/site'],
            'no host'       => ['https://'],
            'ftp scheme'    => ['ftp://www.example.com'],
            'user'          => ['https://user@www.example.com'],
            'password only' => ['https://:secret@www.example.com'],
            'query'         => ['https://www.example.com/?a=b'],
            'empty query'   => ['https://www.example.com/?'],
            'fragment'      => ['https://www.example.com/#top'],
            'line break'    => ["https://www.example.com/\nDisallow: /"],
            'space'         => ['https://www.example.com/a b'],
            'null byte'     => ["https://www.example.com/\0"],
            'delete char'   => ["https://www.example.com/\x7F"],
            'unparseable'   => ['http:///example.com'],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function requestUriProvider(): array
    {
        return [
            'https'                   => ['https://www.example.com/robots.txt', 'https://www.example.com'],
            'http'                    => ['http://www.example.com/robots.txt', 'http://www.example.com'],
            'non-standard port'       => ['https://www.example.com:8443/robots.txt', 'https://www.example.com:8443'],
            'standard port left out'  => ['https://www.example.com:443/robots.txt', 'https://www.example.com'],
            'user info left out'      => ['https://user:pass@www.example.com/robots.txt', 'https://www.example.com'],
            'query and path left out' => ['https://www.example.com/a/b?c=d#e', 'https://www.example.com'],
        ];
    }

    #[Test]
    public function defaultsAMissingSchemeToHttp(): void
    {
        $request = new ServerRequest(uri: (new Uri())->withHost('www.example.com'));

        static::assertSame('http://www.example.com', (new BaseUrl())->resolve($request));
    }

    #[Test]
    public function failsWhenTheRequestHasNoHostAndNoBaseUrlIsConfigured(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request URI has no host; configure sitemap.base_url');

        (new BaseUrl())->resolve(new ServerRequest(uri: '/sitemap.xml'));
    }

    #[Test]
    public function readsAStringConfigAsTheConfiguredBaseUrl(): void
    {
        $request = new ServerRequest(uri: 'https://www.example.com/');

        static::assertSame('https://example.org', BaseUrl::fromConfig('https://example.org/')->resolve($request));
    }

    #[Test]
    public function readsNullConfigAsTheRequestBaseUrl(): void
    {
        $request = new ServerRequest(uri: 'https://www.example.com/');

        static::assertSame('https://www.example.com', BaseUrl::fromConfig(null)->resolve($request));
    }

    #[Test]
    public function rejectsAConfigValueThatIsNotAString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('sitemap.base_url must be a URL string or null');

        BaseUrl::fromConfig(['https://www.example.com']);
    }

    #[DataProvider('invalidBaseUrlProvider')]
    #[Test]
    public function rejectsInvalidBaseUrls(string $baseUrl): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Base URL \"{$baseUrl}\" must be an absolute http(s) URL without credentials, query, fragment or whitespace",
        );

        new BaseUrl($baseUrl);
    }

    #[DataProvider('configuredBaseUrlProvider')]
    #[Test]
    public function usesTheConfiguredBaseUrlWhateverTheRequestHost(string $baseUrl, string $expected): void
    {
        $request = new ServerRequest(uri: 'https://forged.example.net/sitemap.xml');

        static::assertSame($expected, (new BaseUrl($baseUrl))->resolve($request));
    }

    #[DataProvider('requestUriProvider')]
    #[Test]
    public function usesTheRequestSchemeHostAndPortWhenNotConfigured(string $uri, string $expected): void
    {
        static::assertSame($expected, (new BaseUrl())->resolve(new ServerRequest(uri: $uri)));
    }
}
