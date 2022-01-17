<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use Doctrine\DBAL\Result;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type;
use staabm\PHPStanDba\QueryReflection\QueryReflector;

final class DoctrineResultDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Result::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return \in_array(strtolower($methodReflection->getName()), ['fetchnumeric', 'fetchassociative'], true);
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        $defaultReturn = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

        $resultType = $scope->getType($methodCall->var);

        if (!$resultType instanceof GenericObjectType) {
            return $defaultReturn;
        }

        $genericTypes = $resultType->getTypes();

        if (1 !== \count($genericTypes)) {
            return $defaultReturn;
        }

        switch (strtolower($methodReflection->getName())) {
            case 'fetchnumeric':
                $fetchType = QueryReflector::FETCH_TYPE_NUMERIC;
                break;
            case 'fetchassociative':
                $fetchType = QueryReflector::FETCH_TYPE_ASSOC;
                break;
            default:
                $fetchType = QueryReflector::FETCH_TYPE_BOTH;
        }

        $resultType = $genericTypes[0];

        if ((QueryReflector::FETCH_TYPE_NUMERIC === $fetchType || QueryReflector::FETCH_TYPE_ASSOC === $fetchType) && $resultType instanceof ConstantArrayType) {
            $builder = ConstantArrayTypeBuilder::createEmpty();

            $keyTypes = $resultType->getKeyTypes();
            $valueTypes = $resultType->getValueTypes();

            foreach ($keyTypes as $i => $keyType) {
                if (QueryReflector::FETCH_TYPE_NUMERIC === $fetchType && $keyType instanceof ConstantIntegerType) {
                    $builder->setOffsetValueType($keyType, $valueTypes[$i]);
                } elseif (QueryReflector::FETCH_TYPE_ASSOC === $fetchType && $keyType instanceof ConstantStringType) {
                    $builder->setOffsetValueType($keyType, $valueTypes[$i]);
                }
            }

            return $builder->getArray();
        }

        return $resultType;
    }
}
