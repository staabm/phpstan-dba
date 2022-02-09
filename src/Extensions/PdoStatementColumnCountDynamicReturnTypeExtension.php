<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use PDOStatement;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type;
use staabm\PHPStanDba\PdoReflection\PdoStatementReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflector;

final class PdoStatementColumnCountDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return PDOStatement::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return \in_array($methodReflection->getName(), ['columnCount'], true);
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        $pdoStatementReflection = new PdoStatementReflection();
        $defaultReturn = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

        $statementType = $scope->getType($methodCall->var);

        if ($statementType instanceof GenericObjectType) {
            $rowType = $pdoStatementReflection->getRowType($statementType, QueryReflector::FETCH_TYPE_NUMERIC);
            if ($rowType instanceof ConstantArrayType) {
                return new ConstantIntegerType(\count($rowType->getKeyTypes()));
            }
        }

        return $defaultReturn;
    }
}
