<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
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

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        $args = $methodCall->getArgs();
        $defaultReturn = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $methodCall->getArgs(),
            $methodReflection->getVariants(),
        )->getReturnType();

        if (\count($args) < 1) {
            return $defaultReturn;
        }

        if ($scope->getType($args[0]->value) instanceof MixedType) {
            return $defaultReturn;
        }

        try {
            $resultType = $this->inferType($args[0]->value, $scope, $methodReflection->getName());
            if (null !== $resultType) {
                return $resultType;
            }
        } catch (UnresolvableQueryException $exception) {
            // simulation not possible.. use default value
        }

        return $defaultReturn;
    }

    private function inferType(Expr $queryExpr, Scope $scope, string $methodName): ?Type
    {
        $queryReflection = new QueryReflection();
        $resolvedQuery = $queryReflection->resolveQueryString($queryExpr, $scope);

        if (null === $resolvedQuery) {
            return null;
        }

        $result = $queryReflection->getResultType($resolvedQuery, QueryReflector::FETCH_TYPE_COLUMN);

        if ('fetch' === $methodName && null !== $result) {
            return new UnionType([new NullType(), $result]);
        } elseif ('fetchAll' === $methodName && null !== $result) {
            return new ArrayType(new IntegerType(), $result);
        } elseif ('fetchPairs' === $methodName && $result instanceof ConstantArrayType && 2 === \count($result->getValueTypes())) {
            return new ArrayType($result->getValueTypes()[0], $result->getValueTypes()[1]);
        } elseif ('fetchSingle' === $methodName && $result instanceof ConstantArrayType && 1 === \count($result->getValueTypes())) {
            return $result->getValueTypes()[0];
        }

        return null;
    }
}
