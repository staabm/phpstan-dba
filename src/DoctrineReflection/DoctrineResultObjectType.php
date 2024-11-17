<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\DoctrineReflection;

use Doctrine\DBAL\Result;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

final class DoctrineResultObjectType extends ObjectType
{
    private ?Type $rowType = null;

    public static function newWithRowType(Type $rowType): self
    {
        $new = new self(Result::class);
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

    public function getIterableValueType(): Type
    {
        return $this->getRowType();
    }

    // differentiate objects based on the local properties,
    // to make sure TypeCombinator::union() will not normalize separate objects away.
    // this means we need to implement equals() and isSuperTypeOf().
    public function equals(Type $type): bool
    {
        if ($type instanceof self
        ) {
            return $type->rowType !== null
                && $this->rowType !== null
                && $type->rowType === $this->rowType
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
