<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PHPStan\Analyser\Scope;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use staabm\PHPStanDba\DbaException;

final class QueryReflection
{
    /**
     * @var QueryReflector|null
     */
    private static $reflector;

    public static function setupReflector(QueryReflector $reflector): void
    {
        self::$reflector = $reflector;
    }

    public function containsSyntaxError(Expr $expr, Scope $scope): bool
    {
        $queryString = $this->builtSimulatedQuery($expr, $scope);

        if (null === $queryString) {
            return false;
        }

        return self::reflector()->containsSyntaxError($queryString);
    }

    /**
     * @param QueryReflector::FETCH_TYPE* $fetchType
     */
    public function getResultType(Expr $expr, Scope $scope, int $fetchType): ?Type
    {
        $queryString = $this->builtSimulatedQuery($expr, $scope);

        if (null === $queryString) {
            return null;
        }

        return self::reflector()->getResultType($queryString, $fetchType);
    }

    private function builtSimulatedQuery(Expr $expr, Scope $scope): ?string
    {
        $queryString = $this->resolveQueryString($expr, $scope);

        if (null === $queryString) {
            return null;
        }

        if ('SELECT' !== $this->getQueryType($queryString)) {
            return null;
        }

        $queryString = $this->stripTraillingLimit($queryString);
        if (null === $queryString) {
            return null;
        }
        $queryString .= ' LIMIT 0';

        return $queryString;
    }

    private function resolveQueryString(Expr $expr, Scope $scope): ?string
    {
        if ($expr instanceof Concat) {
            $left = $expr->left;
            $right = $expr->right;

            $leftString = $this->resolveQueryString($left, $scope);
            $rightString = $this->resolveQueryString($right, $scope);

            if ($leftString && $rightString) {
                return $leftString.$rightString;
            }
            if ($leftString) {
                return $leftString;
            }
            if ($rightString) {
                return $rightString;
            }
        }

        $type = $scope->getType($expr);
        if ($type instanceof ConstantStringType) {
            return $type->getValue();
        }

        $integerType = new IntegerType();
        if ($integerType->isSuperTypeOf($type)->yes()) {
            return '1';
        }

        $booleanType = new BooleanType();
        if ($booleanType->isSuperTypeOf($type)->yes()) {
            return '1';
        }

        $stringType = new StringType();
        if ($stringType->isSuperTypeOf($type)->yes()) {
            return '1=1';
        }

        $floatType = new FloatType();
        if ($floatType->isSuperTypeOf($type)->yes()) {
            return '1.0';
        }

        if ($type instanceof MixedType) {
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

    private function stripTraillingLimit(string $query): ?string
    {
        return preg_replace('/\s*LIMIT\s+\d+\s*(,\s*\d*)?$/i', '', $query);
    }

    private function reflector(): QueryReflector
    {
        if (null === self::$reflector) {
            throw new DbaException('Reflector not initialized, call '.__CLASS__.'::setupReflector() first');
        }

        return self::$reflector;
    }
}
