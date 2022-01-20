<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use Doctrine\DBAL\Result;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerRangeType;
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
        return \in_array(strtolower($methodReflection->getName()), ['columncount', 'fetchnumeric', 'fetchassociative', 'fetchallnumeric', 'fetchallassociative'], true);
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        $defaultReturn = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

        // make sure we don't report wrong types in doctrine 2.x
        if (!InstalledVersions::satisfies(new VersionParser(), 'doctrine/dbal', '3.*')) {
            return $defaultReturn;
        }

        $resultType = $scope->getType($methodCall->var);
        if (!$resultType instanceof GenericObjectType) {
            return $defaultReturn;
        }

        $genericTypes = $resultType->getTypes();

        if (1 !== \count($genericTypes)) {
            return $defaultReturn;
        }

        $resultRowType = $genericTypes[0];

        switch (strtolower($methodReflection->getName())) {
            case 'columncount':
                if ($resultRowType instanceof ConstantArrayType) {
                    $columnCount = \count($resultRowType->getKeyTypes()) / 2;
                    if (!\is_int($columnCount)) {
                        throw new ShouldNotHappenException();
                    }

                    return new ConstantIntegerType($columnCount);
                }

                return $defaultReturn;

            case 'fetchnumeric':
            case 'fetchallnumeric':
                $fetchType = QueryReflector::FETCH_TYPE_NUMERIC;
                break;
            case 'fetchassociative':
            case 'fetchallassociative':
                $fetchType = QueryReflector::FETCH_TYPE_ASSOC;
                break;
            default:
                $fetchType = QueryReflector::FETCH_TYPE_BOTH;
        }

        if ((QueryReflector::FETCH_TYPE_NUMERIC === $fetchType || QueryReflector::FETCH_TYPE_ASSOC === $fetchType) && $resultRowType instanceof ConstantArrayType) {
            $builder = ConstantArrayTypeBuilder::createEmpty();

            $keyTypes = $resultRowType->getKeyTypes();
            $valueTypes = $resultRowType->getValueTypes();

            foreach ($keyTypes as $i => $keyType) {
                if (QueryReflector::FETCH_TYPE_NUMERIC === $fetchType && $keyType instanceof ConstantIntegerType) {
                    $builder->setOffsetValueType($keyType, $valueTypes[$i]);
                } elseif (QueryReflector::FETCH_TYPE_ASSOC === $fetchType && $keyType instanceof ConstantStringType) {
                    $builder->setOffsetValueType($keyType, $valueTypes[$i]);
                }
            }

            if (\in_array(strtolower($methodReflection->getName()), ['fetchallnumeric', 'fetchallassociative'], true)) {
                return new ArrayType(IntegerRangeType::fromInterval(0, null), $builder->getArray());
            }

            return $builder->getArray();
        }

        return $defaultReturn;
    }
}
