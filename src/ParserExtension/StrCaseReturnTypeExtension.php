<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\ParserExtension;

use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use SqlFtw\Sql\Expression\BuiltInFunction;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\Expression\FunctionCall;

/**
 * @implements QueryExpressionReturnTypeExtension<FunctionCall>
 */
final class StrCaseReturnTypeExtension implements QueryExpressionReturnTypeExtension
{
    public function isExpressionSupported(ExpressionNode $expression): bool
    {
        return
            $expression instanceof FunctionCall
            && \in_array($expression->getFunction()->getName(), [BuiltInFunction::LOWER, BuiltInFunction::UPPER], true);
    }

    public function getTypeFromExpression(ExpressionNode $expression, QueryScope $scope): ?Type
    {
        $args = $expression->getArguments();

        if (1 !== \count($args)) {
            return null;
        }

        $argType = $scope->getType($args[0]);

        if ($argType->isNull()->yes()) {
            return new NullType();
        }

        if ($argType instanceof ConstantScalarType) {
            if (BuiltInFunction::LOWER === $expression->getFunction()->getName()) {
                return new ConstantStringType(strtolower($argType->getValue()));
            }

            return new ConstantStringType(strtoupper($argType->getValue()));
        }

        if (TypeCombinator::containsNull($argType)) {
            return TypeCombinator::addNull($argType->toString());
        }

        return $argType->toString();
    }
}
