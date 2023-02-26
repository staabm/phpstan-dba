<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\SqlAst;

use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use SqlFtw\Sql\Expression\BuiltInFunction;
use SqlFtw\Sql\Expression\FunctionCall;

final class StrCaseReturnTypeExtension implements QueryFunctionReturnTypeExtension
{
    /**
     * @var list<string>
     */
    private $functions = [
        BuiltInFunction::LOWER,
        BuiltInFunction::LCASE,
        BuiltInFunction::UPPER,
        BuiltInFunction::UCASE,
    ];

    public function isFunctionSupported(FunctionCall $expression): bool
    {
        return \in_array($expression->getFunction()->getName(), $this->functions, true);
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

        $results = [];
        $constantStrings = $argType->getConstantStrings();
        foreach ($constantStrings as $constantString) {
            if (\in_array($expression->getFunction()->getName(), [BuiltInFunction::LOWER, BuiltInFunction::LCASE], true)) {
                $results[] = new ConstantStringType(strtolower($constantString->getValue()));

                continue;
            }

            $results[] = new ConstantStringType(strtoupper($constantString->getValue()));
        }

        if (count($results) > 0) {
            return TypeCombinator::union(...$results);
        }

        if (TypeCombinator::containsNull($argType)) {
            return TypeCombinator::addNull($argType->toString());
        }

        return $argType->toString();
    }
}
