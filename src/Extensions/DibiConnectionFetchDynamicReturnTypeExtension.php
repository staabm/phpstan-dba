<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use staabm\PHPStanDba\DibiReflection\DibiReflection;
use staabm\PHPStanDba\QueryReflection\DbaApi;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflector;
use staabm\PHPStanDba\UnresolvableQueryException;

final class DibiConnectionFetchDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return \Dibi\Connection::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return \in_array($methodReflection->getName(), [
            'fetch',
            'fetchAll',
            'fetchPairs',
            'fetchSingle',
        ], true);
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
    {
        $args = $methodCall->getArgs();

        if (\count($args) < 1) {
            return null;
        }

        if ($scope->getType($args[0]->value) instanceof MixedType) {
            return null;
        }

        try {
            return $this->inferType($methodReflection, $args[0]->value, $scope);
        } catch (UnresolvableQueryException $exception) {
            // simulation not possible.. use default value
        }

        return null;
    }

    private function inferType(MethodReflection $methodReflection, Expr $queryExpr, Scope $scope): ?Type
    {
        $queryReflection = new QueryReflection(new DbaApi(DbaApi::API_DIBI));
        $queryStrings = $queryReflection->resolveQueryStrings($queryExpr, $scope);

        return $this->createFetchType($queryStrings, $methodReflection);
    }

    /**
     * @param iterable<string> $queryStrings
     */
    private function createFetchType(iterable $queryStrings, MethodReflection $methodReflection): ?Type
    {
        $queryReflection = new QueryReflection(new DbaApi(DbaApi::API_DIBI));
        $dibiReflection = new DibiReflection();

        $fetchTypes = [];
        foreach ($queryStrings as $queryString) {
            $queryString = $dibiReflection->rewriteQuery($queryString);
            if (null === $queryString) {
                continue;
            }

            $resultType = $queryReflection->getResultType($queryString, QueryReflector::FETCH_TYPE_ASSOC);

            if (null === $resultType) {
                return null;
            }

            $fetchResultType = $this->reduceResultType($methodReflection, $resultType);
            if (null === $fetchResultType) {
                return null;
            }

            $fetchTypes[] = $fetchResultType;
        }

        if (\count($fetchTypes) > 1) {
            return TypeCombinator::union(...$fetchTypes);
        }
        if (1 === \count($fetchTypes)) {
            return $fetchTypes[0];
        }

        return null;
    }

    private function reduceResultType(MethodReflection $methodReflection, Type $resultType): ?Type
    {
        $methodName = $methodReflection->getName();

        if ('fetch' === $methodName) {
            return TypeCombinator::addNull($resultType);
        } elseif ('fetchAll' === $methodName) {
            return new ArrayType(new IntegerType(), $resultType);
        } elseif ('fetchPairs' === $methodName && $resultType instanceof ConstantArrayType && 2 === \count($resultType->getValueTypes())) {
            return new ArrayType($resultType->getValueTypes()[0], $resultType->getValueTypes()[1]);
        } elseif ('fetchSingle' === $methodName && $resultType instanceof ConstantArrayType && 1 === \count($resultType->getValueTypes())) {
            return TypeCombinator::addNull($resultType->getValueTypes()[0]);
        }

        return null;
    }
}
