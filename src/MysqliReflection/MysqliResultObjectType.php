<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\MysqliReflection;

use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

final class MysqliResultObjectType extends ObjectType
{
    private ?Type $rowType;

    public function __construct(
        string $className,
        ?Type $subtractedType = null
    )
    {
        parent::__construct($className, $subtractedType);
    }

    public function setRowType(Type $rowType): void {
        $this->rowType = $rowType;
    }

    public function getRowType(): Type
    {
        if ($this->rowType === null) {
            throw new ShouldNotHappenException();
        }

        return $this->rowType;
    }

    public function getIterableValueType(): \PHPStan\Type\Type
    {
        if($this->rowType !== null) {
            return $this->rowType;
        }

        return parent::getIterableValueType();
    }
}
