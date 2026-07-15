<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Tests\QueryReflection;

use PHPStan\Type\IntegerType;
use PHPUnit\Framework\TestCase;
use staabm\PHPStanDba\CacheNotPopulatedException;
use staabm\PHPStanDba\Error;
use staabm\PHPStanDba\QueryReflection\QueryReflector;
use staabm\PHPStanDba\QueryReflection\ReflectionCache;
use function unlink;

final class ReflectionCacheTest extends TestCase
{
    private const CACHE_FILE = __DIR__ . '/some-file';

    public function testPutResultType(): void
    {
        $query = 'SELECT 1 FROM abc WHERE x = 6';

        $cache = ReflectionCache::create(self::CACHE_FILE);
        self::assertFalse($cache->hasValidationError($query));
        self::assertFalse($cache->hasResultType($query, QueryReflector::FETCH_TYPE_BOTH));

        $cache->putResultType($query, QueryReflector::FETCH_TYPE_BOTH, new IntegerType());
        self::assertTrue($cache->hasResultType($query, QueryReflector::FETCH_TYPE_BOTH));
        self::assertInstanceOf(IntegerType::class, $cache->getResultType($query, QueryReflector::FETCH_TYPE_BOTH));

        self::assertFalse($cache->hasValidationError($query));
        self::assertNull($cache->getValidationError($query));
    }

    public function testPutValidationError(): void
    {
        $query = 'SELECT 1 FROM abc WHERE unknownColumn = 6';

        $cache = ReflectionCache::create(self::CACHE_FILE);
        self::assertFalse($cache->hasValidationError($query));
        self::assertFalse($cache->hasResultType($query, QueryReflector::FETCH_TYPE_BOTH));

        $error = new Error('some error', 123);
        $cache->putValidationError($query, $error);
        self::assertTrue($cache->hasValidationError($query));
        self::assertSame($error, $cache->getValidationError($query));

        self::assertFalse($cache->hasResultType($query, QueryReflector::FETCH_TYPE_BOTH));
        self::assertNull($cache->getResultType($query, QueryReflector::FETCH_TYPE_BOTH));
    }

    public function testCacheNotPopulated(): void
    {
        $query = 'SELECT 1 FROM abc WHERE x = 6';
        $cache = ReflectionCache::create(self::CACHE_FILE);

        self::assertFalse($cache->hasResultType($query, QueryReflector::FETCH_TYPE_BOTH));
        try {
            $cache->getResultType($query, QueryReflector::FETCH_TYPE_BOTH);
            self::fail();
        } catch (CacheNotPopulatedException $e) {
        }

        self::assertFalse($cache->hasValidationError($query));
        try {
            $cache->getValidationError($query);
            self::fail();
        } catch (CacheNotPopulatedException $e) {
        }
    }

    public function tearDown(): void
    {
        unlink(self::CACHE_FILE);
    }
}
