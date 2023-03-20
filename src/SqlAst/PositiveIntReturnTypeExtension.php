<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\SqlAst;

use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\Type;
use SqlFtw\Sql\Expression\BuiltInFunction;
use SqlFtw\Sql\Expression\FunctionCall;

final class PositiveIntReturnTypeExtension implements QueryFunctionReturnTypeExtension
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

    public function isFunctionSupported(FunctionCall $expression): bool
    {
        return \in_array($expression->getFunction()->getName(), $this->functions, true);
    }

    public function getReturnType(FunctionCall $expression, QueryScope $scope): Type
    {
        return IntegerRangeType::fromInterval(0, null);
    }
}
