<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\TypeMapping;

use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\Types\MysqlIntegerRanges;

final class MysqlTypeMapper implements TypeMapper
{
    /**
     * @param list<string> $mysqlFlags
     */
    public function mapToPHPStanType(string $mysqlType, array $mysqlFlags, int $length): Type
    {
        $numeric = false;
        $notNull = false;
        $unsigned = false;
        $autoIncrement = false;

        foreach ($mysqlFlags as $flag) {
            switch (strtoupper($flag)) {
                case self::FLAG_NUMERIC:
                    $numeric = true;
                    break;

                case 'NOT_NULL':
                    $notNull = true;
                    break;

                case self::FLAG_AUTO_INCREMENT:
                    $autoIncrement = true;
                    break;

                case self::FLAG_UNSIGNED:
                    $unsigned = true;
                    break;

                // ???
                case 'PRI_KEY':
                case 'PART_KEY':
                case 'MULTIPLE_KEY':
                case 'NO_DEFAULT_VALUE':
            }
        }

        $phpstanType = null;
        $mysqlIntegerRanges = new MysqlIntegerRanges();

        if ($numeric) {
            if ($unsigned) {
                switch ($length) {
                    case 3:
                    case 4:
                        $phpstanType = $mysqlIntegerRanges->unsignedTinyInt();
                        break;
                    case 5:
                        $phpstanType = $mysqlIntegerRanges->unsignedSmallInt();
                        break;
                    case 8:
                        $phpstanType = $mysqlIntegerRanges->unsignedMediumInt();
                        break;
                    case 10:
                        $phpstanType = $mysqlIntegerRanges->unsignedInt();
                        break;
                    case 20:
                        $phpstanType = $mysqlIntegerRanges->unsignedBigInt();
                        break;
                    default:
                        $phpstanType = null;
                        break;
                }
            } else {
                switch ($length) {
                    case 1:
                    case 4:
                        $phpstanType = $mysqlIntegerRanges->signedTinyInt();
                        break;
                    case 6:
                        $phpstanType = $mysqlIntegerRanges->signedSmallInt();
                        break;
                    case 9:
                        $phpstanType = $mysqlIntegerRanges->signedMediumInt();
                        break;
                    case 11:
                        $phpstanType = $mysqlIntegerRanges->signedInt();
                        break;
                    case 20:
                    case 22:
                        $phpstanType = $mysqlIntegerRanges->signedBigInt();
                        break;
                    default:
                        $phpstanType = null;
                        break;
                }
            }
        }

        if ($autoIncrement) {
            $phpstanType = $mysqlIntegerRanges->unsignedInt();
        }

        // mysqli/pdo support different integer-length for year, hardcode its type for cross driver consistency
        if ('YEAR' === strtoupper($mysqlType)) {
            // see https://dev.mysql.com/doc/refman/8.0/en/year.html
            $phpstanType = IntegerRangeType::fromInterval(0, 2155);
        }
        // floats are detected as numerics in mysqli
        if (\in_array(strtoupper($mysqlType), ['DOUBLE', 'REAL'], true)) {
            $phpstanType = new FloatType();
        }

        // fallbacks
        if (null === $phpstanType) {
            switch (strtoupper($mysqlType)) {
                case 'DECIMAL':
                case 'NEWDECIMAL':
                    $phpstanType = new IntersectionType([
                        new StringType(),
                        new AccessoryNumericStringType(),
                    ]);
                    break;
                case 'LONGLONG':
                case 'LONG':
                case 'SHORT':
                case 'TINY':
                case 'BIT':
                case 'INT24':
                    $phpstanType = new IntegerType();
                    break;
                case 'BLOB':
                case 'CHAR':
                case 'STRING':
                case 'VAR_STRING':
                case 'JSON':
                case 'DATE':
                case 'TIME':
                case 'DATETIME':
                case 'TIMESTAMP':
                    $phpstanType = new StringType();
                    break;
                default:
                    $phpstanType = new MixedType();
                    break;
            }
        }

        if (QueryReflection::getRuntimeConfiguration()->isStringifyTypes()) {
            $numberType = new UnionType([new IntegerType(), new FloatType()]);
            $isNumber = $numberType->isSuperTypeOf($phpstanType)->yes();

            if ($isNumber) {
                $phpstanType = new IntersectionType([
                    new StringType(),
                    new AccessoryNumericStringType(),
                ]);
            }
        }

        if (false === $notNull) {
            $phpstanType = TypeCombinator::addNull($phpstanType);
        }

        return $phpstanType;
    }

    public function isNumericCol(string $mysqlType): bool
    {
        switch (strtoupper($mysqlType)) {
            case 'LONGLONG':
            case 'LONG':
            case 'SHORT':
            case 'TINY':
            case 'YEAR':
            case 'BIT':
            case 'INT24':
                return true;
            default:
                return false;
        }
    }
}
