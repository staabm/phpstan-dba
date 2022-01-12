<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PHPStan\Type\BooleanType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use staabm\PHPStanDba\DbaException;

/**
 * @internal
 */
final class QuerySimulation
{
    public static function simulateParamValueType(Type $paramType): ?string {
        if ($paramType instanceof ConstantScalarType) {
            return (string) $paramType->getValue();
        }

        $integerType = new IntegerType();
        if ($integerType->isSuperTypeOf($paramType)->yes()) {
            return '1';
        }

        $booleanType = new BooleanType();
        if ($booleanType->isSuperTypeOf($paramType)->yes()) {
            return '1';
        }

        if ($paramType->isNumericString()->yes()) {
            return '1';
        }

        $floatType = new FloatType();
        if ($floatType->isSuperTypeOf($paramType)->yes()) {
            return '1.0';
        }

        if ($paramType instanceof MixedType || $paramType instanceof StringType || $paramType instanceof IntersectionType || $paramType instanceof UnionType) {
            return null;
        }

        throw new DbaException(sprintf('Unexpected expression type %s', \get_class($paramType)));
    }

    public static function simulate(string $queryString): ?string
    {
        $queryString = self::stripTraillingLimit($queryString);

        if (null === $queryString) {
            return null;
        }
        $queryString .= ' LIMIT 0';

        return $queryString;
    }

    private static function stripTraillingLimit(string $queryString): ?string
    {
        return preg_replace('/\s*LIMIT\s+\d+\s*(,\s*\d*)?$/i', '', $queryString);
    }
}
