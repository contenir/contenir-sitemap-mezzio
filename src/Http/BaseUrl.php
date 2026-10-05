<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Http;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

use function array_intersect;
use function array_keys;
use function in_array;
use function is_array;
use function is_string;
use function parse_url;
use function preg_match;
use function rtrim;
use function sprintf;
use function strtolower;

/**
 * The scheme, host and optional path the sitemap and robots.txt URLs start
 * with, without a trailing slash.
 *
 * Configured, it is fixed: the response never depends on the request's Host
 * header, so a forged Host cannot inject URLs into a cached sitemap or
 * robots.txt. Unconfigured, it comes from the request URI's scheme, host and
 * non-standard port.
 *
 * @api
 */
final readonly class BaseUrl
{
    private ?string $baseUrl;

    /**
     * @param ?string $baseUrl An absolute http(s) URL, such as "https://www.example.com", or null to use the request.
     *
     * @throws InvalidArgumentException When the URL is not an absolute http(s) URL without credentials, query or
     *                                  fragment.
     */
    public function __construct(?string $baseUrl = null)
    {
        if (null !== $baseUrl && ! self::isValid($baseUrl)) {
            throw new InvalidArgumentException(sprintf(
                'Base URL "%s" must be an absolute http(s) URL without credentials, query, fragment or whitespace',
                $baseUrl,
            ));
        }

        $this->baseUrl = null === $baseUrl ? null : rtrim($baseUrl, characters: '/');
    }

    /**
     * @param mixed $baseUrl The "sitemap.base_url" config value.
     *
     * @throws InvalidArgumentException When the value is neither null nor a valid base URL.
     */
    public static function fromConfig(mixed $baseUrl): self
    {
        if (null !== $baseUrl && ! is_string($baseUrl)) {
            throw new InvalidArgumentException('sitemap.base_url must be a URL string or null');
        }

        return new self($baseUrl);
    }

    private static function isValid(string $baseUrl): bool
    {
        $parts = parse_url($baseUrl);

        return (
            is_array($parts)
                && 1 !== preg_match('/[\s\x00-\x1F\x7F]/', $baseUrl)
                && in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], strict: true)
                && '' !== ($parts['host'] ?? '')
                && [] === array_intersect(array_keys($parts), ['user', 'query', 'fragment'])
        );
    }

    /**
     * @throws RuntimeException When no base URL is configured and the request URI has no host.
     */
    public function resolve(ServerRequestInterface $request): string
    {
        if (null !== $this->baseUrl) {
            return $this->baseUrl;
        }

        $uri  = $request->getUri();
        $host = $uri->getHost();
        if ('' === $host) {
            throw new RuntimeException('The request URI has no host; configure sitemap.base_url');
        }

        $scheme = $uri->getScheme();
        $port   = $uri->getPort();

        return sprintf(
            '%s://%s%s',
            '' === $scheme ? 'http' : $scheme,
            $host,
            null === $port ? '' : ":{$port}",
        );
    }
}
