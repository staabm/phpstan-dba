<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PHPStan\Analyser\Scope;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use staabm\PHPStanDba\DbaException;
use staabm\PHPStanDba\Error;

final class QueryReflection
{
    public const REGEX_PLACEHOLDER = '{:[a-zA-Z0-9_]+}';

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
        if ('SELECT' !== $this->getQueryType($queryString)) {
            return null;
        }

        // this method cannot validate queries which contain placeholders.
        if (0 !== $this->countPlaceholders($queryString)) {
            return null;
        }

        return self::reflector()->validateQueryString($queryString);
    }

    /**
     * @param QueryReflector::FETCH_TYPE* $fetchType
     */
    public function getResultType(string $queryString, int $fetchType): ?Type
    {
        if ('SELECT' !== $this->getQueryType($queryString)) {
            return null;
        }

        return self::reflector()->getResultType($queryString, $fetchType);
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

    /**
     * @return iterable<string>
     */
    public function resolveQueryStrings(Expr $queryExpr, Scope $scope): iterable
    {
        $type = $scope->getType($queryExpr);

        if ($type instanceof UnionType) {
            foreach (TypeUtils::getConstantStrings($type) as $constantString) {
                yield $constantString->getValue();
            }
        }

        $queryString = $this->resolveQueryString($queryExpr, $scope);
        if (null !== $queryString) {
            yield $queryString;
        }
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

        return QuerySimulation::simulateParamValueType($type);
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

                    $parameters[$placeholderName] = QuerySimulation::simulateParamValueType($valueTypes[$i]);
                } elseif ($keyType instanceof ConstantIntegerType) {
                    $parameters[$keyType->getValue()] = QuerySimulation::simulateParamValueType($valueTypes[$i]);
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

    /**
     * @return 0|positive-int
     */
    public function countPlaceholders(string $queryString): int
    {
        $numPlaceholders = substr_count($queryString, '?');

        if (0 !== $numPlaceholders) {
            return $numPlaceholders;
        }

        $numPlaceholders = preg_match_all(self::REGEX_PLACEHOLDER, $queryString);
        if (false === $numPlaceholders || $numPlaceholders < 0) {
            throw new ShouldNotHappenException();
        }

        return $numPlaceholders;
    }

    /**
     * @return list<string>
     */
    public function extractNamedPlaceholders(string $queryString): array
    {
        // pdo does not support mixing of named and '?' placeholders
        $numPlaceholders = substr_count($queryString, '?');

        if (0 !== $numPlaceholders) {
            return [];
        }

        if (preg_match_all(self::REGEX_PLACEHOLDER, $queryString, $matches) > 0) {
            return $matches[0];
        }

        return [];
    }
}
