<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\ParserExtension;

use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\Type;
use SqlFtw\Sql\Expression\BuiltInFunction;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\Expression\FunctionCall;

final class CoalesceParserExtension implements ParserExtension
{
    public function isExpressionSupported(ExpressionNode $expression): bool
    {
        return $expression instanceof FunctionCall && BuiltInFunction::COALESCE == $expression->getFunction()->getName();
    }

    public function getTypeFromExpression(ExpressionNode $expression): Type
    {
        return IntegerRangeType::fromInterval(0, null);
    }
}
