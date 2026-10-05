<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Handler;

use Contenir\Sitemap\Mezzio\Http\BaseUrl;
use Contenir\Sitemap\Mezzio\SitemapSourceInterface;
use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use XMLWriter;

use function array_key_exists;

use const DATE_ATOM;

/**
 * Serves the sitemap XML: one <url> per entry of the sitemap source, with
 * paths made absolute against the base URL and repeated URLs left out.
 *
 * @api
 */
final readonly class SitemapHandler implements RequestHandlerInterface
{
    public const string CONTENT_TYPE = 'application/xml; charset=utf-8';

    public const string ROUTE = 'sitemap';

    public const string XML_NAMESPACE = 'http://www.sitemaps.org/schemas/sitemap/0.9';

    public function __construct(
        private SitemapSourceInterface $source,
        private BaseUrl $baseUrl,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * @throws RuntimeException When no base URL is configured and the request URI has no host.
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse()
            ->withHeader('Content-Type', self::CONTENT_TYPE)
            ->withBody($this->streamFactory->createStream($this->render($this->baseUrl->resolve($request))));
    }

    /**
     * endDocument() closes the <urlset> element.
     */
    private function render(string $baseUrl): string
    {
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->setIndentString('  ');
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElementNs(null, 'urlset', self::XML_NAMESPACE);

        $seen = [];
        foreach ($this->source->getEntries() as $entry) {
            $location = $entry->absoluteLocation($baseUrl);
            if (array_key_exists($location, $seen)) {
                continue;
            }

            $seen[$location] = $entry;
            $xml->startElement('url');
            $xml->writeElement('loc', $location);
            if (null !== $entry->lastModified) {
                $xml->writeElement('lastmod', $entry->lastModified->format(DATE_ATOM));
            }

            if (null !== $entry->changeFrequency) {
                $xml->writeElement('changefreq', $entry->changeFrequency->value);
            }

            if (null !== $entry->priority) {
                $xml->writeElement('priority', (string) $entry->priority);
            }

            $xml->endElement();
        }

        $xml->endDocument();

        return $xml->outputMemory();
    }
}
