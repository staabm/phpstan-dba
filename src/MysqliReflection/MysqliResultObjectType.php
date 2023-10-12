<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\MysqliReflection;

use mysqli_result;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type;

final class MysqliResultObjectType extends GenericObjectType
{
    /**
     * @param array<int, Type> $types
     */
    public function __construct(array $types, ?Type $subtractedType = null)
    {
        parent::__construct(mysqli_result::class, $types, $subtractedType);
    }

    public function getTypeWithoutSubtractedType(): Type
    {
        $withoutSubtracted = $this->changeSubtractedType(null);
        if ($withoutSubtracted instanceof GenericObjectType) {
            return new self(
                $withoutSubtracted->getTypes(),
                $withoutSubtracted->getSubtractedType(),
            );
        }
        return $withoutSubtracted;
    }

    public function equals(Type $type): bool
    {
        if ($type instanceof self) {
            return false;
        }

        return parent::equals($type);
    }

    public function isSuperTypeOf(Type $type): TrinaryLogic
    {
        if ($type instanceof self) {
            return TrinaryLogic::createFromBoolean($this->equals($type));
        }

        return parent::isSuperTypeOf($type);
    }
}
