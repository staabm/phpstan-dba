<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\SqlAst;

use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use SqlFtw\Sql\Expression\BuiltInFunction;
use SqlFtw\Sql\Expression\FunctionCall;

final class SumReturnTypeExtension implements QueryFunctionReturnTypeExtension
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
        return \in_array($expression->getFunction()->getName(), [BuiltInFunction::SUM], true);
    }

    public function getReturnType(FunctionCall $expression, QueryScope $scope): ?Type
    {
        $args = $expression->getArguments();

        if (1 !== \count($args)) {
            return null;
        }

        $argType = $scope->getType($args[0]);
        if ($argType->isNull()->yes()) {
            return new NullType();
        }

        $containsNull = TypeCombinator::containsNull($argType);
        $argType = TypeCombinator::removeNull($argType);
        if ($argType->isInteger()->yes()) {
            // sum(IntegerRange) is a unbound integer
            $result = new IntegerType();
        } elseif ($argType->isFloat()->yes()) {
            $result = new FloatType();
        } else {
            $result = $argType;
        }

        if ($containsNull || ! $this->hasGroupBy) {
            $result = TypeCombinator::addNull($result);
        }
        return $result;
    }
}
