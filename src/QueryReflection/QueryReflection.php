<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;

final class QueryReflection
{
    /**
     * @var QueryReflector
     */
    private $reflector;

    public function __construct()
    {
        $this->reflector = new MysqliQueryReflector();
    }

    public function containsSyntaxError(Expr $expr, Scope $scope): bool
    {
        $queryString = $this->builtSimulatedQuery($expr, $scope);

        if (null === $queryString) {
            return false;
        }

        return $this->reflector->containsSyntaxError($queryString);
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

        return $this->reflector->getResultType($queryString, $fetchType);
    }

    private function builtSimulatedQuery(Expr $expr, Scope $scope): ?string
    {
        $queryString = $this->resolveQueryString($expr, $scope);

        if ('SELECT' !== $this->getQueryType($queryString)) {
            return null;
        }

        $queryString = $this->stripTraillingLimit($queryString);
        $queryString .= ' LIMIT 0';

        return $queryString;
    }

    private function resolveQueryString(Expr $expr, Scope $scope): string
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

        $stringType = new StringType();
        if ($stringType->isSuperTypeOf($type)->yes()) {
            return '1=1';
        }

        $floatType = new FloatType();
        if ($floatType->isSuperTypeOf($type)->yes()) {
            return '1.0';
        }

        throw new \Exception(sprintf('Unexpected expression type %s', \get_class($type)));
    }

    private function getQueryType(string $query): ?string
    {
        $query = ltrim($query);

        if (preg_match('/^\s*\(?\s*(SELECT|SHOW|UPDATE|INSERT|DELETE|REPLACE|CREATE|CALL|OPTIMIZE)/i', $query, $matches)) {
            return strtoupper($matches[1]);
        }

        return null;
    }

    private function stripTraillingLimit(string $query): string
    {
        return preg_replace('/\s*LIMIT\s+\d+\s*(,\s*\d*)?$/i', '', $query);
    }
}
