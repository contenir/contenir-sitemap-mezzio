<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Tests\Unit\Factory;

use Contenir\Sitemap\Mezzio\Factory\Service;
use Contenir\Sitemap\Mezzio\Tests\TestAsset\Container\InMemoryContainer;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

#[Group('unit')]
final class ServiceTest extends TestCase
{
    #[Test]
    public function rejectsAServiceOfTheWrongType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Service "router" must be a stdClass, string given');

        Service::get(new InMemoryContainer(['router' => 'fast-route']), 'router', stdClass::class);
    }

    #[Test]
    public function returnsAServiceOfTheExpectedType(): void
    {
        $service = new stdClass();

        static::assertSame($service, Service::get(
            new InMemoryContainer(['service' => $service]),
            'service',
            stdClass::class,
        ));
    }
}
