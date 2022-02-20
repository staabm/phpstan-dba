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

final class MysqlTypeMapper
{
    public const FLAG_AUTO_INCREMENT = 'AUTO_INCREMENT';
    public const FLAG_NUMERIC = 'NUM';
    public const FLAG_UNSIGNED = 'UNSIGNED';

    /**
     * @param list<string> $mysqlFlags
     */
    public function mapToPHPStanType(string $mysqlType, array $mysqlFlags, int $length, ?string $function = null): Type
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
                $phpstanType = match ($length) {
                    3,4 => $mysqlIntegerRanges->unsignedTinyInt(),
                    5 => $mysqlIntegerRanges->unsignedSmallInt(),
                    8 => $mysqlIntegerRanges->unsignedMediumInt(),
                    10 => $mysqlIntegerRanges->unsignedInt(),
                    20 => $mysqlIntegerRanges->unsignedBigInt(),
                    default => null,
                };
            } else {
                $phpstanType = match ($length) {
                    1, 4 => $mysqlIntegerRanges->signedTinyInt(),
                    6 => $mysqlIntegerRanges->signedSmallInt(),
                    9 => $mysqlIntegerRanges->signedMediumInt(),
                    11 => $mysqlIntegerRanges->signedInt(),
                    20, 22 => $mysqlIntegerRanges->signedBigInt(),
                    default => null,
                };
            }

            if ('COUNT' === $function) {
                $phpstanType = $mysqlIntegerRanges->unsignedBigInt();
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
        if (\in_array(strtoupper($mysqlType), ['DOUBLE', 'NEWDECIMAL', 'REAL'], true)) {
            $phpstanType = new FloatType();
        }

        // fallbacks
        if (null === $phpstanType) {
            $phpstanType = match (strtoupper($mysqlType)) {
                'LONGLONG',
                'LONG',
                'SHORT',
                'TINY',
                'BIT',
                'INT24' => new IntegerType(),
                'BLOB',
                'CHAR',
                'STRING',
                'VAR_STRING',
                'JSON',
                'DATE',
                'TIME',
                'DATETIME',
                'TIMESTAMP' => new StringType(),
                default => new MixedType(),
            };
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
}
