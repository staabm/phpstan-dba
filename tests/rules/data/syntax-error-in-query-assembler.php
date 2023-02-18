<?php

namespace SyntaxErrorInQueryAssemblerRuleTest;

use staabm\PHPStanDba\Tests\Fixture\Connection;

class Foo
{
    public function errorIfTableIsNotLiteralString(Connection $conn, string $tableName)
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

    public function noErrorWithStringValue(Connection $conn, string $value)
    {
        $conn->assembleOneArray('ada', ['email' => 'foo']);
        $conn->assembleOneArray('ada', ['email' => $value]);
    }

    public function noErrorWithIntValue(Connection $conn, int $value)
    {
        $conn->assembleOneArray('ada', ['adaid' => 1]);
        $conn->assembleOneArray('ada', ['adaid' => $int]);
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
}
