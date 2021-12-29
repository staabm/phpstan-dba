<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use PDO;
use PDOStatement;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use staabm\PHPStanDba\QueryReflection\QueryReflection;

final class PdoQueryDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return PDO::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return 'query' === $methodReflection->getName();
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        $args = $methodCall->getArgs();
        $mixed = new MixedType(true);

        $defaultReturn = TypeCombinator::union(
            new GenericObjectType(PDOStatement::class, [new ArrayType($mixed, $mixed)]),
            new ConstantBooleanType(false)
        );

        if (\count($args) < 2) {
            return $defaultReturn;
        }

        $fetchModeType = $scope->getType($args[1]->value);
        if (!$fetchModeType instanceof ConstantIntegerType || PDO::FETCH_ASSOC !== $fetchModeType->getValue()) {
            return $defaultReturn;
        }

        $queryReflection = new QueryReflection();
        $resultType = $queryReflection->getResultType($args[0]->value, $scope, QueryReflection::FETCH_TYPE_ASSOC);
        if ($resultType) {
            return new GenericObjectType(PDOStatement::class, [$resultType]);
        }

        return $defaultReturn;
    }
}
