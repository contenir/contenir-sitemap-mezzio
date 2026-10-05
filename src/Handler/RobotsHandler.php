<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Handler;

use Contenir\Sitemap\Mezzio\Http\BaseUrl;
use Contenir\Sitemap\Mezzio\Robots\RobotsRules;
use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/**
 * Serves robots.txt: the configured rules, then a Sitemap line pointing at
 * the sitemap route when it is registered.
 *
 * @api
 */
final readonly class RobotsHandler implements RequestHandlerInterface
{
    public const string CONTENT_TYPE = 'text/plain; charset=utf-8';

    public const string ROUTE = 'robots';

    /**
     * @param ?string $sitemapPath The sitemap route's path, or null to leave the Sitemap line out.
     */
    public function __construct(
        private RobotsRules $rules,
        private BaseUrl $baseUrl,
        private ?string $sitemapPath,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * @throws RuntimeException When no base URL is configured and the request URI has no host.
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $baseUrl = $this->baseUrl->resolve($request);
        $body    = $this->rules->render($baseUrl, null === $this->sitemapPath ? null : $baseUrl . $this->sitemapPath);

        return $this->responseFactory
            ->createResponse()
            ->withHeader('Content-Type', self::CONTENT_TYPE)
            ->withBody($this->streamFactory->createStream($body));
    }
}
