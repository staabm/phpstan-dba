<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Tests;

use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\TestCase;
use staabm\PHPStanDba\SchemaReflection\SchemaReflection;
use staabm\PHPStanDba\SqlAst\ParserInference;

class ParserInferenceTest extends TestCase
{
    /**
     * Regression: with FETCH_TYPE_ASSOC the reflector-derived row shape is
     * string-keyed only. Reading an integer offset on it returns ErrorType
     * under PHPStan >= 2.2 (previously a benign type). For alias-qualified
     * columns (`t.col`) QueryScope::resolveExpression() returns MixedType,
     * so the ErrorType used to be written over the (correct) reflector type
     * under the column name key, collapsing the row shape to
     * `array{col: *ERROR*, ...}`.
     *
     * @dataProvider provideAliasedAndUnaliasedQueries
     */
    public function testAssocOnlyRowShapeIsPreservedForAliasedColumns(string $sql): void
    {
        $serviceGroupIdType = IntegerRangeType::fromInterval(0, 4294967295);
        $nameType = new StringType();

        // mysqli FETCH_TYPE_ASSOC row shape: ONLY string keys, no integer offsets.
        $resultType = new ConstantArrayType(
            [
                new ConstantStringType('service_group_id'),
                new ConstantStringType('name'),
            ],
            [
                $serviceGroupIdType,
                $nameType,
            ]
        );

        $schema = new SchemaReflection(static function (string $query) use ($resultType): ?Type {
            if (stripos($query, 'SELECT * FROM service_group') === 0) {
                return $resultType;
            }
            return null;
        });

        $inference = new ParserInference($schema);
        $narrowed = $inference->narrowResultType($sql, $resultType);

        self::assertSame(
            'array{service_group_id: int<0, 4294967295>, name: string}',
            $narrowed->describe(VerbosityLevel::precise())
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public function provideAliasedAndUnaliasedQueries(): iterable
    {
        yield 'unqualified columns' => [
            'SELECT service_group_id, name FROM service_group',
        ];
        yield 'alias-qualified columns' => [
            'SELECT sg.service_group_id, sg.name FROM service_group sg',
        ];
    }
}
