<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Tests\Trait;

use Laminas\Cache\Storage\StorageInterface;

use function array_key_exists;

/**
 * A StorageInterface stub backed by an array the test can inspect.
 */
trait InMemoryCacheTrait
{
    /** @var array<string, mixed> */
    private array $cacheItems = [];

    private function createInMemoryCache(): StorageInterface
    {
        $cache = $this->createStub(StorageInterface::class);
        $cache->method('hasItem')
            ->willReturnCallback(fn(string $key): bool => array_key_exists($key, $this->cacheItems));
        $cache->method('getItem')->willReturnCallback(fn(string $key): mixed => $this->cacheItems[$key] ?? null);
        $cache->method('setItem')
            ->willReturnCallback(function (string $key, mixed $value): bool {
                $this->cacheItems[$key] = $value;

                return true;
            });
        $cache->method('removeItem')
            ->willReturnCallback(function (string $key): bool {
                unset($this->cacheItems[$key]);

                return true;
            });

        return $cache;
    }
}
