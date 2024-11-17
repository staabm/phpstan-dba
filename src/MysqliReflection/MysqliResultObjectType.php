<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\MysqliReflection;

use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

final class MysqliResultObjectType extends ObjectType
{
    private ?Type $rowType = null;

    public function setRowType(Type $rowType): void
    {
        $this->rowType = $rowType;
    }

    public function getIterableValueType(): \PHPStan\Type\Type
    {
        if ($this->rowType !== null) {
            return $this->rowType;
        }

        return parent::getIterableValueType();
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
