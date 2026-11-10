<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PHPStan\DependencyInjection\Container;
use PHPStan\PhpDoc\TypeStringResolver;
use Throwable;

/**
 * Utility class to access the PHPStan container from phpstan-dba internal classes which cannot access the DI because of BC.
 *
 * @internal
 */
final class DIContainerBridge
{
    private static Container $container;

    public function __construct(Container $container)
    {
        self::$container = $container;
    }

    /**
     * @phpstan-template T of object
     * @phpstan-param class-string<T> $className
     * @phpstan-return T
     * @return mixed
     */
    public static function getByType(string $className): object
    {
        return self::$container->getByType($className);
    }

    public static function isAvailable(): bool
    {
        try {
            self::$container->getByType(TypeStringResolver::class);
            return true;
        } catch (Throwable $exception) {
            return false;
        }
    }
}
