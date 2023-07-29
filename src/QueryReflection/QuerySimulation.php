<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Accessory\AccessoryType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use staabm\PHPStanDba\DbaException;
use staabm\PHPStanDba\UnresolvableQueryMixedTypeException;
use staabm\PHPStanDba\UnresolvableQueryStringTypeException;

/**
 * @internal
 */
final class QuerySimulation
{
    private const DATE_FORMAT = 'Y-m-d';

    /**
     * @throws \staabm\PHPStanDba\UnresolvableQueryException
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

        // TODO the dateformat should be taken from bound-parameter-types, see https://github.com/staabm/phpstan-dba/pull/342
        if ($paramType instanceof ObjectType && $paramType->isInstanceOf(\DateTimeInterface::class)->yes()) {
            return date(self::DATE_FORMAT, 0);
        }

        $stringType = new StringType();
        $isStringableObjectType = $paramType instanceof ObjectType
            && ! $paramType->toString() instanceof ErrorType;
        if (
            $stringType->isSuperTypeOf($paramType)->yes()
            || $isStringableObjectType
        ) {
            // in a prepared context, regular strings are fine
            if (true === $preparedParam) {
                // returns a string in date-format, so in case the simulated value is used against a date/datetime column
                // we won't run into a sql error.
                // XX in case we would have a proper sql parser, we could feed schema-type-dependent default values in case of strings.
                return date(self::DATE_FORMAT, 0);
            }

            // plain string types can contain anything.. we cannot reason about it
            if (QueryReflection::getRuntimeConfiguration()->isDebugEnabled()) {
                throw new UnresolvableQueryStringTypeException('Cannot resolve query with variable type: ' . $paramType->describe(VerbosityLevel::precise()));
            }

            return null;
        }

        if ($paramType instanceof IntersectionType) {
            foreach ($paramType->getTypes() as $type) {
                if ($type instanceof AccessoryType) {
                    continue;
                }

                $simulated = self::simulateParamValueType($type, $preparedParam);
                if (null !== $simulated) {
                    return $simulated;
                }
            }
        }

        // all types which we can't simulate and render a query unresolvable at analysis time
        if ($paramType instanceof MixedType || $paramType instanceof IntersectionType) {
            if (QueryReflection::getRuntimeConfiguration()->isDebugEnabled()) {
                throw new UnresolvableQueryMixedTypeException('Cannot simulate parameter value for type: ' . $paramType->describe(VerbosityLevel::precise()));
            }

            return null;
        }

        throw new DbaException(sprintf('Unexpected expression type %s of class %s', $paramType->describe(VerbosityLevel::precise()), \get_class($paramType)));
    }

    public static function simulate(string $queryString): ?string
    {
        $queryString = self::stripTrailers(self::stripComments($queryString));

        if (null === $queryString) {
            return null;
        }

        // make sure we don't unnecessarily transfer data, as we are only interested in the statement is succeeding
        if ('SELECT' === QueryReflection::getQueryType($queryString)) {
            $queryString .= ' LIMIT 0';
        }

        return $queryString;
    }

    public static function stripTrailers(string $queryString): ?string
    {
        // XXX someday we will use a proper SQL parser
        $queryString = rtrim($queryString);

        // strip trailing delimiting semicolon
        $queryString = rtrim($queryString, ';');

        // strip trailing FOR UPDATE/FOR SHARE
        $queryString = preg_replace('/(.*)FOR (UPDATE|SHARE)\s*(SKIP\s+LOCKED|NOWAIT)?$/i', '$1', $queryString);

        if (null === $queryString) {
            throw new ShouldNotHappenException('Could not strip trailing FOR UPDATE/SHARE from query');
        }

        // strip trailing OFFSET
        $queryString = preg_replace('/(.*)OFFSET\s+["\']?\d+["\']?\s*$/i', '$1', $queryString);

        if (null === $queryString) {
            throw new ShouldNotHappenException('Could not strip trailing OFFSET from query');
        }

        return preg_replace('/\s*LIMIT\s+["\']?\d+["\']?\s*(,\s*["\']?\d*["\']?)?\s*$/i', '', $queryString);
    }

    /**
     * @see https://larrysteinle.com/2011/02/09/use-regular-expressions-to-clean-sql-statements/
     * @see https://github.com/decemberster/sql-strip-comments/blob/3bef3558211a6f6191d2ad0ceb8577eda39dd303/index.js
     */
    public static function stripComments(string $query): string
    {
        return trim(preg_replace_callback(
            '/("(""|[^"])*")|(\'(\'\'|[^\'])*\')|(--[^\n\r]*)|(\/\*[\w\W]*?(?=\*\/)\*\/)/m',
            static function (array $matches): string {
                $match = $matches[0];
                $matchLength = \strlen($match);
                if (
                    ('"' === $match[0] && '"' === $match[$matchLength - 1])
                    || ('\'' === $match[0] && '\'' === $match[$matchLength - 1])
                ) {
                    return $match;
                }

                return '';
            },
            $query
        ) ?? $query);
    }
}
