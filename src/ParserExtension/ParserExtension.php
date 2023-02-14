<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\ParserExtension;

use PHPStan\Type\Type;
use SqlFtw\Sql\Expression\ExpressionNode;

interface ParserExtension
{
    public function isExpressionSupported(ExpressionNode $expression): bool;

    public function getTypeFromExpression(ExpressionNode $expression): ?Type;
}
