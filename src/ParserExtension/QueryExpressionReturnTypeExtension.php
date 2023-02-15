<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\ParserExtension;

use PHPStan\Type\Type;
use SqlFtw\Sql\Expression\ExpressionNode;

/** @template TExpression of ExpressionNode */
interface QueryExpressionReturnTypeExtension
{
    public function isExpressionSupported(ExpressionNode $expression): bool;

    /** @param TExpression $expression */
    public function getTypeFromExpression(ExpressionNode $expression, QueryScope $scope): ?Type;
}
