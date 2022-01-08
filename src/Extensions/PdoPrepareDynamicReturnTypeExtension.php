<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use PDO;
use PDOStatement;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflector;

final class PdoPrepareDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return PDO::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return 'prepare' === $methodReflection->getName();
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        $args = $methodCall->getArgs();
        $defaultReturn = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

        if (\count($args) < 1) {
            return $defaultReturn;
        }

        $queryReflection = new QueryReflection();
        $queryString = $queryReflection->resolveQueryString($args[0]->value, $scope);
        if (null === $queryString) {
            return $defaultReturn;
        }

        $reflectionFetchType = QueryReflector::FETCH_TYPE_BOTH;
        $resultType = $queryReflection->getResultType($queryString, $reflectionFetchType);
        if ($resultType) {
            return new GenericObjectType(PDOStatement::class, [$resultType]);
        }

        return $defaultReturn;
    }
}
