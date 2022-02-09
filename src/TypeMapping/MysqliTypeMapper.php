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

final class MysqliTypeMapper implements TypeMapper
{
    /** @var array<int, string> */
    private array $nativeTypes = [];

    /** @var array<int, string> */
    private array $nativeFlags = [];

    public function __construct()
    {
        $constants = get_defined_constants(true);
        foreach ($constants['mysqli'] as $c => $n) {
            if (!\is_int($n)) {
                // skip bool constants like MYSQLI_IS_MARIADB
                continue;
            }
            if (preg_match('/^MYSQLI_TYPE_(.*)/', $c, $m)) {
                if (!\is_string($m[1])) {
                    throw new ShouldNotHappenException();
                }
                $this->nativeTypes[$n] = $m[1];
            } elseif (preg_match('/MYSQLI_(.*)_FLAG$/', $c, $m)) {
                if (!\is_string($m[1])) {
                    throw new ShouldNotHappenException();
                }
                if (!\array_key_exists($n, $this->nativeFlags)) {
                    $this->nativeFlags[$n] = $m[1];
                }
            }
        }
    }

    public function mapToPHPStanType(int $mysqlType, int $mysqlFlags, int $length): Type
    {
        $numeric = false;
        $notNull = false;
        $unsigned = false;
        $autoIncrement = false;

        foreach ($this->flags2txt($mysqlFlags) as $flag) {
            switch ($flag) {
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
            $phpstanType = match ($this->type2txt($mysqlType)) {
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

    private function type2txt(int $typeId): ?string
    {
        return \array_key_exists($typeId, $this->nativeTypes) ? $this->nativeTypes[$typeId] : null;
    }

    /** @return list<string> */
    private function flags2txt(int $flagId): array
    {
        $result = [];
        foreach ($this->nativeFlags as $n => $t) {
            if ($flagId & $n) {
                $result[] = $t;
            }
        }

        return $result;
    }
}
