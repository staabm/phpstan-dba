<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\TypeMapping;

use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\Types\MysqlIntegerRanges;

final class PDOTypeMapper implements TypeMapper
{
    public function mapToPHPStanType(int|string $mysqlType, int|array $mysqlFlags, int $length): Type
    {
        $numeric = false;
        $notNull = false;
        $unsigned = false;
        $autoIncrement = false;

        foreach ($mysqlFlags as $flag) {
            switch (strtoupper($flag)) {
                case 'NUM':
                    $numeric = true;
                    break;

                case 'NOT_NULL':
                    $notNull = true;
                    break;

                case 'AUTO_INCREMENT':
                    $autoIncrement = true;
                    break;

                case 'UNSIGNED':
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
        }

        if ($autoIncrement) {
            $phpstanType = $mysqlIntegerRanges->unsignedInt();
        }

        if (null === $phpstanType) {
            $phpstanType = match ($mysqlType) {
                'DOUBLE', 'NEWDECIMAL' => new FloatType(),
                'LONGLONG',
                'LONG',
                'SHORT',
                'YEAR',
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
