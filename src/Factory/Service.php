<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Factory;

use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

use function get_debug_type;
use function sprintf;

/**
 * Typed service lookup for the factories.
 *
 * @internal
 */
final class Service
{
    /**
     * @template T of object
     *
     * @param class-string<T> $type
     *
     * @return T
     *
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException When the service is not a $type.
     *
     * @mago-expect analysis:mixed-assignment Container services are untyped; the type is checked here.
     */
    public static function get(ContainerInterface $container, string $name, string $type): object
    {
        $service = $container->get($name);
        if (! $service instanceof $type) {
            throw new InvalidArgumentException(sprintf(
                'Service "%s" must be a %s, %s given',
                $name,
                $type,
                get_debug_type($service),
            ));
        }

        return $service;
    }
}
