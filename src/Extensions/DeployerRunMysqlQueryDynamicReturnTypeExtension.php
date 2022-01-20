<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflector;

final class DeployerRunMysqlQueryDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{
    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return 'deployer\runmysqlquery' === strtolower($functionReflection->getName());
    }

    public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
    {
        $args = $functionCall->getArgs();
        $defaultReturn = ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();

        if (\count($args) < 2) {
            return $defaultReturn;
        }

        if ($scope->getType($args[0]->value) instanceof MixedType) {
            return $defaultReturn;
        }

        $queryReflection = new QueryReflection();
        $queryString = $queryReflection->resolveQueryString($args[0]->value, $scope);
        if (null === $queryString) {
            return $defaultReturn;
        }

        $resultType = $queryReflection->getResultType($queryString, QueryReflector::FETCH_TYPE_NUMERIC);
        if ($resultType instanceof ConstantArrayType) {
            $builder = ConstantArrayTypeBuilder::createEmpty();
            foreach ($resultType->getKeyTypes() as $keyType) {
                $builder->setOffsetValueType($keyType, new StringType());
            }

            return TypeCombinator::addNull(new ArrayType(new IntegerType(), $builder->getArray()));
        }

        return $defaultReturn;
    }
}
