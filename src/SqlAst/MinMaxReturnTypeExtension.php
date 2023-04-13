<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\SqlAst;

use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use SqlFtw\Sql\Expression\BuiltInFunction;
use SqlFtw\Sql\Expression\FunctionCall;

final class MinMaxReturnTypeExtension implements QueryFunctionReturnTypeExtension
{
    /**
     * @var bool
     */
    private $hasGroupBy;

    public function __construct(bool $hasGroupBy)
    {
        $this->hasGroupBy = $hasGroupBy;
    }

    public function isFunctionSupported(FunctionCall $expression): bool
    {
        return \in_array($expression->getFunction()->getName(), [BuiltInFunction::MIN, BuiltInFunction::MAX], true);
    }

    public function getReturnType(FunctionCall $expression, QueryScope $scope): ?Type
    {
        $args = $expression->getArguments();

        if (1 !== \count($args)) {
            return null;
        }

        $argType = $scope->getType($args[0]);

        if (! $this->hasGroupBy) {
            $argType = TypeCombinator::addNull($argType);
        }
        return $argType;
    }
}
