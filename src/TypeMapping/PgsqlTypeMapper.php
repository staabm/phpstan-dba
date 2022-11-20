<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\TypeMapping;

use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use staabm\PHPStanDba\QueryReflection\DbaApi;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\Types\PgsqlIntegerRanges;
use function strtoupper;

final class PgsqlTypeMapper implements TypeMapper
{
    /**
     * @var DbaApi|null
     */
    private $dbaApi;

    public function __construct(?DbaApi $dbaApi)
    {
        $this->dbaApi = $dbaApi;
    }

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
            switch ($length) {
                case 2:
                    $phpstanType = $integerRanges->smallint();
                    break;
                case 4:
                    $phpstanType = $integerRanges->integer();
                    break;
                case 8:
                    $phpstanType = $integerRanges->bigint();
                    break;
                default:
                    $phpstanType = null;
                    break;
            }
        }

        if ($autoIncrement) {
            switch ($length) {
                case 4:
                    $phpstanType = $integerRanges->serial();
                    break;
                case 8:
                    $phpstanType = $integerRanges->bigserial();
                    break;
                default:
                    throw new ShouldNotHappenException();
            }
        }

        // floats are detected as numerics in mysqli
        if (\in_array(strtoupper($type), ['DECIMAL', 'NUMERIC', 'REAL', 'DOUBLE PRECISION', 'FLOAT8'], true)) {
            $phpstanType = new FloatType();
        }

        // fallbacks
        if (null === $phpstanType) {
            switch (strtoupper($type)) {
                case 'BOOL':
                    $phpstanType = new BooleanType();
                    break;
                case 'BIT':
                case 'INT2':
                case 'INT4':
                case 'INT8':
                    $phpstanType = new IntegerType();
                    break;
                case 'JSON':
                case 'JSONB':
                case 'VARCHAR':
                case 'TEXT':
                    $phpstanType = new StringType();
                    break;
                case 'DATE':
                case 'TIME':
                case 'TIMESTAMP':
                    if (null !== $this->dbaApi && $this->dbaApi->returnsDateTimeImmutable()) {
                        $phpstanType = new ObjectType(\DateTimeImmutable::class);
                        break;
                    }

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
            case 'DECIMAL':
            case 'NUMERIC':
            case 'REAL':
            case 'DOUBLE PRECISION':
            case 'INT2':
            case 'INT4':
            case 'INT8':
                return true;
            default:
                return false;
        }
    }
}
