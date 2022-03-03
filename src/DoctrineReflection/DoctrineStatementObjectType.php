<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\DoctrineReflection;

use Doctrine\DBAL\Statement;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type;

final class DoctrineStatementObjectType extends GenericObjectType
{
    public function __construct(Type $rowType)
    {
        parent::__construct(Statement::class, [$rowType]);
    }
}
