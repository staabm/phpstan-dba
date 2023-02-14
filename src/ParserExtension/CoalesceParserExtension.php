<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\ParserExtension;

use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use SqlFtw\Sql\Expression\BuiltInFunction;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\Expression\FunctionCall;

/**
 * @implements ParserExtension<FunctionCall>
 */
final class CoalesceParserExtension implements ParserExtension
{
    public function isExpressionSupported(ExpressionNode $expression): bool
    {
        return $expression instanceof FunctionCall && BuiltInFunction::COALESCE == $expression->getFunction()->getName();
    }

    public function getTypeFromExpression(ExpressionNode $expression, QueryScope $scope): Type
    {
        $args = $expression->getArguments();

        $results = [];
        $containsNonNullable = false;
        foreach ($args as $arg) {
            $argType = $scope->getType($arg);

            $results[] = $argType;
            if (!TypeCombinator::containsNull($argType)) {
                $containsNonNullable = true;
            }
        }

        $union = TypeCombinator::union(...$results);
        if ($containsNonNullable) {
            return TypeCombinator::removeNull($union);
        }

        return $union;
    }
}
