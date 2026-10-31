<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use mysqli_result;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use staabm\PHPStanDba\MysqliReflection\MysqliResultObjectType;

final class MysqliResultDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return mysqli_result::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return 'fetch_assoc' === $methodReflection->getName();
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
    {
        $resultType = $scope->getType($methodCall->var);
        if (count($methodCall->getArgs()) === 0) {
            return $this->getRowType($resultType);
        }
        return null;
    }

    private function getRowType(Type $resultType): ?Type
    {
        if ($resultType instanceof MysqliResultObjectType) {
            return $resultType->getRowType();
        }

        if (! $resultType instanceof UnionType) {
            return null;
        }

        $rowTypes = [];
        foreach ($resultType->getTypes() as $type) {
            $rowType = $this->getRowType($type);
            if ($rowType === null) {
                return null;
            }

            $rowTypes[] = $rowType;
        }

        return TypeCombinator::union(...$rowTypes);
    }
}
