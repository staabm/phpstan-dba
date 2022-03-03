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
use staabm\PHPStanDba\Types\PgsqlIntegerRanges;

use function strtoupper;

final class PgsqlTypeMapper implements TypeMapper
{
    public const FLAG_AUTO_INCREMENT = 'AUTO_INCREMENT';
    public const FLAG_NOT_NULL = 'NOT_NULL';
    public const FLAG_NUMERIC = 'NUM';

    /**
     * @param list<string> $flags
     */
    public function mapToPHPStanType(string $type, array $flags, int $length): Type
    {
        $numeric = false;
        $notNull = false;
        $autoIncrement = false;

        foreach ($flags as $flag) {
            switch (strtoupper($flag)) {
                case self::FLAG_NUMERIC:
                    $numeric = true;
                    break;

                case self::FLAG_NOT_NULL:
                    $notNull = true;
                    break;

                case self::FLAG_AUTO_INCREMENT:
                    $autoIncrement = true;
                    break;
            }
        }

        $phpstanType = null;
        $integerRanges = new PgsqlIntegerRanges();

        if ($numeric) {
            $phpstanType = match ($length) {
                2 => $integerRanges->smallint(),
                4 => $integerRanges->integer(),
                8 => $integerRanges->bigint(),
                default => null,
            };
        }

        if ($autoIncrement) {
            $phpstanType = match ($length) {
                4 => $integerRanges->serial(),
                8 => $integerRanges->bigserial(),
                default => throw new ShouldNotHappenException(),
            };
        }

        // floats are detected as numerics in mysqli
        if (\in_array(strtoupper($type), ['DECIMAL', 'NUMERIC', 'REAL', 'DOUBLE PRECISION'], true)) {
            $phpstanType = new FloatType();
        }

        // fallbacks
        if (null === $phpstanType) {
            $phpstanType = match (strtoupper($type)) {
                'INT2',
                'INT4',
                'INT8' => new IntegerType(),
                'VARCHAR' => new StringType(),
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

    public function isNumericCol(string $mysqlType): bool
    {
        return match (strtoupper($mysqlType)) {
            'DECIMAL',
            'NUMERIC',
            'REAL',
            'DOUBLE PRECISION',
            'INT2',
            'INT4',
            'INT8' => true,
            default => false,
        };
    }
}
