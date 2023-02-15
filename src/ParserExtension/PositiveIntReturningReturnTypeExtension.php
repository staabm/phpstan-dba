<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\ParserExtension;

use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\Type;
use SqlFtw\Sql\Expression\BuiltInFunction;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\Expression\FunctionCall;

/**
 * @implements QueryExpressionReturnTypeExtension<FunctionCall>
 */
final class PositiveIntReturningReturnTypeExtension implements QueryExpressionReturnTypeExtension
{
    /**
     * @var list<string>
     */
    private $functions = [
        BuiltInFunction::COUNT,
        BuiltInFunction::LENGTH,
        BuiltInFunction::CHAR_LENGTH,
        BuiltInFunction::CHARACTER_LENGTH,
        BuiltInFunction::OCTET_LENGTH,
        BuiltInFunction::FIELD,
    ];

    public function isExpressionSupported(ExpressionNode $expression): bool
    {
        return
            $expression instanceof FunctionCall
            && \in_array($expression->getFunction()->getName(), $this->functions, true);
    }

    public function getTypeFromExpression(ExpressionNode $expression, QueryScope $scope): Type
    {
        return IntegerRangeType::fromInterval(0, null);
    }
}
