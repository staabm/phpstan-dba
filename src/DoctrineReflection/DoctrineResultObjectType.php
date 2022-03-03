<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\DoctrineReflection;

use Doctrine\DBAL\Result;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type;

final class DoctrineResultObjectType extends GenericObjectType
{
    public function __construct(Type $rowType)
    {
        parent::__construct(Result::class, [$rowType]);
    }

    public function getRowType(): Type
    {
        $genericTypes = $this->getTypes();

        return $genericTypes[0];
    }
}
