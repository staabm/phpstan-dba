<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use PDOStatement;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use staabm\PHPStanDba\PdoReflection\PdoStatementReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflection;

final class PdoStatementFetchDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

    public function getClass(): string
    {
        return PDOStatement::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return \in_array($methodReflection->getName(), ['fetchAll', 'fetch'], true);
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        $defaultReturn = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

        $resultType = $this->inferType($methodReflection, $methodCall, $scope);
        if (null !== $resultType) {
            return $resultType;
        }

        return $defaultReturn;
    }

    private function inferType(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
    {
        $args = $methodCall->getArgs();
        $pdoStatementReflection = new PdoStatementReflection();

        $statementType = $scope->getType($methodCall->var);
        if (!$statementType instanceof GenericObjectType) {
            return null;
        }

        $fetchType = QueryReflection::getRuntimeConfiguration()->getDefaultFetchMode();
        if (\count($args) > 0) {
            $fetchModeType = $scope->getType($args[0]->value);
            $fetchType = $pdoStatementReflection->getFetchType($fetchModeType);

            if (null === $fetchType) {
                return null;
            }
        }

        $rowType = $pdoStatementReflection->getRowType($statementType, $fetchType);
        if (null === $rowType) {
            return null;
        }

        if ('fetchAll' === $methodReflection->getName()) {
            return new ArrayType(new IntegerType(), $rowType);
        }

        return new UnionType([$rowType, new ConstantBooleanType(false)]);
    }
}
