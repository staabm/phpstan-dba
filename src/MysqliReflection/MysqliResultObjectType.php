<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\MysqliReflection;

use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

final class MysqliResultObjectType extends ObjectType
{
    // same-value replacements for MYSQLI_ASSOC, MYSQLI_NUM, MYSQLI_BOTH constants in mysqli extension
    private const MYSQLI_ASSOC = 1;

    private const MYSQLI_NUM = 2;

    private const MYSQLI_BOTH = 3;

    private ?Type $rowType = null;

    public static function newWithRowType(Type $rowType): self
    {
        $new = new self(\mysqli_result::class);
        $new->rowType = $rowType;
        return $new;
    }

    public function getRowType(): Type
    {
        if ($this->rowType === null) {
            throw new ShouldNotHappenException();
        }

        return $this->rowType;
    }

    public static function getRowTypeForMode(Type $rowType, int $mode = self::MYSQLI_NUM): ?Type
    {
        if (! \in_array($mode, [self::MYSQLI_NUM, self::MYSQLI_ASSOC, self::MYSQLI_BOTH], true)) {
            return null;
        }

        $rowTypes = [];
        foreach ($rowType->getConstantArrays() as $arrayType) {
            $builder = ConstantArrayTypeBuilder::createEmpty();
            $numericIndex = 0;

            foreach ($arrayType->getKeyTypes() as $index => $keyType) {
                $valueType = $arrayType->getValueTypes()[$index];
                if ($mode === self::MYSQLI_ASSOC || $mode === self::MYSQLI_BOTH) {
                    $builder->setOffsetValueType($keyType, $valueType);
                }

                if ($mode === self::MYSQLI_NUM || $mode === self::MYSQLI_BOTH) {
                    $builder->setOffsetValueType(new ConstantIntegerType($numericIndex), $valueType);
                    $numericIndex++;
                }
            }
            $rowTypes[] = $builder->getArray();
        }

        if (count($rowTypes) === 0) {
            return null;
        }

        return TypeCombinator::union(...$rowTypes);
    }

    public function getIterableValueType(): Type
    {
        return $this->getRowType();
    }

    // differentiate objects based on the local properties,
    // to make sure TypeCombinator::union() will not normalize separate objects away.
    // this means we need to implement equals() and isSuperTypeOf().
    public function equals(Type $type): bool
    {
        if ($type instanceof self) {
            return $type->rowType !== null
                && $this->rowType !== null
                && $type->rowType->equals($this->rowType);
        }

        return parent::equals($type);
    }

    public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
    {
        if ($type instanceof self) {
            return IsSuperTypeOfResult::createFromBoolean(
                $type->rowType !== null
                && $this->rowType !== null
                && $type->rowType->equals($this->rowType)
            );
        }

        return parent::isSuperTypeOf($type);
    }
}
