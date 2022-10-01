<?php

namespace staabm\PHPStanDba\Tests;

use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPUnit\Framework\TestCase;
use staabm\PHPStanDba\QueryReflection\QuerySimulation;

class QuerySimulationTest extends TestCase
{
    public function testIntersectionTypeInt()
    {
        // non-empty-array<int, int>
        $builder = ConstantArrayTypeBuilder::createEmpty();
        $builder->setOffsetValueType(new IntegerType(), new IntegerType());

        $simulatedValue = QuerySimulation::simulateParamValueType($builder->getArray(), false);
        $this->assertNotNull($simulatedValue);
    }

    public function testIntersectionTypeString()
    {
        // non-empty-array<string, int>
        $builder = ConstantArrayTypeBuilder::createEmpty();
        $builder->setOffsetValueType(new StringType(), new IntegerType());

        $simulatedValue = QuerySimulation::simulateParamValueType($builder->getArray(), false);
        $this->assertNotNull($simulatedValue);
    }

    public function testIntersectionTypeMix()
    {
        // non-empty-array<string, int>
        $builder = ConstantArrayTypeBuilder::createEmpty();
        $builder->setOffsetValueType(new StringType(), new IntegerType());
        $builder->setOffsetValueType(new IntegerType(), new FloatType());

        $simulatedValue = QuerySimulation::simulateParamValueType($builder->getArray(), false);
        $this->assertNotNull($simulatedValue);
    }

    /**
     * @dataProvider provideQueriesWithComments
     */
    public function testStripComments(string $query, string $expectedQuery)
    {
        self::assertSame($expectedQuery, QuerySimulation::stripComments($query));
    }

    /**
     * @return iterable<array{string, string}>
     */
    public function provideQueriesWithComments(): iterable
    {
        yield 'Single line comment' => [
            'SELECT * FROM ada; -- ignore me',
            'SELECT * FROM ada;',
        ];
        yield 'Single line block comment' => [
            'SELECT * FROM ada; /* ignore me */',
            'SELECT * FROM ada;',
        ];
        yield 'Single line comment inside block comment' => [
            'SELECT * FROM ada; /* -- ignore me */',
            'SELECT * FROM ada;',
        ];
        yield 'Select comment-like value' => [
            'SELECT "hello -- darkness my old friend -- /* ive come to talk with you again */ bye" FROM ada --thanks',
            'SELECT "hello -- darkness my old friend -- /* ive come to talk with you again */ bye" FROM ada',
        ];
        yield 'Multi-line comment' => [
            '
                SELECT * FROM ada
                /*
                    nice
                    comment
                 */
            ',
            'SELECT * FROM ada',
        ];
    }
}
