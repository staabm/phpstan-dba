<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Tests;

use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\IntegerType;
use PHPUnit\Framework\TestCase;
use staabm\PHPStanDba\PdoReflection\PdoStatementReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflector;

class PdoStatementReflectionTest extends TestCase
{
    /**
     * @dataProvider fetchModes
     *
     * @param QueryReflector::FETCH_TYPE*|null $expected
     */
    public function testFetchMode(int $mode, ?int $expected): void
    {
        self::assertSame($expected, (new PdoStatementReflection())->getFetchType(new ConstantIntegerType($mode)));
    }

    /**
     * @return iterable<string, array{int, QueryReflector::FETCH_TYPE*|null}>
     */
    public function fetchModes(): iterable
    {
        yield 'associative' => [2, QueryReflector::FETCH_TYPE_ASSOC];
        yield 'numeric' => [3, QueryReflector::FETCH_TYPE_NUMERIC];
        yield 'both' => [4, QueryReflector::FETCH_TYPE_BOTH];
        yield 'object' => [5, QueryReflector::FETCH_TYPE_CLASS];
        yield 'column' => [7, QueryReflector::FETCH_TYPE_COLUMN];
        yield 'class' => [8, QueryReflector::FETCH_TYPE_CLASS];
        yield 'key pair' => [12, QueryReflector::FETCH_TYPE_KEY_VALUE];
        yield 'unsupported' => [0, null];
    }

    public function testUnknownFetchMode(): void
    {
        self::assertNull((new PdoStatementReflection())->getFetchType(new IntegerType()));
    }
}
