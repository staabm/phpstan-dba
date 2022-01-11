<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PHPStan\Analyser\Scope;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use staabm\PHPStanDba\DbaException;
use staabm\PHPStanDba\Error;

final class QueryReflection
{
    /**
     * @var QueryReflector|null
     */
    private static $reflector;
    /**
     * @var RuntimeConfiguration|null
     */
    private static $runtimeConfiguration;

    public static function setupReflector(QueryReflector $reflector, RuntimeConfiguration $runtimeConfiguration): void
    {
        self::$reflector = $reflector;
        self::$runtimeConfiguration = $runtimeConfiguration;
    }

    public function validateQueryString(string $queryString): ?Error
    {
        $queryString = $this->builtSimulatedQuery($queryString);

        if (null === $queryString) {
            return null;
        }

        return self::reflector()->validateQueryString($queryString);
    }

    /**
     * @param QueryReflector::FETCH_TYPE* $fetchType
     */
    public function getResultType(string $queryString, int $fetchType): ?Type
    {
        $queryString = $this->builtSimulatedQuery($queryString);

        if (null === $queryString) {
            return null;
        }

        return self::reflector()->getResultType($queryString, $fetchType);
    }

    private function builtSimulatedQuery(string $queryString): ?string
    {
        if ('SELECT' !== $this->getQueryType($queryString)) {
            return null;
        }

        return $queryString;
    }

    public function resolvePreparedQueryString(Expr $queryExpr, Type $parameterTypes, Scope $scope): ?string
    {
        $queryString = $this->resolveQueryString($queryExpr, $scope);

        if (null === $queryString) {
            return null;
        }

        $parameters = $this->resolveParameters($parameterTypes);
        if (null === $parameters) {
            return null;
        }

        return $this->replaceParameters($queryString, $parameters);
    }

    public function resolveQueryString(Expr $queryExpr, Scope $scope): ?string
    {
        if ($queryExpr instanceof Concat) {
            $left = $queryExpr->left;
            $right = $queryExpr->right;

            $leftString = $this->resolveQueryString($left, $scope);
            $rightString = $this->resolveQueryString($right, $scope);

            if (null === $leftString || null === $rightString) {
                return null;
            }

            return $leftString.$rightString;
        }

        $type = $scope->getType($queryExpr);
        if ($type instanceof ConstantScalarType) {
            return (string) $type->getValue();
        }

        $integerType = new IntegerType();
        if ($integerType->isSuperTypeOf($type)->yes()) {
            return '1';
        }

        $booleanType = new BooleanType();
        if ($booleanType->isSuperTypeOf($type)->yes()) {
            return '1';
        }

        if ($type->isNumericString()->yes()) {
            return '1';
        }

        $floatType = new FloatType();
        if ($floatType->isSuperTypeOf($type)->yes()) {
            return '1.0';
        }

        if ($type instanceof MixedType || $type instanceof StringType || $type instanceof IntersectionType || $type instanceof UnionType) {
            return null;
        }

        throw new DbaException(sprintf('Unexpected expression type %s', \get_class($type)));
    }

    private function getQueryType(string $query): ?string
    {
        $query = ltrim($query);

        if (preg_match('/^\s*\(?\s*(SELECT|SHOW|UPDATE|INSERT|DELETE|REPLACE|CREATE|CALL|OPTIMIZE)/i', $query, $matches)) {
            return strtoupper($matches[1]);
        }

        return null;
    }

    /**
     * @return array<string|int, scalar|null>
     */
    public function resolveParameters(Type $parameterTypes): ?array
    {
        $parameters = [];

        if ($parameterTypes instanceof ConstantArrayType) {
            $keyTypes = $parameterTypes->getKeyTypes();
            $valueTypes = $parameterTypes->getValueTypes();

            foreach ($keyTypes as $i => $keyType) {
                if ($keyType instanceof ConstantStringType) {
                    $placeholderName = $keyType->getValue();

                    if (!str_starts_with($placeholderName, ':')) {
                        $placeholderName = ':'.$placeholderName;
                    }

                    if ($valueTypes[$i] instanceof ConstantScalarType) {
                        $parameters[$placeholderName] = $valueTypes[$i]->getValue();
                    }
                } elseif ($keyType instanceof ConstantIntegerType) {
                    if ($valueTypes[$i] instanceof ConstantScalarType) {
                        $parameters[$keyType->getValue()] = $valueTypes[$i]->getValue();
                    }
                }
            }

            return $parameters;
        }

        return null;
    }

    /**
     * @param array<string|int, scalar|null> $parameters
     */
    private function replaceParameters(string $queryString, array $parameters): string
    {
        $replaceFirst = function (string $haystack, string $needle, string $replace) {
            $pos = strpos($haystack, $needle);
            if (false !== $pos) {
                return substr_replace($haystack, $replace, $pos, \strlen($needle));
            }

            return $haystack;
        };

        foreach ($parameters as $placeholderKey => $value) {
            if (\is_string($value)) {
                // XXX escaping
                $value = "'".$value."'";
            } elseif (null === $value) {
                $value = 'NULL';
            } else {
                $value = (string) $value;
            }

            if (\is_int($placeholderKey)) {
                $queryString = $replaceFirst($queryString, '?', $value);
            } else {
                $queryString = str_replace($placeholderKey, $value, $queryString);
            }
        }

        return $queryString;
    }

    private static function reflector(): QueryReflector
    {
        if (null === self::$reflector) {
            throw new DbaException('Reflector not initialized, call '.__CLASS__.'::setupReflector() first');
        }

        return self::$reflector;
    }

    public static function getRuntimeConfiguration(): RuntimeConfiguration
    {
        if (null === self::$runtimeConfiguration) {
            throw new DbaException('Runtime configuration not initialized, call '.__CLASS__.'::setupReflector() first');
        }

        return self::$runtimeConfiguration;
    }
}
