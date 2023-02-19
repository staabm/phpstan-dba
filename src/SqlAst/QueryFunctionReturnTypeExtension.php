<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\SqlAst;

use PHPStan\Type\Type;
use SqlFtw\Sql\Expression\FunctionCall;

interface QueryFunctionReturnTypeExtension
{
    public function isFunctionSupported(FunctionCall $expression): bool;

    public function getReturnType(FunctionCall $expression, QueryScope $scope): ?Type;
}
