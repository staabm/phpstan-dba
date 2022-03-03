<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\MysqliReflection;

use mysqli_result;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type;

final class MysqliResultObjectType extends GenericObjectType
{
    public function __construct(Type $rowType)
    {
        parent::__construct(mysqli_result::class, [$rowType]);
    }
}
