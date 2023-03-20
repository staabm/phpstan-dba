<?php

namespace DoctrineKeyValueStyleRuleTest;

use staabm\PHPStanDba\Tests\Fixture\Connection;

class Foo
{
    public function errorIfTableIsNotConstantString(Connection $conn, string $tableName)
    {
        $conn->assembleNoArrays($tableName);
    }

    public function errorIfTableDoesNotExist(Connection $conn)
    {
        $conn->assembleNoArrays('not_a_table');
    }

    public function errorIfFirstArrayArgIsNotArray(Connection $conn)
    {
        $conn->assembleOneArray('ada', 'not_an_array');
    }

    public function errorIfSecondArrayArgIsNotArray(Connection $conn)
    {
        $conn->assembleTwoArrays('ada', ['adaid' => 1], 'not_an_array');
    }

    public function errorIfArrayArgIsNotConstant(Connection $conn, string $columnName)
    {
        $conn->assembleOneArray('ada', [$columnName => 'foo']);
    }

    public function errorIfColumnIsConstantInt(Connection $conn)
    {
        $conn->assembleOneArray('ada', [42 => 'foo']);
    }

    public function errorIfColumnDoesNotExist(Connection $conn)
    {
        $conn->assembleOneArray('ada', ['not_a_column' => 'foo']);
    }

    public function errorIfColumnDoesNotAcceptValueType(Connection $conn, string $value)
    {
        $conn->assembleOneArray('ada', ['adaid' => $value]);
    }

    public function errorIfColumnMaybeAcceptsValueType(Connection $conn, ?int $value)
    {
        $conn->assembleOneArray('ada', ['adaid' => $value]);
    }

    public function errorIfValueIsMixedType(Connection $conn, mixed $value)
    {
        $conn->assembleOneArray('ada', ['adaid' => $value]);
    }

    /**
     * @param int<0, 65535> $value
     */
    public function noErrorIfValueIsImproperIntegerRangeType(Connection $conn, int $value)
    {
        $conn->assembleOneArray('ada', ['adaid' => $value]);
    }

    public function noErrorIfColumnAcceptsNullableInt(Connection $conn, ?int $value)
    {
        $conn->assembleOneArray('ak', ['eladaid' => $value]);
    }

    public function noErrorWithStringValue(Connection $conn, string $value)
    {
        $conn->assembleOneArray('ada', ['email' => 'foo']);
        $conn->assembleOneArray('ada', ['email' => $value]);
    }

    public function noErrorWithIntValue(Connection $conn, int $value)
    {
        $conn->assembleOneArray('ada', ['adaid' => 1]);
        $conn->assembleOneArray('ada', ['adaid' => $value]);
    }

    /**
     * @param array{adaid: int, email: string} $params
     */
    public function noErrorWithArrayShapeValue(Connection $conn, array $params)
    {
        $conn->assembleOneArray('ada', $params);
    }

    public function noErrorWithOnlyTableName(Connection $conn)
    {
        $conn->assembleNoArrays('ada');
    }

    public function noErrorWithMultipleArrayArgs(Connection $conn)
    {
        $conn->assembleTwoArrays('ada', ['adaid' => 1], ['email' => 'foo']);
    }

    public function noErrorWithBackticks(Connection $conn)
    {
        $conn->assembleOneArray('`ada`', ['`adaid`' => 1]);
    }

    /**
     * @param 'ada'|'not_a_table1'|'not_a_table2' $table
     */
    public function errorInTableNameUnion(Connection $conn, string $table)
    {
        $conn->assembleNoArrays($table);
    }

    /**
     * @param array{email: string}|array{not_a_column1: int}|array{not_a_column2: int} $params
     */
    public function errorInParamArrayUnion(Connection $conn, array $params)
    {
        $conn->assembleOneArray('ada', $params);
    }

    /**
     * @param int|float $value
     */
    public function errorWithUnacceptableUnionValue(Connection $conn, $value)
    {
        $conn->assembleOneArray('typemix', ['c_int' => $value]);
    }

    /**
     * @param array-key $value A benevolent union type (int|string)
     */
    public function errorWithBenevolentUnionValue(Connection $conn, $value)
    {
        $conn->assembleOneArray('typemix', ['c_int' => $value]);
    }

    public function noErrorWithIntValueForFloatColumn(Connection $conn, int $value)
    {
        $conn->assembleOneArray('typemix', ['c_float' => $value]);
    }
}
