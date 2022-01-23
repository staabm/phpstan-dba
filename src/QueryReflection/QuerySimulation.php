<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use staabm\PHPStanDba\DbaException;
use staabm\PHPStanDba\UnresolvableQueryException;

/**
 * @internal
 */
final class QuerySimulation
{
    /**
     * @throws UnresolvableQueryException
     */
    public static function simulateParamValueType(Type $paramType, bool $preparedParam): ?string
    {
        if ($paramType instanceof ConstantScalarType) {
            return (string) $paramType->getValue();
        }

        if ($paramType instanceof ArrayType) {
            return self::simulateParamValueType($paramType->getItemType(), $preparedParam);
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

        if ($paramType instanceof UnionType) {
            foreach ($paramType->getTypes() as $type) {
                // pick one representative value out of the union
                $simulated = self::simulateParamValueType($type, $preparedParam);
                if (null !== $simulated) {
                    return $simulated;
                }
            }

            return null;
        }

        $stringType = new StringType();
        if ($stringType->isSuperTypeOf($paramType)->yes()) {
            // in a prepared context, regular strings are fine
            if (true === $preparedParam) {
                return '1';
            }

            // plain string types can contain anything.. we cannot reason about it
            return null;
        }

        // all types which we can't simulate and render a query unresolvable at analysis time
        if ($paramType instanceof MixedType || $paramType instanceof IntersectionType) {
            if (QueryReflection::getRuntimeConfiguration()->isDebugEnabled()) {
                throw new UnresolvableQueryException('Cannot simulate parameter value for type: '.$paramType->describe(VerbosityLevel::precise()));
            }

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
        // XXX someday we will use a proper SQL parser,
        $queryString = rtrim($queryString, ';');

        // strip trailling FOR UPDATE/FOR SHARE
        $queryString = preg_replace('/(.*)FOR (UPDATE|SHARE)\s*$/i', '$1', $queryString);

        if (null === $queryString) {
            throw new ShouldNotHappenException('Could not strip trailling FOR UPDATE/SHARE from query');
        }

        // strip trailling OFFSET
        $queryString = preg_replace('/(.*)OFFSET\s+["\']?\d+["\']?\s*$/i', '$1', $queryString);

        if (null === $queryString) {
            throw new ShouldNotHappenException('Could not strip trailing OFFSET from query');
        }

        return preg_replace('/\s*LIMIT\s+["\']?\d+["\']?\s*(,\s*["\']?\d*["\']?)?\s*$/i', '', $queryString);
    }
}
