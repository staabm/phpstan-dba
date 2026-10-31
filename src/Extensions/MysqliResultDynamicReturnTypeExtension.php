<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use mysqli_result;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\IntegerType;
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
        return \in_array(strtolower($methodReflection->getName()), ['fetch_assoc', 'fetch_all'], true);
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
    {
        $resultType = $scope->getType($methodCall->var);
        $methodName = strtolower($methodReflection->getName());
        if ('fetch_assoc' === $methodName) {
            if (count($methodCall->getArgs()) === 0) {
                return $this->getRowType($resultType);
            }

            return null;
        }

        $modes = [\MYSQLI_NUM];
        if (count($methodCall->getArgs()) > 0) {
            $modes = $this->getModes($scope->getType($methodCall->getArgs()[0]->value));
            if ($modes === null) {
                return null;
            }
        }

        $rowType = $this->getRowType($resultType);
        if ($rowType === null) {
            return null;
        }

        $rows = [];
        foreach ($modes as $mode) {
            $modeRowType = $this->getRowTypeForMode($rowType, $mode);
            if ($modeRowType === null) {
                return null;
            }

            $rows[] = $modeRowType;
        }

        return TypeCombinator::intersect(
            new ArrayType(new IntegerType(), TypeCombinator::union(...$rows)),
            new AccessoryArrayListType()
        );
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

    /**
     * @return list<int>|null
     */
    private function getModes(Type $modeType): ?array
    {
        if ($modeType instanceof ConstantIntegerType) {
            return [$modeType->getValue()];
        }

        if (! $modeType instanceof UnionType) {
            return null;
        }

        $modes = [];
        foreach ($modeType->getTypes() as $type) {
            $typeModes = $this->getModes($type);
            if ($typeModes === null) {
                return null;
            }

            foreach ($typeModes as $mode) {
                $modes[] = $mode;
            }
        }

        return $modes;
    }

    private function getRowTypeForMode(Type $rowType, int $mode): ?Type
    {
        if (! \in_array($mode, [\MYSQLI_NUM, \MYSQLI_ASSOC, \MYSQLI_BOTH], true)) {
            return null;
        }

        $rowTypes = [];
        foreach ($rowType->getConstantArrays() as $arrayType) {
            $rowTypes[] = $this->convertRowType($arrayType, $mode);
        }

        if (count($rowTypes) === 0) {
            return null;
        }

        return TypeCombinator::union(...$rowTypes);
    }

    private function convertRowType(ConstantArrayType $rowType, int $mode): ConstantArrayType
    {
        $builder = ConstantArrayTypeBuilder::createEmpty();
        $numericIndex = 0;

        foreach ($rowType->getKeyTypes() as $index => $keyType) {
            $valueType = $rowType->getValueTypes()[$index];
            if ($mode === \MYSQLI_ASSOC || $mode === \MYSQLI_BOTH) {
                $builder->setOffsetValueType($keyType, $valueType);
            }

            if ($mode === \MYSQLI_NUM || $mode === \MYSQLI_BOTH) {
                $builder->setOffsetValueType(new ConstantIntegerType($numericIndex), $valueType);
                $numericIndex++;
            }
        }

        return $builder->getArray();
    }
}
